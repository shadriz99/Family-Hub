<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Core\Actions\CreateHousehold;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use App\Modules\Finance\Database\Seeders\FinanceDemoSeeder;
use App\Modules\Finance\Enums\AllocationCategory;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\AllocationPlan;
use App\Modules\Finance\Models\Budget;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\Loan;
use App\Modules\Finance\Models\PlannedItem;
use App\Modules\Finance\Models\RecurringBill;
use App\Modules\Finance\Models\Transaction;
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
});

it('seeds default Indonesian categories when household is created', function (): void {
    $context = app(HouseholdContext::class);

    $context->runInContext($this->household, function (): void {
        $parentExpense = Category::whereNull('parent_id')
            ->where('type', CategoryType::Expense)
            ->whereNotIn('system_key', ['balance_adjustment', 'loan_disbursement_out'])
            ->get();

        // Exactly 5 main parent expense categories per user requirement
        expect($parentExpense->count())->toBe(5);

        // Verify key parent categories exist
        $pokok = Category::whereNull('parent_id')->where('name', 'Kebutuhan Pokok')->first();
        expect($pokok)->not->toBeNull()
            ->and($pokok->type)->toBe(CategoryType::Expense)
            ->and($pokok->default_allocation)->toBe(AllocationCategory::Living);

        $lifestyle = Category::whereNull('parent_id')->where('name', 'Lifestyle')->first();
        expect($lifestyle)->not->toBeNull()
            ->and($lifestyle->type)->toBe(CategoryType::Expense)
            ->and($lifestyle->default_allocation)->toBe(AllocationCategory::Tertiary);

        $tabungan = Category::whereNull('parent_id')->where('name', 'Tabungan')->first();
        expect($tabungan)->not->toBeNull()
            ->and($tabungan->type)->toBe(CategoryType::Expense)
            ->and($tabungan->default_allocation)->toBe(AllocationCategory::Savings);

        $donasi = Category::whereNull('parent_id')->where('name', 'Donasi')->first();
        expect($donasi)->not->toBeNull()
            ->and($donasi->type)->toBe(CategoryType::Expense)
            ->and($donasi->default_allocation)->toBe(AllocationCategory::Social);

        $hutang = Category::whereNull('parent_id')->where('name', 'Hutang')->first();
        expect($hutang)->not->toBeNull()
            ->and($hutang->type)->toBe(CategoryType::Expense)
            ->and($hutang->default_allocation)->toBe(AllocationCategory::Debt);

        // Verify sub categories have correct allocation overrides
        $makanLuar = Category::where('parent_id', $lifestyle->getKey())->where('name', 'Makan di Luar')->first();
        expect($makanLuar)->not->toBeNull()
            ->and($makanLuar->default_allocation)->toBe(AllocationCategory::Tertiary);

        $dapur = Category::where('parent_id', $pokok->getKey())->where('name', 'Belanja Dapur')->first();
        expect($dapur)->not->toBeNull()
            ->and($dapur->default_allocation)->toBe(AllocationCategory::Living);

        // Verify system categories
        $cicilan = Category::where('system_key', 'loan_installment')->first();
        expect($cicilan)->not->toBeNull()
            ->and($cicilan->name)->toBe('Hutang')
            ->and($cicilan->default_allocation)->toBe(AllocationCategory::Debt);

        $shopping = Category::where('system_key', 'shopping')->first();
        expect($shopping)->not->toBeNull()
            ->and($shopping->name)->toBe('Belanja Bulanan');

        $maintenance = Category::where('system_key', 'asset_maintenance')->first();
        expect($maintenance)->not->toBeNull()
            ->and($maintenance->name)->toBe('Rumah & Perawatan');

        $bankFee = Category::where('system_key', 'bank_fee')->first();
        expect($bankFee)->not->toBeNull()
            ->and($bankFee->name)->toBe('Biaya Admin Bank');

        // Income categories
        $gaji = Category::whereNull('parent_id')->where('name', 'Gaji')->first();
        expect($gaji)->not->toBeNull()
            ->and($gaji->type)->toBe(CategoryType::Income);

        $piutang = Category::where('system_key', 'loan_receipt')->first();
        expect($piutang)->not->toBeNull()
            ->and($piutang->name)->toBe('Penerimaan Piutang');

        // Balance adjustment system categories for both income and expense
        $adjIncome = Category::where('system_key', 'balance_adjustment')->where('type', CategoryType::Income)->first();
        $adjExpense = Category::where('system_key', 'balance_adjustment')->where('type', CategoryType::Expense)->first();

        expect($adjIncome)->not->toBeNull()
            ->and($adjExpense)->not->toBeNull();
    });
});

it('populates realistic demo data with FinanceDemoSeeder', function (): void {
    $seeder = new FinanceDemoSeeder();
    $seeder->run($this->household, $this->user);

    $context = app(HouseholdContext::class);

    $context->runInContext($this->household, function (): void {
        expect(Account::count())->toBe(6)
            ->and(AllocationPlan::count())->toBe(1)
            ->and(Budget::count())->toBe(5)
            ->and(RecurringBill::count())->toBe(4)
            ->and(Loan::count())->toBe(1)
            ->and(PlannedItem::count())->toBe(1)
            ->and(Transaction::count())->toBe(3);

        $loan = Loan::first();
        expect($loan->name)->toBe('KPR BTN Graha Indah')
            ->and($loan->totalDifference())->toBe(227000000); // 477m - 250m

        $tx = Transaction::where('type', 'transfer')->first();
        expect($tx->allocation)->toBe(AllocationCategory::Savings)
            ->and($tx->allocation_detail)->toBe('Dana Darurat');
    });
});
