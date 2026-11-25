<?php

declare(strict_types=1);

use App\Core\Enums\MemberRole;
use App\Core\Models\Household;
use App\Core\Models\HouseholdMember;
use App\Core\Support\HouseholdContext;
use App\Core\Support\Period;
use App\Models\User;
use App\Modules\Finance\Enums\AccountType;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Livewire\Transactions\Form;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Models\TransactionSplit;
use App\Modules\Finance\Services\BudgetService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::create([
        'name' => 'Doni Wahyudi',
        'email' => 'doni@familyhub.test',
        'password' => 'secret123',
    ]);

    $this->household = Household::create([
        'name' => 'Keluarga Doni',
        'timezone' => 'Asia/Jakarta',
        'finance_period_start_day' => 1,
    ]);

    $member = new HouseholdMember([
        'user_id' => $this->user->id,
        'role' => MemberRole::Owner,
        'color' => '#059669',
        'slot' => 1,
        'joined_at' => now(),
    ]);
    $member->household()->associate($this->household);
    $member->save();

    app(HouseholdContext::class)->use($this->household);

    $this->account = Account::create([
        'household_id' => $this->household->id,
        'name' => 'BCA Utama',
        'type' => AccountType::Bank,
        'opening_balance' => 5000000,
        'opening_date' => '2026-01-01',
        'sort_order' => 1,
        'created_by' => $this->user->id,
    ]);

    $this->catGroceries = Category::create([
        'household_id' => $this->household->id,
        'name' => 'Bahan Makanan',
        'type' => CategoryType::Expense,
        'sort_order' => 1,
        'created_by' => $this->user->id,
    ]);

    $this->catHousehold = Category::create([
        'household_id' => $this->household->id,
        'name' => 'Kebutuhan Rumah',
        'type' => CategoryType::Expense,
        'sort_order' => 2,
        'created_by' => $this->user->id,
    ]);
});

test('transaction form rejects split when total splits do not match transaction amount', function (): void {
    Livewire::actingAs($this->user)
        ->test(Form::class)
        ->set('type', 'expense')
        ->set('mode', 'detail')
        ->set('account_id', $this->account->id)
        ->set('amount', '100.000')
        ->set('occurred_on', now()->toDateString())
        ->set('is_split', true)
        ->set('splits', [
            ['category_id' => $this->catGroceries->id, 'amount' => '40.000', 'notes' => 'Sayuran'],
            ['category_id' => $this->catHousehold->id, 'amount' => '50.000', 'notes' => 'Detergen'],
        ])
        ->call('saveTransaction')
        ->assertHasErrors('splits');

    expect(Transaction::count())->toBe(0)
        ->and(TransactionSplit::count())->toBe(0);
});

test('transaction form creates transaction and splits when amounts match strictly', function (): void {
    Livewire::actingAs($this->user)
        ->test(Form::class)
        ->set('type', 'expense')
        ->set('mode', 'detail')
        ->set('account_id', $this->account->id)
        ->set('amount', '100.000')
        ->set('occurred_on', now()->toDateString())
        ->set('is_split', true)
        ->set('splits', [
            ['category_id' => $this->catGroceries->id, 'amount' => '40.000', 'notes' => 'Sayuran & Buah'],
            ['category_id' => $this->catHousehold->id, 'amount' => '60.000', 'notes' => 'Detergen & Sabun'],
        ])
        ->call('saveTransaction')
        ->assertHasNoErrors()
        ->assertRedirect(route('finance.transactions.index'));

    $tx = Transaction::latest('id')->first();
    expect($tx)->not->toBeNull()
        ->and($tx->amount)->toBe(100000)
        ->and($tx->hasSplits())->toBeTrue()
        ->and($tx->splits)->toHaveCount(2);

    $split1 = $tx->splits->firstWhere('category_id', $this->catGroceries->id);
    $split2 = $tx->splits->firstWhere('category_id', $this->catHousehold->id);

    expect($split1->amount)->toBe(40000)
        ->and($split1->notes)->toBe('Sayuran & Buah')
        ->and($split2->amount)->toBe(60000)
        ->and($split2->notes)->toBe('Detergen & Sabun');
});

test('budget service attributes split amounts accurately to respective categories without double count', function (): void {
    $tx = Transaction::create([
        'household_id' => $this->household->id,
        'type' => TransactionType::Expense,
        'account_id' => $this->account->id,
        'category_id' => $this->catGroceries->id,
        'amount' => 100000,
        'occurred_on' => '2026-11-05',
        'created_by' => $this->user->id,
    ]);

    TransactionSplit::create([
        'household_id' => $this->household->id,
        'transaction_id' => $tx->id,
        'category_id' => $this->catGroceries->id,
        'amount' => 35000,
        'notes' => 'Porsi bahan makanan',
    ]);

    TransactionSplit::create([
        'household_id' => $this->household->id,
        'transaction_id' => $tx->id,
        'category_id' => $this->catHousehold->id,
        'amount' => 65000,
        'notes' => 'Porsi sabun & pembersih',
    ]);

    $budgetService = app(BudgetService::class);
    $period = new Period(
        CarbonImmutable::parse('2026-11-01'),
        CarbonImmutable::parse('2026-11-30')
    );

    $spentGroceries = $budgetService->calculateSpent($this->catGroceries, $period);
    $spentHousehold = $budgetService->calculateSpent($this->catHousehold, $period);

    // Groceries only gets its 35.000 split, Household gets its 65.000 split
    expect($spentGroceries)->toBe(35000)
        ->and($spentHousehold)->toBe(65000)
        ->and($spentGroceries + $spentHousehold)->toBe(100000);
});
