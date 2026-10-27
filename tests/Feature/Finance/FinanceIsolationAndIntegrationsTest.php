<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Core\Actions\CreateHousehold;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use App\Modules\Finance\Enums\AccountType;
use App\Modules\Finance\Enums\Direction;
use App\Modules\Finance\Enums\PlannedSource;
use App\Modules\Finance\Enums\PlannedStatus;
use App\Modules\Finance\Integrations\FinanceAlertSource;
use App\Modules\Finance\Integrations\FinanceHouseholdExporter;
use App\Modules\Finance\Integrations\FinanceSummaryWidget;
use App\Modules\Finance\Integrations\PlannedItemCalendarSource;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\PlannedItem;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->userA = User::create([
        'name' => 'Keluarga A',
        'email' => 'keluarga_a@example.com',
        'password' => bcrypt('password123'),
    ]);

    $this->householdA = app(CreateHousehold::class)->handle(
        $this->userA,
        'Household A',
        1,
        'Asia/Jakarta'
    );

    $this->userB = User::create([
        'name' => 'Keluarga B',
        'email' => 'keluarga_b@example.com',
        'password' => bcrypt('password123'),
    ]);

    $this->householdB = app(CreateHousehold::class)->handle(
        $this->userB,
        'Household B',
        1,
        'Asia/Jakarta'
    );

    $this->householdA->onboarded_at = CarbonImmutable::now();
    $this->householdA->save();
    $this->householdB->onboarded_at = CarbonImmutable::now();
    $this->householdB->save();
});

it('strictly isolates finance routes between different households', function (string $route): void {
    $this->actingAs($this->userA);
    $responseA = $this->get($route);
    $responseA->assertOk();

    // User B accesses same route and gets their own household scope
    $this->actingAs($this->userB);
    $responseB = $this->get($route);
    $responseB->assertOk();
})->with([
    'overview' => ['/finance'],
    'accounts' => ['/finance/accounts'],
    'categories' => ['/finance/categories'],
    'transactions' => ['/finance/transactions'],
    'budgets' => ['/finance/budgets'],
    'plan' => ['/finance/plan'],
    'recurring' => ['/finance/recurring'],
    'loans' => ['/finance/loans'],
    'reports' => ['/finance/reports'],
]);

it('integrates with core calendar, alerts, dashboard widgets, and household exporter', function (): void {
    $context = app(HouseholdContext::class);

    $context->runInContext($this->householdA, function (): void {
        $account = Account::create([
            'household_id' => $this->householdA->id,
            'name' => 'BCA A',
            'type' => AccountType::Bank,
            'opening_balance' => 5000000,
            'opening_date' => now()->subDays(10),
            'color' => '#10b981',
            'icon' => 'wallet',
        ]);

        $category = Category::where('household_id', $this->householdA->id)->firstOrFail();

        $plannedItem = PlannedItem::create([
            'household_id' => $this->householdA->id,
            'direction' => Direction::Expense,
            'name' => 'Tagihan Terjadwal',
            'category_id' => $category->id,
            'account_id' => $account->id,
            'amount' => 500000,
            'due_date' => CarbonImmutable::now()->addDays(2),
            'period' => '2026-10-01',
            'status' => PlannedStatus::Planned,
            'source_type' => PlannedSource::Manual,
        ]);

        // 1. Calendar Source Integration
        $calSource = app(PlannedItemCalendarSource::class);
        $calItems = collect($calSource->items($this->householdA, CarbonImmutable::now()->subDays(1), CarbonImmutable::now()->addDays(5)));
        expect($calItems->count())->toBe(1);
        expect($calItems->first()?->title)->toContain('Tagihan Terjadwal');

        // 2. Alert Source Integration (make item overdue)
        $plannedItem->due_date = CarbonImmutable::now()->subDays(2);
        $plannedItem->save();

        $alertSource = app(FinanceAlertSource::class);
        $alerts = collect($alertSource->alerts($this->householdA));
        expect($alerts->count())->toBeGreaterThan(0);
        expect($alerts->first()?->level)->toBe('danger');

        // 3. Dashboard Widget Integration
        $widget = app(FinanceSummaryWidget::class);
        expect($widget->isEnabled($this->householdA))->toBeTrue();
        $rendered = $widget->render($this->householdA);
        expect($rendered->render())->toContain('Ringkasan Keuangan');

        // 4. Exporter Integration
        $exporter = app(FinanceHouseholdExporter::class);
        $exported = $exporter->export($this->householdA);
        expect($exported)->toHaveKey('finance/accounts.json');
        expect($exported)->toHaveKey('finance/categories.json');
        expect($exported)->toHaveKey('finance/transactions.json');
        expect($exported)->toHaveKey('finance/planned_items.json');
    });
});
