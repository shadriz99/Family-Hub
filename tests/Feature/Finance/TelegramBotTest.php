<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Core\Actions\CreateHousehold;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use App\Modules\Finance\Enums\AccountType;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Events\TransactionRecorded;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\TelegramLink;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Services\CategorySuggestionService;
use App\Modules\Finance\Services\NaturalExpenseParser;
use App\Modules\Finance\Services\ReceiptOcrService;
use App\Modules\Finance\Services\TelegramBotService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('telegram.bot_token', 'mock_bot_token_123');
    config()->set('telegram.webhook_secret', 'mock_secret_abc');

    $this->user = User::create([
        'name' => 'Shadri Zulkurniadi',
        'email' => 'shadri@example.com',
        'password' => bcrypt('password123'),
    ]);

    $this->household = app(CreateHousehold::class)->handle(
        $this->user,
        'Keluarga Shadri',
        1,
        'Asia/Jakarta'
    );
    $this->household->onboarded_at = now()->toImmutable();
    $this->household->save();
    app(HouseholdContext::class)->use($this->household);

    $this->cashAccount = Account::create([
        'household_id' => $this->household->id,
        'name' => 'Dompet Kas',
        'type' => AccountType::Cash,
        'opening_balance' => 1_000_000,
        'opening_date' => now()->toDateString(),
        'color' => '#10b981',
        'icon' => 'wallet',
        'created_by' => $this->user->id,
    ]);

    $this->bankAccount = Account::create([
        'household_id' => $this->household->id,
        'name' => 'BCA',
        'type' => AccountType::Bank,
        'opening_balance' => 5_000_000,
        'opening_date' => now()->toDateString(),
        'color' => '#3b82f6',
        'icon' => 'building-library',
        'created_by' => $this->user->id,
    ]);

    $this->botService = app(TelegramBotService::class);
});

it('rejects webhook request with invalid secret token', function (): void {
    $response = $this->postJson('/telegram/webhook', [
        'update_id' => 100,
        'message' => ['text' => 'hello'],
    ], [
        'X-Telegram-Bot-Api-Secret-Token' => 'wrong_token',
    ]);

    $response->assertStatus(403);
});

it('links a Telegram user via /start command with valid code', function (): void {
    Http::fake([
        'https://api.telegram.org/bot*' => Http::response(['ok' => true], 200),
    ]);

    $link = TelegramLink::create([
        'household_id' => $this->household->id,
        'user_id' => $this->user->id,
        'link_code' => '998877',
        'link_code_expires_at' => CarbonImmutable::now()->addMinutes(15),
    ]);

    $update = [
        'update_id' => 1001,
        'message' => [
            'chat' => ['id' => 12345678],
            'from' => ['id' => 12345678, 'username' => 'shadriz'],
            'text' => '/start 998877',
        ],
    ];

    $result = $this->botService->handleUpdate($update);

    expect($result['status'])->toBe('linked');

    $link->refresh();
    expect($link->telegram_chat_id)->toBe('12345678')
        ->and($link->telegram_username)->toBe('shadriz')
        ->and($link->link_code)->toBeNull();
});

it('creates draft expense on natural text message and saves on callback confirmation', function (): void {
    Event::fake([TransactionRecorded::class]);
    Http::fake([
        'https://api.telegram.org/bot*' => Http::response(['ok' => true], 200),
    ]);

    // Pre-link user
    $link = TelegramLink::create([
        'household_id' => $this->household->id,
        'user_id' => $this->user->id,
        'telegram_chat_id' => '12345678',
        'telegram_username' => 'shadriz',
    ]);

    // 1. Send natural text message: "bensin pertamax 50k bca"
    $msgUpdate = [
        'update_id' => 2001,
        'message' => [
            'chat' => ['id' => 12345678],
            'from' => ['id' => 12345678, 'username' => 'shadriz'],
            'text' => 'bensin pertamax 50k bca',
        ],
    ];

    $draftRes = $this->botService->handleUpdate($msgUpdate);
    expect($draftRes['status'])->toBe('draft_created');

    $link->refresh();
    expect($link->pending_draft)->not->toBeNull()
        ->and($link->pending_draft['amount'])->toBe(50000)
        ->and($link->pending_draft['payee'])->toBe('Bensin Pertamax')
        ->and($link->pending_draft['account_id'])->toBe($this->bankAccount->id);

    // 2. User taps [ ✅ Simpan ] inline button -> callback query "confirm_tx"
    $callbackUpdate = [
        'update_id' => 2002,
        'callback_query' => [
            'id' => 'cb_query_999',
            'from' => ['id' => 12345678, 'username' => 'shadriz'],
            'data' => 'confirm_tx',
            'message' => [
                'message_id' => 77,
                'chat' => ['id' => 12345678],
            ],
        ],
    ];

    $confirmRes = $this->botService->handleUpdate($callbackUpdate);
    expect($confirmRes['status'])->toBe('saved');

    // Verify transaction exists in database
    $tx = Transaction::where('household_id', $this->household->id)->first();
    expect($tx)->not->toBeNull()
        ->and($tx->type)->toBe(TransactionType::Expense)
        ->and($tx->amount)->toBe(50000)
        ->and($tx->payee)->toBe('Bensin Pertamax')
        ->and($tx->account_id)->toBe($this->bankAccount->id)
        ->and($tx->created_by)->toBe($this->user->id);

    Event::assertDispatched(TransactionRecorded::class);

    // Draft is now cleared
    $link->refresh();
    expect($link->pending_draft)->toBeNull();
});

it('clears draft when user taps cancel', function (): void {
    Http::fake([
        'https://api.telegram.org/bot*' => Http::response(['ok' => true], 200),
    ]);

    $link = TelegramLink::create([
        'household_id' => $this->household->id,
        'user_id' => $this->user->id,
        'telegram_chat_id' => '12345678',
        'pending_draft' => [
            'amount' => 30000,
            'payee' => 'Makan',
            'account_id' => $this->cashAccount->id,
        ],
    ]);

    $callbackUpdate = [
        'update_id' => 3001,
        'callback_query' => [
            'id' => 'cb_query_888',
            'from' => ['id' => 12345678],
            'data' => 'cancel_tx',
            'message' => [
                'message_id' => 78,
                'chat' => ['id' => 12345678],
            ],
        ],
    ];

    $res = $this->botService->handleUpdate($callbackUpdate);
    expect($res['status'])->toBe('cancelled');

    $link->refresh();
    expect($link->pending_draft)->toBeNull();
    expect(Transaction::where('household_id', $this->household->id)->count())->toBe(0);
});

it('handles /saldo and /hariini commands', function (): void {
    Http::fake([
        'https://api.telegram.org/bot*' => Http::response(['ok' => true], 200),
    ]);

    TelegramLink::create([
        'household_id' => $this->household->id,
        'user_id' => $this->user->id,
        'telegram_chat_id' => '12345678',
    ]);

    // /saldo
    $saldoRes = $this->botService->handleUpdate([
        'update_id' => 4001,
        'message' => [
            'chat' => ['id' => 12345678],
            'from' => ['id' => 12345678],
            'text' => '/saldo',
        ],
    ]);
    expect($saldoRes['status'])->toBe('saldo_sent');

    // /hariini
    $hariiniRes = $this->botService->handleUpdate([
        'update_id' => 4002,
        'message' => [
            'chat' => ['id' => 12345678],
            'from' => ['id' => 12345678],
            'text' => '/hariini',
        ],
    ]);
    expect($hariiniRes['status'])->toBe('hariini_sent');
});

it('unlinks account via /unlink command', function (): void {
    Http::fake([
        'https://api.telegram.org/bot*' => Http::response(['ok' => true], 200),
    ]);

    $link = TelegramLink::create([
        'household_id' => $this->household->id,
        'user_id' => $this->user->id,
        'telegram_chat_id' => '12345678',
        'telegram_username' => 'shadriz',
    ]);

    $res = $this->botService->handleUpdate([
        'update_id' => 5001,
        'message' => [
            'chat' => ['id' => 12345678],
            'from' => ['id' => 12345678],
            'text' => '/unlink',
        ],
    ]);

    expect($res['status'])->toBe('unlinked');

    $link->refresh();
    expect($link->telegram_chat_id)->toBeNull()
        ->and($link->telegram_username)->toBeNull();
});

it('processes photo receipt via OCR and creates draft', function (): void {
    Http::fake([
        'https://api.telegram.org/bot*/getFile*' => Http::response([
            'ok' => true,
            'result' => [
                'file_id' => 'photo_file_123',
                'file_path' => 'photos/receipt.jpg',
            ],
        ], 200),
        'https://api.telegram.org/file/bot*/*' => Http::response('mock_image_binary_content', 200),
        'https://api.telegram.org/bot*' => Http::response(['ok' => true], 200),
    ]);

    // Mock OCR service to return a structured scan
    $mockOcr = \Mockery::mock(ReceiptOcrService::class);
    $mockOcr->shouldReceive('scan')
        ->once()
        ->andReturn([
            'payee' => 'Indomaret Point',
            'amount' => 45000,
            'confidence' => 0.95,
            'occurred_on' => CarbonImmutable::now(),
            'raw_text' => 'Indomaret Point Total 45.000',
        ]);

    $botService = new TelegramBotService(
        app(NaturalExpenseParser::class),
        $mockOcr,
        app(CategorySuggestionService::class),
        app(\App\Modules\Finance\Services\AccountBalanceService::class),
        app(HouseholdContext::class)
    );

    $link = TelegramLink::create([
        'household_id' => $this->household->id,
        'user_id' => $this->user->id,
        'telegram_chat_id' => '12345678',
    ]);

    $update = [
        'update_id' => 6001,
        'message' => [
            'chat' => ['id' => 12345678],
            'from' => ['id' => 12345678],
            'photo' => [
                ['file_id' => 'small_thumb', 'width' => 100, 'height' => 100],
                ['file_id' => 'photo_file_123', 'width' => 800, 'height' => 800],
            ],
        ],
    ];

    $res = $botService->handleUpdate($update);

    expect($res['status'])->toBe('draft_created');

    $link->refresh();
    expect($link->pending_draft)->not->toBeNull()
        ->and($link->pending_draft['amount'])->toBe(45000)
        ->and($link->pending_draft['payee'])->toBe('Indomaret Point');
});

it('runs telegram:poll command with --once flag', function (): void {
    Http::fake([
        'https://api.telegram.org/bot*/getUpdates*' => Http::response([
            'ok' => true,
            'result' => [],
        ], 200),
    ]);

    $this->artisan('telegram:poll', ['--once' => true, '--timeout' => 1])
        ->expectsOutput('Starting Telegram Bot Poller for Family Hub (ODROID)...')
        ->expectsOutput('Poller stopped.')
        ->assertSuccessful();
});
