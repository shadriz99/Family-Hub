<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Core\Actions\CreateHousehold;
use App\Core\Support\HouseholdContext;
use App\Core\Support\Money;
use App\Models\User;
use App\Modules\Finance\Enums\AccountType;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Livewire\Transactions\Form as TransactionsForm;
use App\Modules\Finance\Livewire\Transactions\Index as TransactionsIndex;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Services\CategorySuggestionService;
use App\Modules\Finance\Services\ReceiptOcrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
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

    $this->actingAs($this->user);
});

it('supports Money::parse formatted amounts on quick add form', function (string $rawInput, int $expected): void {
    $parsed = Money::parse($rawInput);
    expect($parsed)->toBe($expected);
})->with([
    '45rb' => ['45rb', 45000],
    '1,2jt' => ['1,2jt', 1200000],
    'Rp 10.000' => ['Rp 10.000', 10000],
    '500000' => ['500000', 500000],
    '2,5jt' => ['2,5jt', 2500000],
]);

it('extracts merchant, date, and amount locally from receipt text without external network requests', function (): void {
    $ocrService = app(ReceiptOcrService::class);

    $receiptText = "INDOMARET POINT\nJl. Sudirman No 12\nTanggal: 2026-10-27\nItem 1 15.000\nItem 2 30.000\nTOTAL: Rp 45.000\nTerima Kasih";

    $result = $ocrService->extractFromText($receiptText);

    expect($result['payee'])->toBe('Indomaret');
    expect($result['occurred_on']?->toDateString())->toBe('2026-10-27');
    expect($result['amount'])->toBe(45000);
    expect($result['confidence'])->toBeGreaterThan(0.7);

    // It only suggests, never creates a transaction automatically
    expect(Transaction::count())->toBe(0);
});

it('provides category suggestions based on payee history without auto-saving prior to user confirmation', function (): void {
    $context = app(HouseholdContext::class);
    $suggester = app(CategorySuggestionService::class);

    $context->runInContext($this->household, function () use ($suggester): void {
        $account = Account::create([
            'household_id' => $this->household->id,
            'name' => 'BCA',
            'type' => AccountType::Bank,
            'opening_balance' => 1000000,
            'opening_date' => now()->subDays(5),
            'color' => '#10b981',
            'icon' => 'wallet',
        ]);

        $category = Category::where('household_id', $this->household->id)
            ->where('name', 'Belanja Dapur')
            ->firstOrFail();

        // Past transaction with payee Superindo
        Transaction::create([
            'household_id' => $this->household->id,
            'type' => TransactionType::Expense,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 120000,
            'occurred_on' => now()->subDays(2),
            'payee' => 'Superindo',
        ]);

        // Suggest category for same payee
        $suggested = $suggester->suggestCategory($this->household, 'Superindo', TransactionType::Expense);
        expect($suggested?->id)->toBe($category->id);

        // Case-insensitive matching
        $suggestedCase = $suggester->suggestCategory($this->household, 'superindo', TransactionType::Expense);
        expect($suggestedCase?->id)->toBe($category->id);

        // Unknown payee returns null
        $unknown = $suggester->suggestCategory($this->household, 'Toko Bangunan Antah Berantah', TransactionType::Expense);
        expect($unknown)->toBeNull();
    });
});

it('allows quick 1-tap category save from Livewire component and supports undo soft-delete', function (): void {
    $account = Account::create([
        'household_id' => $this->household->id,
        'name' => 'Dompet Cash',
        'type' => AccountType::Cash,
        'opening_balance' => 500000,
        'opening_date' => now()->subDays(5),
        'color' => '#10b981',
        'icon' => 'wallet',
    ]);

    $category = Category::where('household_id', $this->household->id)
        ->where('name', 'Makan di Luar')
        ->firstOrFail();

    // 1-Tap quick save
    Livewire::test(TransactionsForm::class)
        ->set('amount', '35rb')
        ->set('account_id', $account->id)
        ->call('quickSaveCategory', $category->id)
        ->assertRedirect(route('finance.transactions.index'));

    $tx = Transaction::latest('created_at')->first();
    expect($tx)->not->toBeNull();
    expect($tx->amount)->toBe(35000);
    expect($tx->category_id)->toBe($category->id);

    // Test undo from index page
    Livewire::test(TransactionsIndex::class)
        ->set('undoTransactionId', $tx->id)
        ->call('undoLastTransaction');

    // Should be soft-deleted
    expect(Transaction::find($tx->id))->toBeNull();
    expect(Transaction::withTrashed()->find($tx->id)?->trashed())->toBeTrue();
});

it('records transfers between accounts and creates linked admin fee transaction when fee is provided', function (): void {
    $accA = Account::create([
        'household_id' => $this->household->id,
        'name' => 'BCA',
        'type' => AccountType::Bank,
        'opening_balance' => 5000000,
        'opening_date' => now()->subDays(5),
        'color' => '#10b981',
        'icon' => 'wallet',
    ]);

    $accB = Account::create([
        'household_id' => $this->household->id,
        'name' => 'Gopay',
        'type' => AccountType::EWallet,
        'opening_balance' => 50000,
        'opening_date' => now()->subDays(5),
        'color' => '#3b82f6',
        'icon' => 'wallet',
    ]);

    Livewire::test(TransactionsForm::class)
        ->set('type', 'transfer')
        ->set('amount', '200rb')
        ->set('account_id', $accA->id)
        ->set('to_account_id', $accB->id)
        ->set('admin_fee', '2500')
        ->call('saveTransaction')
        ->assertRedirect(route('finance.transactions.index'));

    // Check main transfer transaction
    $transferTx = Transaction::where('type', TransactionType::Transfer)->first();
    expect($transferTx)->not->toBeNull();
    expect($transferTx->amount)->toBe(200000);
    expect($transferTx->account_id)->toBe($accA->id);
    expect($transferTx->to_account_id)->toBe($accB->id);

    // Check linked admin fee transaction (US-FIN-02 AC1)
    $feeTx = Transaction::where('related_transaction_id', $transferTx->id)->first();
    expect($feeTx)->not->toBeNull();
    expect($feeTx->type)->toBe(TransactionType::Expense);
    expect($feeTx->amount)->toBe(2500);
    expect($feeTx->category?->system_key)->toBe('bank_fee');
});

it('allows typing a custom subcategory manually on the fly and creates it under the parent category', function (): void {
    $account = Account::create([
        'household_id' => $this->household->id,
        'name' => 'BCA',
        'type' => AccountType::Bank,
        'opening_balance' => 2000000,
        'opening_date' => now()->subDays(5),
        'color' => '#10b981',
        'icon' => 'wallet',
    ]);

    $parentCategory = Category::where('household_id', $this->household->id)
        ->whereNull('parent_id')
        ->where('name', 'Lifestyle')
        ->firstOrFail();

    // User selects parent "Lifestyle", but types custom subcategory "Nonton Konser Musik"
    Livewire::test(TransactionsForm::class)
        ->set('type', 'expense')
        ->set('amount', '750rb')
        ->set('account_id', $account->id)
        ->set('parent_category_id', $parentCategory->id)
        ->set('subcategory_name', 'Nonton Konser Musik')
        ->call('saveTransaction')
        ->assertRedirect(route('finance.transactions.index'));

    // Verify subcategory was created automatically under Lifestyle
    $createdSub = Category::where('household_id', $this->household->id)
        ->where('parent_id', $parentCategory->id)
        ->where('name', 'Nonton Konser Musik')
        ->first();

    expect($createdSub)->not->toBeNull();
    expect($createdSub->default_allocation)->toBe($parentCategory->default_allocation);

    // Verify transaction is linked to this newly created subcategory
    $tx = Transaction::latest('created_at')->first();
    expect($tx)->not->toBeNull();
    expect($tx->amount)->toBe(750000);
    expect($tx->category_id)->toBe($createdSub->id);
});
