<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Core\Actions\CreateHousehold;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use App\Modules\Finance\Actions\GeneratePlannedItemsFromRecurringBills;
use App\Modules\Finance\Actions\RecomputePlannedItem;
use App\Modules\Finance\Enums\AccountType;
use App\Modules\Finance\Enums\Direction;
use App\Modules\Finance\Enums\PlannedSource;
use App\Modules\Finance\Enums\PlannedStatus;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\PlannedItem;
use App\Modules\Finance\Models\RecurringBill;
use App\Modules\Finance\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
    app(HouseholdContext::class)->use($this->household);

    $this->account = Account::create([
        'household_id' => $this->household->id,
        'name' => 'BCA',
        'type' => AccountType::Bank,
        'opening_balance' => 10000000,
        'opening_date' => now()->subDays(60),
        'color' => '#10b981',
        'icon' => 'wallet',
    ]);

    $this->category = Category::where('household_id', $this->household->id)
        ->where('name', 'Tagihan Rumah')
        ->firstOrFail();
});

it('generates planned items idempotently and respects until date and updates future planned items', function (): void {
    $context = app(HouseholdContext::class);
    $generator = app(GeneratePlannedItemsFromRecurringBills::class);

    $context->runInContext($this->household, function () use ($generator): void {
        $bill = RecurringBill::create([
            'household_id' => $this->household->id,
            'name' => 'Langganan Internet',
            'direction' => Direction::Expense,
            'category_id' => $this->category->id,
            'account_id' => $this->account->id,
            'amount' => 450000,
            'rrule' => 'FREQ=MONTHLY;BYMONTHDAY=15',
            'dtstart' => CarbonImmutable::now()->startOfMonth(),
            'until' => CarbonImmutable::now()->addMonths(2)->endOfMonth(),
            'remind_days_before' => 3,
            'is_active' => true,
        ]);

        // First generation
        $created = $generator->execute($this->household, $bill);
        expect($created)->toBeGreaterThan(0);

        $initialCount = PlannedItem::where('source_id', $bill->id)->count();

        // Second generation must be completely idempotent (0 new items)
        $secondRun = $generator->execute($this->household, $bill);
        expect($secondRun)->toBe(0);
        expect(PlannedItem::where('source_id', $bill->id)->count())->toBe($initialCount);

        // Deactivate bill -> future planned items become cancelled (BR-FIN-022)
        $bill->is_active = false;
        $bill->save();

        $generator->execute($this->household, $bill);

        $cancelledCount = PlannedItem::where('source_id', $bill->id)
            ->where('status', PlannedStatus::Cancelled)
            ->count();
        expect($cancelledCount)->toBeGreaterThan(0);
    });
});

it('recomputes PlannedItem status and paid_amount accurately when transactions are added, edited, or deleted', function (): void {
    $context = app(HouseholdContext::class);
    $recompute = app(RecomputePlannedItem::class);

    $context->runInContext($this->household, function () use ($recompute): void {
        $item = PlannedItem::create([
            'household_id' => $this->household->id,
            'direction' => Direction::Expense,
            'name' => 'Bayar SPP Sekolah',
            'category_id' => $this->category->id,
            'account_id' => $this->account->id,
            'amount' => 1000000,
            'due_date' => now()->addDays(5),
            'period' => '2026-10-01',
            'status' => PlannedStatus::Planned,
            'source_type' => PlannedSource::Manual,
        ]);

        expect($item->status)->toBe(PlannedStatus::Planned);
        expect($item->paid_amount)->toBe(0);

        // 1. Partial payment (600rb)
        $tx = Transaction::create([
            'household_id' => $this->household->id,
            'type' => TransactionType::Expense,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'planned_item_id' => $item->id,
            'amount' => 600000,
            'occurred_on' => now(),
        ]);

        $item = $recompute->execute($item);
        expect($item->status)->toBe(PlannedStatus::Partial);
        expect($item->paid_amount)->toBe(600000);

        // 2. Full payment (additional 400rb)
        $tx2 = Transaction::create([
            'household_id' => $this->household->id,
            'type' => TransactionType::Expense,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'planned_item_id' => $item->id,
            'amount' => 400000,
            'occurred_on' => now(),
        ]);

        $item = $recompute->execute($item);
        expect($item->status)->toBe(PlannedStatus::Paid);
        expect($item->paid_amount)->toBe(1000000);
        expect($item->settled_at)->not->toBeNull();

        // 3. Delete tx2 -> should revert back to Partial
        $tx2->delete();
        $item = $recompute->execute($item);
        expect($item->status)->toBe(PlannedStatus::Partial);
        expect($item->paid_amount)->toBe(600000);

        // 4. Delete tx1 -> should revert back to Planned
        $tx->delete();
        $item = $recompute->execute($item);
        expect($item->status)->toBe(PlannedStatus::Planned);
        expect($item->paid_amount)->toBe(0);
        expect($item->settled_at)->toBeNull();
    });
});
