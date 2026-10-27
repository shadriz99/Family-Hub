<?php

declare(strict_types=1);

namespace Tests\Feature\Organizer;

use App\Core\Actions\CreateHousehold;
use App\Core\Models\Household;
use App\Core\Models\HouseholdMember;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Category;
use App\Modules\Organizer\Enums\ShoppingGroup;
use App\Modules\Organizer\Enums\ShoppingListStatus;
use App\Modules\Organizer\Livewire\Shopping\Show as ShoppingShow;
use App\Modules\Organizer\Models\ShoppingList;
use App\Modules\Organizer\Services\ShoppingItemParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class ShoppingListAndExpenseTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $partner;
    private Household $household;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password123'),
        ]);

        $this->household = app(CreateHousehold::class)->handle(
            $this->user,
            'Keluarga Budi',
            1,
            'Asia/Jakarta'
        );
        $this->household->onboarded_at = now();
        $this->household->save();

        $this->partner = User::create([
            'name' => 'Ani Santoso',
            'email' => 'ani@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password123'),
        ]);

        HouseholdMember::create([
            'household_id' => $this->household->id,
            'user_id' => $this->partner->id,
            'role' => 'partner',
            'color' => '#f43f5e',
            'joined_at' => now(),
            'slot' => 2,
        ]);

        app(HouseholdContext::class)->use($this->household);
    }

    public function test_shopping_item_parser_extracts_name_qty_unit_and_group(): void
    {
        $parser = new ShoppingItemParser();

        $res1 = $parser->parse('telur 2 kg');
        $this->assertSame('telur', $res1['name']);
        $this->assertSame(2.0, $res1['quantity']);
        $this->assertSame('kg', $res1['unit']);
        $this->assertSame(ShoppingGroup::MeatSeafood, $res1['group']);

        $res2 = $parser->parse('1.5 liter minyak goreng');
        $this->assertSame('minyak goreng', $res2['name']);
        $this->assertSame(1.5, $res2['quantity']);
        $this->assertSame('liter', $res2['unit']);
        $this->assertSame(ShoppingGroup::Groceries, $res2['group']);

        $res3 = $parser->parse('sabun 3');
        $this->assertSame('sabun', $res3['name']);
        $this->assertSame(3.0, $res3['quantity']);
        $this->assertSame('pcs', $res3['unit']);
        $this->assertSame(ShoppingGroup::Cleaning, $res3['group']);
    }

    public function test_collaborative_shopping_toggle_and_completion_with_expense(): void
    {
        $this->actingAs($this->user);

        $account = Account::create([
            'household_id' => $this->household->id,
            'name' => 'Dompet',
            'type' => 'cash',
            'opening_balance' => 500000,
            'opening_date' => '2026-01-01',
        ]);

        $category = Category::query()->where('household_id', $this->household->id)->first();

        // Create list
        $list = app(HouseholdContext::class)->runInContext($this->household, function () {
            return ShoppingList::create([
                'household_id' => $this->household->id,
                'created_by' => $this->user->id,
                'name' => 'Belanja Superindo',
                'status' => ShoppingListStatus::Active,
            ]);
        });

        // Add items via component
        Livewire::test(ShoppingShow::class, ['id' => $list->id])
            ->set('quickInput', 'bayam 2 ikat')
            ->set('quickEstimatedPrice', 5000)
            ->call('addItem')
            ->set('quickInput', 'beras 5 kg')
            ->set('quickEstimatedPrice', 75000)
            ->call('addItem');

        $this->assertCount(2, $list->fresh()->items);

        // Partner checks one item
        $bayamItem = $list->fresh()->items->firstWhere('name', 'bayam');
        $this->actingAs($this->partner);
        Livewire::test(ShoppingShow::class, ['id' => $list->id])
            ->call('toggleItem', $bayamItem->id);

        $this->assertTrue($bayamItem->fresh()->is_checked);
        $this->assertSame($this->partner->id, $bayamItem->fresh()->checked_by);

        // Complete shopping
        $this->actingAs($this->user);
        Livewire::test(ShoppingShow::class, ['id' => $list->id])
            ->set('actualTotal', 85000)
            ->set('expenseAccountId', $account->id)
            ->set('expenseCategoryId', $category->id)
            ->set('uncheckedAction', 'move_to_new')
            ->call('completeShopping');

        $list->refresh();
        $this->assertSame(ShoppingListStatus::Completed, $list->status);
        $this->assertSame(85000, $list->actual_total);
        $this->assertNotNull($list->transaction_id);

        // Unchecked beras was moved to new list
        $this->assertDatabaseHas('org_shopping_lists', [
            'name' => 'Sisa Belanja: Belanja Superindo',
            'status' => 'active',
        ]);

        // Transaction recorded in finance
        $this->assertDatabaseHas('fin_transactions', [
            'id' => $list->transaction_id,
            'amount' => 85000,
            'source_type' => 'org_shopping_list',
        ]);
    }
}
