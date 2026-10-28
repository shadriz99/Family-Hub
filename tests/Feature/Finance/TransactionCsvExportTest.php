<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Core\Actions\CreateHousehold;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use App\Modules\Finance\Enums\AccountType;
use App\Modules\Finance\Enums\AllocationCategory;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TransactionCsvExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private $household;
    private Account $account;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Finance Exporter',
            'email' => 'exporter@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->household = app(CreateHousehold::class)->handle(
            $this->user,
            'Keluarga Csv',
            1,
            'Asia/Jakarta'
        );
        $this->household->forceFill(['onboarded_at' => now()])->save();
        app(HouseholdContext::class)->use($this->household);

        $this->account = Account::create([
            'household_id' => $this->household->id,
            'name' => 'BCA Utama',
            'type' => AccountType::Bank,
            'opening_balance' => 5000000,
            'opening_date' => now()->subMonth(),
            'color' => '#3b82f6',
            'icon' => 'bank',
        ]);

        $this->category = Category::create([
            'household_id' => $this->household->id,
            'name' => 'Belanja Bulanan',
            'type' => CategoryType::Expense,
            'default_allocation' => AllocationCategory::Living,
            'color' => '#10b981',
            'icon' => 'cart',
        ]);
    }

    public function test_guest_cannot_export_transactions(): void
    {
        $response = $this->get(route('finance.export.transactions'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_export_csv_with_proper_headers(): void
    {
        Transaction::create([
            'household_id' => $this->household->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => TransactionType::Expense,
            'amount' => 150000,
            'occurred_on' => now()->toDateString(),
            'payee' => 'Supermarket ABC',
            'note' => 'Beli sembako',
            'allocation' => AllocationCategory::Living,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('finance.export.transactions'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('transaksi-', (string) $response->headers->get('content-disposition'));

        $content = $response->streamedContent();
        // Check UTF-8 BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('Tanggal,Tipe,"Akun Sumber"', $content);
        $this->assertStringContainsString('Supermarket ABC', $content);
        $this->assertStringContainsString('Beli sembako', $content);
        $this->assertStringContainsString('150000', $content);
    }

    public function test_export_respects_tenant_isolation(): void
    {
        $context = app(HouseholdContext::class);

        // Other household transaction
        $otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other@example.com',
            'password' => bcrypt('password123'),
        ]);
        $otherHousehold = app(CreateHousehold::class)->handle(
            $otherUser,
            'Keluarga Lain',
            1,
            'Asia/Jakarta'
        );

        $context->runInContext($otherHousehold, function () use ($otherHousehold, $otherUser): void {
            $otherAccount = Account::create([
                'household_id' => $otherHousehold->id,
                'name' => 'Mandiri Lain',
                'type' => AccountType::Bank,
                'opening_balance' => 1000000,
                'opening_date' => now()->subMonth(),
                'color' => '#f59e0b',
                'icon' => 'bank',
            ]);
            Transaction::create([
                'household_id' => $otherHousehold->id,
                'account_id' => $otherAccount->id,
                'type' => TransactionType::Expense,
                'amount' => 999999,
                'occurred_on' => now()->toDateString(),
                'payee' => 'Rahasia Keluarga Lain',
                'note' => 'Jangan sampai bocor',
                'created_by' => $otherUser->id,
            ]);
        });

        // Ensure our household context is active
        $context->use($this->household);

        // Own transaction
        Transaction::create([
            'household_id' => $this->household->id,
            'account_id' => $this->account->id,
            'type' => TransactionType::Income,
            'amount' => 500000,
            'occurred_on' => now()->toDateString(),
            'payee' => 'Gaji Pokok',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('finance.export.transactions'));
        $response->assertOk();

        $content = $response->streamedContent();
        $this->assertStringContainsString('Gaji Pokok', $content);
        $this->assertStringNotContainsString('Rahasia Keluarga Lain', $content);
        $this->assertStringNotContainsString('999999', $content);
    }

    public function test_export_filters_by_type_and_date_range(): void
    {
        Transaction::create([
            'household_id' => $this->household->id,
            'account_id' => $this->account->id,
            'type' => TransactionType::Expense,
            'amount' => 100000,
            'occurred_on' => '2026-10-01',
            'payee' => 'Toko Buku',
            'created_by' => $this->user->id,
        ]);

        Transaction::create([
            'household_id' => $this->household->id,
            'account_id' => $this->account->id,
            'type' => TransactionType::Income,
            'amount' => 2000000,
            'occurred_on' => '2026-10-15',
            'payee' => 'Bonus Kinerja',
            'created_by' => $this->user->id,
        ]);

        // Filter type expense
        $responseExpense = $this->actingAs($this->user)->get(route('finance.export.transactions', ['type' => 'expense']));
        $contentExpense = $responseExpense->streamedContent();
        $this->assertStringContainsString('Toko Buku', $contentExpense);
        $this->assertStringNotContainsString('Bonus Kinerja', $contentExpense);

        // Filter date range
        $responseDate = $this->actingAs($this->user)->get(route('finance.export.transactions', [
            'from' => '2026-10-10',
            'to' => '2026-10-20',
        ]));
        $contentDate = $responseDate->streamedContent();
        $this->assertStringContainsString('Bonus Kinerja', $contentDate);
        $this->assertStringNotContainsString('Toko Buku', $contentDate);
    }
}
