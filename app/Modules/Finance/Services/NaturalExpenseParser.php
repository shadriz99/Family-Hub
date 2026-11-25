<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Core\Models\Household;
use App\Core\Support\Money;
use App\Modules\Finance\Enums\AllocationCategory;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Category;
use Carbon\CarbonImmutable;

final class NaturalExpenseParser
{
    public function __construct(
        private readonly CategorySuggestionService $categorySuggester
    ) {
    }

    /**
     * Parse natural language text into expense attributes.
     *
     * Examples:
     * - "kopi 25rb"
     * - "bensin 50.000 bca"
     * - "beli tiket bioskop 85k tunai"
     * - "1.5jt servis motor di bengkel honda"
     * - "superindo 150rb BCA belanja bulanan"
     *
     * @return array{
     *     is_valid: bool,
     *     amount: int,
     *     formatted_amount: string,
     *     payee: string,
     *     account_id: string|null,
     *     account_name: string|null,
     *     category_id: string|null,
     *     category_name: string|null,
     *     allocation: AllocationCategory|null,
     *     occurred_on: CarbonImmutable,
     *     raw_text: string,
     *     error_message: string|null
     * }
     */
    public function parse(Household $household, string $text): array
    {
        $rawText = trim($text);

        if ($rawText === '') {
            return $this->failureResult($rawText, 'Teks pengeluaran kosong.');
        }

        // 1. Extract Amount
        $amountInfo = $this->extractAmount($rawText);
        if ($amountInfo === null) {
            return $this->failureResult($rawText, 'Nominal transaksi tidak terdeteksi.');
        }

        $amount = $amountInfo['amount'];
        $remainingText = $amountInfo['remaining_text'];

        // 2. Extract Account from Household's active accounts
        $accountInfo = $this->extractAccount($household, $remainingText);
        $account = $accountInfo['account'];
        $remainingText = $accountInfo['remaining_text'];

        // 3. Clean Payee & Description
        $payee = $this->cleanPayee($remainingText);
        if ($payee === '') {
            $payee = 'Pengeluaran';
        }

        // 4. Determine Category (History suggestion -> Keyword match -> Default Expense category)
        $category = $this->determineCategory($household, $payee, $rawText);

        $allocation = $category?->default_allocation;

        return [
            'is_valid' => true,
            'amount' => $amount,
            'formatted_amount' => Money::format($amount),
            'payee' => $payee,
            'account_id' => $account?->id,
            'account_name' => $account !== null ? $account->name : 'Kas / Tunai',
            'category_id' => $category?->id,
            'category_name' => $category?->name,
            'allocation' => $allocation,
            'occurred_on' => CarbonImmutable::now(),
            'raw_text' => $rawText,
            'error_message' => null,
        ];
    }

    /**
     * @return array{amount: int, remaining_text: string}|null
     */
    private function extractAmount(string $text): ?array
    {
        // Pattern matches:
        // - Rp 25.000 / Rp25000 / Rp 25k / Rp 1.5jt
        // - 25rb / 25k / 25ribu / 25 rb / 25 k
        // - 1.5jt / 1,5jt / 2 juta
        // - 50.000 / 12,500 / 50000
        $pattern = '/(?:^|\s)(?:rp\.?\s*)?(\d+(?:[.,]\d+)?\s*(?:rb|ribu|k|jt|juta)|\d{1,3}(?:\.\d{3})+|\d+)(?:\s|$)/i';

        if (! preg_match($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $rawMatch = $matches[1][0];
        $fullMatch = $matches[0][0];

        $normalized = strtolower(trim($rawMatch));
        $normalized = str_replace([' ', 'k', 'ribu', 'juta'], ['', 'rb', 'rb', 'jt'], $normalized);

        // Convert decimal comma to standard dot or format for Money::parse
        // Example: 1.5jt -> 1,5jt for Money::parse fraction support
        if (str_contains($normalized, '.') && (str_ends_with($normalized, 'rb') || str_ends_with($normalized, 'jt'))) {
            $normalized = str_replace('.', ',', $normalized);
        }

        try {
            $amount = Money::parse($normalized);
        } catch (\Throwable) {
            // Fallback for simple plain numbers without dots
            $cleanDigits = (int) preg_replace('/[^\d]/', '', $normalized);
            if ($cleanDigits <= 0) {
                return null;
            }
            $amount = $cleanDigits;
        }

        if ($amount <= 0) {
            return null;
        }

        // Replace matched amount from text safely
        $matchPos = $matches[0][1];
        $matchLen = strlen($fullMatch);
        $remainingText = substr_replace($text, ' ', $matchPos, $matchLen);

        return [
            'amount' => $amount,
            'remaining_text' => trim(preg_replace('/\s+/', ' ', $remainingText) ?? ''),
        ];
    }

    /**
     * @return array{account: Account|null, remaining_text: string}
     */
    private function extractAccount(Household $household, string $text): array
    {
        $accounts = Account::query()
            ->where('household_id', $household->id)
            ->whereNull('archived_at')
            ->orderBy('sort_order')
            ->get();

        if ($accounts->isEmpty()) {
            return ['account' => null, 'remaining_text' => $text];
        }

        $lowerText = strtolower($text);

        // Alias mapping for common account synonyms
        $aliases = [
            'kas' => ['kas', 'tunai', 'cash', 'dompet'],
            'tunai' => ['kas', 'tunai', 'cash', 'dompet'],
            'bca' => ['bca', 'bank bca'],
            'mandiri' => ['mandiri', 'bank mandiri', 'livin'],
            'bri' => ['bri', 'bank bri', 'brimo'],
            'bni' => ['bni', 'bank bni'],
            'jago' => ['jago', 'bank jago'],
            'gopay' => ['gopay', 'go pay'],
            'ovo' => ['ovo'],
            'dana' => ['dana'],
            'shopeepay' => ['shopeepay', 'shopee pay', 'spay'],
        ];

        foreach ($accounts as $acc) {
            $accName = strtolower($acc->name);

            // Direct check with account name
            if (preg_match('/\b' . preg_quote($accName, '/') . '\b/i', $text, $m)) {
                $cleanText = trim((string) preg_replace('/\b' . preg_quote($m[0], '/') . '\b/i', '', $text));

                return ['account' => $acc, 'remaining_text' => preg_replace('/\s+/', ' ', $cleanText) ?? ''];
            }

            // Check against aliases
            foreach ($aliases as $key => $synonyms) {
                if (str_contains($accName, $key)) {
                    foreach ($synonyms as $synonym) {
                        if (preg_match('/\b' . preg_quote($synonym, '/') . '\b/i', $text, $m)) {
                            $cleanText = trim((string) preg_replace('/\b' . preg_quote($m[0], '/') . '\b/i', '', $text));

                            return ['account' => $acc, 'remaining_text' => preg_replace('/\s+/', ' ', $cleanText) ?? ''];
                        }
                    }
                }
            }
        }

        // Fallback: pick the first liquid account (cash or bank or ewallet), or the first account
        $defaultAccount = $accounts->first(fn ($a) => in_array($a->type->value, ['cash', 'bank', 'ewallet'], true)) ?? $accounts->first();

        return ['account' => $defaultAccount, 'remaining_text' => $text];
    }

    private function cleanPayee(string $text): string
    {
        $text = trim($text);

        // Remove filler prefixes like "beli", "bayar", "buat", "untuk", "ke", "di"
        $cleaned = preg_replace('/^(?:beli|bayar|buat|untuk|ke|di)\s+/i', '', $text);
        $cleaned = trim((string) $cleaned);

        // Remove trailing prepositions/fillers
        $cleaned = preg_replace('/\s+(?:di|ke|via|pake|pakai)$/i', '', $cleaned);
        $cleaned = trim((string) $cleaned);

        return mb_convert_case($cleaned, MB_CASE_TITLE, 'UTF-8');
    }

    private function determineCategory(Household $household, string $payee, string $rawText): ?Category
    {
        // 1. History-based suggestion for this payee
        $suggested = $this->categorySuggester->suggestCategory($household, $payee, TransactionType::Expense);
        if ($suggested !== null) {
            return $suggested;
        }

        // 2. Keyword mapping against subcategory & category names
        $categories = Category::query()
            ->where('household_id', $household->id)
            ->where('type', 'expense')
            ->whereNull('archived_at')
            ->get();

        $combinedText = strtolower($payee . ' ' . $rawText);

        $keywordMap = [
            'bensin' => ['bensin', 'pertamax', 'pertalite', 'solar', 'spbu', 'shell', 'bbm'],
            'parkir' => ['parkir', 'toll', 'tol'],
            'kopi' => ['kopi', 'coffee', 'cafe', 'starbucks', 'kenangan', 'janji jiwa'],
            'makan' => ['makan', 'lunch', 'dinner', 'sarapan', 'nasi', 'bakso', 'mie', 'soto', 'warteg', 'resto', 'ayam', 'gofood', 'shopeefood', 'grabfood'],
            'dapur' => ['sayur', 'buah', 'beras', 'minyak', 'telur', 'pasar', 'daging', 'ikan'],
            'belanja' => ['superindo', 'indomaret', 'alfamart', 'alfamidi', 'lotte', 'hypermart', 'tokopedia', 'shopee'],
            'listrik' => ['listrik', 'pln', 'token'],
            'pulsa' => ['pulsa', 'paket data', 'kuota', 'telkomsel', 'indosat', 'xl', 'byu'],
            'kesehatan' => ['obat', 'apotek', 'klinik', 'dokter', 'paracetamol', 'panadol', 'vitamin'],
            'hiburan' => ['netflix', 'spotify', 'youtube', 'bioskop', 'xxi', 'game', 'steam'],
        ];

        foreach ($keywordMap as $categoryHint => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($combinedText, $kw)) {
                    // Find matching category or subcategory
                    $matched = $categories->first(function ($cat) use ($categoryHint, $kw) {
                        $catName = strtolower($cat->name);

                        return str_contains($catName, $categoryHint) || str_contains($catName, $kw);
                    });

                    if ($matched !== null) {
                        return $matched;
                    }
                }
            }
        }

        // 3. Fallback: match any category name directly
        foreach ($categories as $cat) {
            if (str_contains($combinedText, strtolower($cat->name))) {
                return $cat;
            }
        }

        // 4. Default: first primary expense category or subcategory
        return $categories->first(fn ($c) => $c->parent_id !== null) ?? $categories->first();
    }

    /**
     * @return array{
     *     is_valid: bool,
     *     amount: int,
     *     formatted_amount: string,
     *     payee: string,
     *     account_id: string|null,
     *     account_name: string|null,
     *     category_id: string|null,
     *     category_name: string|null,
     *     allocation: AllocationCategory|null,
     *     occurred_on: CarbonImmutable,
     *     raw_text: string,
     *     error_message: string|null
     * }
     */
    private function failureResult(string $rawText, string $errorMessage): array
    {
        return [
            'is_valid' => false,
            'amount' => 0,
            'formatted_amount' => 'Rp 0',
            'payee' => '',
            'account_id' => null,
            'account_name' => null,
            'category_id' => null,
            'category_name' => null,
            'allocation' => null,
            'occurred_on' => CarbonImmutable::now(),
            'raw_text' => $rawText,
            'error_message' => $errorMessage,
        ];
    }
}
