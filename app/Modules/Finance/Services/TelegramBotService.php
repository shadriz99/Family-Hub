<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Core\Models\Household;
use App\Core\Support\HouseholdContext;
use App\Core\Support\Money;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Events\TransactionRecorded;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\TelegramLink;
use App\Modules\Finance\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class TelegramBotService
{
    private string $botToken;
    private string $apiUrl;

    public function __construct(
        private readonly NaturalExpenseParser $parser,
        private readonly ReceiptOcrService $ocrService,
        private readonly CategorySuggestionService $categorySuggester,
        private readonly AccountBalanceService $balanceService,
        private readonly HouseholdContext $context
    ) {
        $this->botToken = (string) config('telegram.bot_token', '');
        $this->apiUrl = (string) config('telegram.api_url', 'https://api.telegram.org/bot') . $this->botToken;
    }

    /**
     * Send message to Telegram chat.
     *
     * @param array<string, mixed>|null $replyMarkup
     * @return array<string, mixed>|null
     */
    public function sendMessage(int|string $chatId, string $text, ?array $replyMarkup = null): ?array
    {
        if ($this->botToken === '') {
            return null;
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ];

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        try {
            $response = Http::timeout(10)->post("{$this->apiUrl}/sendMessage", $payload);

            return $response->json();
        } catch (Throwable $e) {
            Log::error('Telegram sendMessage error: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Edit message text.
     *
     * @param array<string, mixed>|null $replyMarkup
     * @return array<string, mixed>|null
     */
    public function editMessageText(int|string $chatId, int $messageId, string $text, ?array $replyMarkup = null): ?array
    {
        if ($this->botToken === '') {
            return null;
        }

        $payload = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ];

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        try {
            $response = Http::timeout(10)->post("{$this->apiUrl}/editMessageText", $payload);

            return $response->json();
        } catch (Throwable $e) {
            Log::error('Telegram editMessageText error: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Answer callback query.
     *
     * @return array<string, mixed>|null
     */
    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null, bool $showAlert = false): ?array
    {
        if ($this->botToken === '') {
            return null;
        }

        $payload = [
            'callback_query_id' => $callbackQueryId,
            'show_alert' => $showAlert,
        ];

        if ($text !== null) {
            $payload['text'] = $text;
        }

        try {
            $response = Http::timeout(10)->post("{$this->apiUrl}/answerCallbackQuery", $payload);

            return $response->json();
        } catch (Throwable $e) {
            Log::error('Telegram answerCallbackQuery error: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Get updates for long-polling.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUpdates(int $offset = 0, int $limit = 100, int $timeout = 20): array
    {
        if ($this->botToken === '') {
            return [];
        }

        try {
            $response = Http::timeout($timeout + 5)->get("{$this->apiUrl}/getUpdates", [
                'offset' => $offset,
                'limit' => $limit,
                'timeout' => $timeout,
            ]);

            if ($response->successful()) {
                return (array) ($response->json('result') ?? []);
            }
        } catch (Throwable $e) {
            Log::error('Telegram getUpdates error: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Set webhook URL.
     *
     * @return array<string, mixed>|null
     */
    public function setWebhook(string $url, ?string $secretToken = null): ?array
    {
        $payload = ['url' => $url];
        if ($secretToken !== null) {
            $payload['secret_token'] = $secretToken;
        }

        try {
            $response = Http::timeout(10)->post("{$this->apiUrl}/setWebhook", $payload);

            return $response->json();
        } catch (Throwable $e) {
            Log::error('Telegram setWebhook error: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Download Telegram file to a local temporary path.
     */
    public function downloadTelegramFile(string $fileId): ?string
    {
        try {
            $res = Http::timeout(10)->get("{$this->apiUrl}/getFile", ['file_id' => $fileId]);
            if (! $res->successful()) {
                return null;
            }

            $filePath = $res->json('result.file_path');
            if (empty($filePath)) {
                return null;
            }

            $downloadUrl = "https://api.telegram.org/file/bot{$this->botToken}/{$filePath}";
            $tempPath = sys_get_temp_dir() . '/tg_receipt_' . uniqid('', true) . '.jpg';

            $fileContents = Http::timeout(25)->get($downloadUrl)->body();
            file_put_contents($tempPath, $fileContents);

            return $tempPath;
        } catch (Throwable $e) {
            Log::error('Telegram downloadFile error: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Main dispatcher for incoming Telegram updates.
     *
     * @param array<string, mixed> $update
     * @return array{status: string, message: string}
     */
    public function handleUpdate(array $update): array
    {
        // 1. Handle Callback Query (Button clicks)
        if (isset($update['callback_query'])) {
            return $this->handleCallbackQuery($update['callback_query']);
        }

        // 2. Handle Message
        if (isset($update['message'])) {
            return $this->handleMessage($update['message']);
        }

        return ['status' => 'ignored', 'message' => 'No recognized update payload.'];
    }

    /**
     * @param array<string, mixed> $callbackQuery
     * @return array{status: string, message: string}
     */
    private function handleCallbackQuery(array $callbackQuery): array
    {
        $queryId = (string) ($callbackQuery['id'] ?? '');
        $fromId = (string) ($callbackQuery['from']['id'] ?? '');
        $data = (string) ($callbackQuery['data'] ?? '');
        $message = $callbackQuery['message'] ?? [];
        $chatId = $message['chat']['id'] ?? $fromId;
        $messageId = $message['message_id'] ?? null;

        $link = TelegramLink::query()->where('telegram_chat_id', $fromId)->first();
        if ($link === null) {
            $this->answerCallbackQuery($queryId, 'Akun Anda belum terhubung.');

            return ['status' => 'unlinked', 'message' => 'Account not linked.'];
        }

        if ($data === 'cancel_tx') {
            $link->clearDraft();
            $this->answerCallbackQuery($queryId, 'Pencatatan dibatalkan.');
            if ($messageId !== null) {
                $this->editMessageText($chatId, (int) $messageId, "❌ *Pencatatan transaksi dibatalkan.*");
            }

            return ['status' => 'cancelled', 'message' => 'Transaction cancelled.'];
        }

        if ($data === 'confirm_tx') {
            $draft = $link->pending_draft;
            if (empty($draft)) {
                $this->answerCallbackQuery($queryId, 'Data draft transaksi tidak ditemukan atau sudah kadaluarsa.', true);
                if ($messageId !== null) {
                    $this->editMessageText($chatId, (int) $messageId, "⚠️ *Draft transaksi tidak ditemukan.*");
                }

                return ['status' => 'expired', 'message' => 'Draft expired.'];
            }

            // Save transaction under household context
            $household = $link->household;
            $user = $link->user;

            $this->context->runInContext($household, function () use ($household, $user, $draft): void {
                $amount = (int) ($draft['amount'] ?? 0);
                $accountId = $draft['account_id'];
                $categoryId = $draft['category_id'] ?? null;
                $payee = $draft['payee'] ?? null;
                $occurredOn = CarbonImmutable::parse($draft['occurred_on'] ?? now());

                $category = $categoryId ? Category::find($categoryId) : null;
                $allocation = $category?->default_allocation;

                DB::transaction(function () use ($household, $user, $amount, $accountId, $category, $allocation, $payee, $occurredOn): void {
                    $tx = new Transaction();
                    $tx->household_id = $household->id;
                    $tx->type = TransactionType::Expense;
                    $tx->account_id = $accountId;
                    $tx->category_id = $category?->id;
                    $tx->amount = $amount;
                    $tx->occurred_on = $occurredOn;
                    $tx->payee = $payee;
                    $tx->note = 'Dicatat via Telegram Bot';
                    $tx->allocation = $allocation;
                    $tx->created_by = $user->id;
                    $tx->save();

                    $this->balanceService->bustCache($accountId);
                    TransactionRecorded::dispatch($tx->id);
                });
            });

            $formatted = Money::format((int) $draft['amount']);
            $payee = $draft['payee'] ?? 'Pengeluaran';
            $accountName = $draft['account_name'] ?? 'Akun';
            $categoryName = $draft['category_name'] ?? 'Umum';

            $link->clearDraft();
            $this->answerCallbackQuery($queryId, 'Berhasil disimpan!');

            if ($messageId !== null) {
                $confirmedText = "✅ *Transaksi Berhasil Disimpan!*\n\n"
                    . "• *Keperluan:* {$payee}\n"
                    . "• *Nominal:* `{$formatted}`\n"
                    . "• *Akun:* {$accountName}\n"
                    . "• *Kategori:* {$categoryName}";

                $this->editMessageText($chatId, (int) $messageId, $confirmedText);
            }

            return ['status' => 'saved', 'message' => 'Transaction successfully saved.'];
        }

        return ['status' => 'unknown', 'message' => 'Unknown callback action.'];
    }

    /**
     * @param array<string, mixed> $message
     * @return array{status: string, message: string}
     */
    private function handleMessage(array $message): array
    {
        $chatId = $message['chat']['id'] ?? null;
        $fromId = (string) ($message['from']['id'] ?? $chatId);
        $username = $message['from']['username'] ?? null;
        $text = trim((string) ($message['text'] ?? ''));

        if ($chatId === null) {
            return ['status' => 'ignored', 'message' => 'Missing chat ID.'];
        }

        // 1. Check commands
        if (str_starts_with($text, '/start')) {
            return $this->handleStartCommand($chatId, $fromId, $username, $text);
        }

        if (str_starts_with($text, '/help') || str_starts_with($text, '/bantuan')) {
            $this->sendHelpMessage($chatId);

            return ['status' => 'help_sent', 'message' => 'Help message sent.'];
        }

        // Authenticate linked account
        $link = TelegramLink::query()->where('telegram_chat_id', $fromId)->first();
        if ($link === null) {
            $this->sendMessage(
                $chatId,
                "👋 *Halo! Akun Telegram Anda belum terhubung ke Family Hub.*\n\n"
                . "Untuk menghubungkannya:\n"
                . "1. Buka web Family Hub > menu **Pengaturan** > **Integrasi Telegram**\n"
                . "2. Tekan tombol **Hubungkan** untuk melihat kode verifikasi 6-digit\n"
                . "3. Kirimkan pesan `/start <kode>` ke bot ini."
            );

            return ['status' => 'unlinked', 'message' => 'User not linked.'];
        }

        if (str_starts_with($text, '/unlink')) {
            $link->update([
                'telegram_chat_id' => null,
                'telegram_username' => null,
            ]);
            $this->sendMessage($chatId, "🔌 *Akun Telegram berhasil diputuskan dari Family Hub.*");

            return ['status' => 'unlinked', 'message' => 'Account unlinked.'];
        }

        if (str_starts_with($text, '/saldo') || str_starts_with($text, '/balance')) {
            return $this->handleSaldoCommand($chatId, $link->household);
        }

        if (str_starts_with($text, '/hariini') || str_starts_with($text, '/today')) {
            return $this->handleHariIniCommand($chatId, $link->household);
        }

        // 2. Check Photo (Receipt OCR)
        if (! empty($message['photo'])) {
            return $this->handlePhotoMessage($chatId, $link, (array) $message['photo']);
        }

        // 3. Regular Text Expense Parsing
        return $this->handleTextExpense($chatId, $link, $text);
    }

    /**
     * @return array{status: string, message: string}
     */
    private function handleStartCommand(int|string $chatId, string $fromId, ?string $username, string $text): array
    {
        $parts = explode(' ', $text, 2);
        $code = isset($parts[1]) ? trim($parts[1]) : '';

        if ($code !== '') {
            $link = TelegramLink::query()
                ->where('link_code', $code)
                ->where('link_code_expires_at', '>=', now())
                ->first();

            if ($link !== null) {
                $link->update([
                    'telegram_chat_id' => $fromId,
                    'telegram_username' => $username,
                    'link_code' => null,
                    'link_code_expires_at' => null,
                ]);

                $userName = $link->user->name;
                $householdName = $link->household->name;

                $welcome = "🎉 *Selamat Datang di Family Hub Bot!*\n\n"
                    . "Akun Telegram Anda berhasil terhubung dengan akun **{$userName}** di keluarga **{$householdName}**.\n\n"
                    . "Sekarang Anda dapat mencatat pengeluaran langsung dari sini:\n"
                    . "• Cukup ketik: `kopi 25rb` atau `bensin 50.000 bca`\n"
                    . "• Atau kirim langsung **foto struk belanja** Anda!\n\n"
                    . "Ketik `/help` untuk panduan lengkap.";

                $this->sendMessage($chatId, $welcome);

                return ['status' => 'linked', 'message' => 'Successfully linked account.'];
            }

            $this->sendMessage($chatId, "⚠️ *Kode verifikasi tidak valid atau sudah kadaluarsa.* Silakan buat kode baru di Pengaturan Family Hub.");

            return ['status' => 'invalid_code', 'message' => 'Invalid or expired link code.'];
        }

        // Already linked check
        $existing = TelegramLink::query()->where('telegram_chat_id', $fromId)->first();
        if ($existing !== null) {
            $this->sendMessage(
                $chatId,
                "✅ *Akun Anda sudah terhubung ke Family Hub.*\n\n"
                . "Ketik pengeluaran yang ingin dicatat (contoh: `makan siang 35k`) atau ketik `/help`."
            );

            return ['status' => 'already_linked', 'message' => 'Account already linked.'];
        }

        $this->sendMessage(
            $chatId,
            "👋 *Halo! Akun Telegram Anda belum terhubung ke Family Hub.*\n\n"
            . "Silakan buka web Family Hub > Pengaturan > Integrasi Telegram untuk mendapatkan kode 6-digit, lalu kirim: `/start <kode>`"
        );

        return ['status' => 'unlinked', 'message' => 'Missing link code.'];
    }

    /**
     * @param array<int, array<string, mixed>> $photos
     * @return array{status: string, message: string}
     */
    private function handlePhotoMessage(int|string $chatId, TelegramLink $link, array $photos): array
    {
        $this->sendMessage($chatId, "⏳ *Sedang memproses struk dengan OCR lokal ODROID...*");

        // Telegram photos are ordered from smallest to largest; pick the last one
        $largestPhoto = end($photos);
        $fileId = $largestPhoto['file_id'] ?? null;

        if ($fileId === null) {
            $this->sendMessage($chatId, "❌ Gagal mengunduh foto struk.");

            return ['status' => 'error', 'message' => 'Missing file ID.'];
        }

        $tempPath = $this->downloadTelegramFile((string) $fileId);
        if ($tempPath === null || ! file_exists($tempPath)) {
            $this->sendMessage($chatId, "❌ Gagal memproses file foto dari Telegram.");

            return ['status' => 'error', 'message' => 'File download failed.'];
        }

        try {
            $ocrResult = $this->ocrService->scan($tempPath);
            @unlink($tempPath);

            $household = $link->household;
            $payee = $ocrResult['payee'] ?? 'Struk Belanja';
            $amount = $ocrResult['amount'] ?? 0;

            if ($amount <= 0) {
                $this->sendMessage(
                    $chatId,
                    "⚠️ *OCR selesai tetapi nominal tidak terbaca dengan jelas.*\n"
                    . "Merchant: *" . $payee . "*\n\n"
                    . "Silakan catat manual dengan teks (contoh: `" . $payee . " 50rb`)."
                );

                return ['status' => 'ocr_no_amount', 'message' => 'OCR amount not detected.'];
            }

            // Suggest category & select account
            $category = $this->categorySuggester->suggestCategory($household, $payee, TransactionType::Expense);
            $account = Account::query()
                ->where('household_id', $household->id)
                ->whereNull('archived_at')
                ->first();

            $formattedAmount = Money::format($amount);
            $confidencePct = round($ocrResult['confidence'] * 100);

            $draft = [
                'amount' => $amount,
                'payee' => $payee,
                'account_id' => $account?->id,
                'account_name' => $account !== null ? $account->name : 'Kas / Tunai',
                'category_id' => $category?->id,
                'category_name' => $category !== null ? $category->name : 'Kebutuhan Pokok',
                'occurred_on' => ($ocrResult['occurred_on'] ?? now())->toDateString(),
            ];

            $link->setDraft($draft);

            $text = "🧾 *Hasil Pindai Struk (OCR)*\n\n"
                . "• *Merchant:* {$payee}\n"
                . "• *Nominal:* `{$formattedAmount}`\n"
                . "• *Akun:* {$draft['account_name']}\n"
                . "• *Kategori:* {$draft['category_name']}\n"
                . "• *Akurasi:* {$confidencePct}%\n\n"
                . "Konfirmasi simpan pengeluaran ini?";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '✅ Simpan', 'callback_data' => 'confirm_tx'],
                        ['text' => '❌ Batal', 'callback_data' => 'cancel_tx'],
                    ],
                ],
            ];

            $this->sendMessage($chatId, $text, $keyboard);

            return ['status' => 'draft_created', 'message' => 'Receipt draft created.'];
        } catch (Throwable $e) {
            @unlink($tempPath);
            $this->sendMessage($chatId, "❌ Terjadi kesalahan saat membaca struk: " . $e->getMessage());

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array{status: string, message: string}
     */
    private function handleTextExpense(int|string $chatId, TelegramLink $link, string $text): array
    {
        $household = $link->household;
        $result = $this->parser->parse($household, $text);

        if (! $result['is_valid']) {
            $msg = "❌ *" . ($result['error_message'] ?? 'Format tidak dikenali') . "*\n\n"
                . "Contoh pencatatan:\n"
                . "• `kopi 25rb`\n"
                . "• `bensin 50.000 bca`\n"
                . "• `makan siang 35k tunai`\n"
                . "• `superindo 150rb belanja`";

            $this->sendMessage($chatId, $msg);

            return ['status' => 'invalid_input', 'message' => $result['error_message'] ?? 'Invalid input'];
        }

        $draft = [
            'amount' => $result['amount'],
            'payee' => $result['payee'],
            'account_id' => $result['account_id'],
            'account_name' => $result['account_name'],
            'category_id' => $result['category_id'],
            'category_name' => $result['category_name'] ?? 'Umum',
            'occurred_on' => $result['occurred_on']->toDateString(),
        ];

        $link->setDraft($draft);

        $textConfirm = "💸 *Konfirmasi Pengeluaran:*\n\n"
            . "• *Keperluan:* {$draft['payee']}\n"
            . "• *Nominal:* `{$result['formatted_amount']}`\n"
            . "• *Akun:* {$draft['account_name']}\n"
            . "• *Kategori:* {$draft['category_name']}\n\n"
            . "Simpan transaksi ini?";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '✅ Simpan', 'callback_data' => 'confirm_tx'],
                    ['text' => '❌ Batal', 'callback_data' => 'cancel_tx'],
                ],
            ],
        ];

        $this->sendMessage($chatId, $textConfirm, $keyboard);

        return ['status' => 'draft_created', 'message' => 'Draft created successfully.'];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function handleSaldoCommand(int|string $chatId, Household $household): array
    {
        $accounts = Account::query()
            ->where('household_id', $household->id)
            ->whereNull('archived_at')
            ->orderBy('sort_order')
            ->get();

        if ($accounts->isEmpty()) {
            $this->sendMessage($chatId, "Belum ada akun keuangan terdaftar.");

            return ['status' => 'empty', 'message' => 'No accounts found.'];
        }

        $lines = ["🏦 *Ringkasan Saldo Akun:*", ""];
        $totalBalance = 0;

        foreach ($accounts as $acc) {
            $balance = $this->balanceService->getBalance($acc);
            $totalBalance += $balance;
            $formatted = Money::format($balance);
            $lines[] = "• *{$acc->name}:* `{$formatted}`";
        }

        $lines[] = "";
        $lines[] = "💰 *Total Saldo:* `" . Money::format($totalBalance) . "`";

        $this->sendMessage($chatId, implode("\n", $lines));

        return ['status' => 'saldo_sent', 'message' => 'Account balances sent.'];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function handleHariIniCommand(int|string $chatId, Household $household): array
    {
        $today = CarbonImmutable::now()->toDateString();

        $transactions = Transaction::query()
            ->where('household_id', $household->id)
            ->where('type', TransactionType::Expense)
            ->where('occurred_on', $today)
            ->latest('created_at')
            ->get();

        $totalToday = $transactions->sum('amount');
        $formattedTotal = Money::format($totalToday);

        $lines = [
            "📅 *Pengeluaran Hari Ini ({$today}):*",
            "Total: `{$formattedTotal}`",
            "",
        ];

        if ($transactions->isEmpty()) {
            $lines[] = "_Belum ada pengeluaran tercatat hari ini._";
        } else {
            foreach ($transactions->take(5) as $tx) {
                $payee = $tx->payee ?? 'Pengeluaran';
                $amount = Money::format($tx->amount);
                $lines[] = "• {$payee}: `{$amount}`";
            }
            if ($transactions->count() > 5) {
                $lines[] = "_(dan " . ($transactions->count() - 5) . " transaksi lainnya)_";
            }
        }

        $this->sendMessage($chatId, implode("\n", $lines));

        return ['status' => 'hariini_sent', 'message' => 'Today expenses sent.'];
    }

    private function sendHelpMessage(int|string $chatId): void
    {
        $help = "🤖 *Panduan Family Hub Bot*\n\n"
            . "*Catat Pengeluaran Cepat:*\n"
            . "• `kopi 25rb`\n"
            . "• `bensin 50.000 bca`\n"
            . "• `makan siang 35k tunai`\n"
            . "• `superindo 150rb bca belanja bulanan`\n\n"
            . "*Pindai Struk (OCR):*\n"
            . "• Kirim foto struk belanja Anda langsung ke chat ini!\n\n"
            . "*Perintah Lainnya:*\n"
            . "• `/saldo` - Cek ringkasan saldo akun\n"
            . "• `/hariini` - Cek pengeluaran hari ini\n"
            . "• `/unlink` - Putuskan sambungan Telegram\n"
            . "• `/help` - Tampilkan panduan ini";

        $this->sendMessage($chatId, $help);
    }
}
