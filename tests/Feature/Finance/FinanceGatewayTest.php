<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Core\Actions\CreateHousehold;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use App\Modules\Finance\Contracts\FinanceGateway;
use App\Modules\Finance\DTO\ExpenseData;
use App\Modules\Finance\DTO\PlannedExpenseData;
use App\Modules\Finance\Enums\AccountType;
use App\Modules\Finance\Enums\PlannedStatus;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\PlannedItem;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Services\NullFinanceGateway;
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
        ->where('system_key', 'asset_maintenance')
        ->firstOrFail();
});

it('records and updates external module expenses through DefaultFinanceGateway with source morphs', function (): void {
    $context = app(HouseholdContext::class);

    $context->runInContext($this->household, function (): void {
        /** @var FinanceGateway $gateway */
        $gateway = app(FinanceGateway::class);

        $dto = new ExpenseData(
            accountId: $this->account->id,
            categoryId: $this->category->id,
            amount: 750000,
            occurredOn: CarbonImmutable::now(),
            note: 'Servis AC Ruang Tamu',
            sourceType: 'ast_service_record',
            sourceId: '01JABCDEF0123456789ABCDEF0',
        );

        $txId = $gateway->recordExpense($dto);

        expect($txId)->not->toBeNull();
        $tx = Transaction::find($txId);
        expect($tx)->not->toBeNull();
        expect($tx?->amount)->toBe(750000);
        expect($tx?->source_type)->toBe('ast_service_record');
        expect($tx?->source_id)->toBe('01JABCDEF0123456789ABCDEF0');

        // Upsert planned expense
        $planDto = new PlannedExpenseData(
            name: 'Jadwal Ganti Oli Motor',
            categoryId: $this->category->id,
            accountId: $this->account->id,
            amount: 150000,
            dueDate: CarbonImmutable::now()->addDays(14),
            sourceType: 'ast_maintenance_plan',
            sourceId: '01JXYZ123456789ABCDEF01234',
        );

        $plannedId = $gateway->upsertPlannedExpense($planDto);
        expect($plannedId)->not->toBeNull();

        $item = PlannedItem::find($plannedId);
        expect($item)->not->toBeNull();
        expect($item?->amount)->toBe(150000);

        // Cancel planned expenses
        $gateway->cancelPlannedExpenses('ast_maintenance_plan', '01JXYZ123456789ABCDEF01234');
        $item->refresh();
        expect($item->status)->toBe(PlannedStatus::Cancelled);
    });
});

it('returns null and does not mutate database when NullFinanceGateway is used or integration disabled', function (): void {
    $nullGateway = new NullFinanceGateway();

    $dto = new ExpenseData(
        accountId: $this->account->id,
        categoryId: $this->category->id,
        amount: 750000,
        occurredOn: CarbonImmutable::now(),
        note: 'Servis',
        sourceType: 'ast_service_record',
        sourceId: '01JABCDEF0123456789ABCDEF0',
    );

    $txId = $nullGateway->recordExpense($dto);
    expect($txId)->toBeNull();
    expect(Transaction::count())->toBe(0);

    $planDto = new PlannedExpenseData(
        name: 'Rencana',
        categoryId: $this->category->id,
        accountId: $this->account->id,
        amount: 100000,
        dueDate: CarbonImmutable::now(),
        sourceType: 'ast_plan',
        sourceId: '01JABC',
    );

    $planId = $nullGateway->upsertPlannedExpense($planDto);
    expect($planId)->toBeNull();
    expect(PlannedItem::count())->toBe(0);

    expect($nullGateway->accountOptions())->toBe([]);
    expect($nullGateway->expenseCategoryOptions())->toBe([]);
    expect($nullGateway->defaultCategoryId('asset_maintenance'))->toBeNull();
});
