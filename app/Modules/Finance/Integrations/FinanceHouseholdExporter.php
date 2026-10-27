<?php

declare(strict_types=1);

namespace App\Modules\Finance\Integrations;

use App\Core\Contracts\HouseholdExporter;
use App\Core\Models\Household;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\AllocationPlan;
use App\Modules\Finance\Models\Budget;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\Loan;
use App\Modules\Finance\Models\PlannedItem;
use App\Modules\Finance\Models\RecurringBill;
use App\Modules\Finance\Models\Transaction;

final class FinanceHouseholdExporter implements HouseholdExporter
{
    public function module(): string
    {
        return 'finance';
    }

    /**
     * @return array<string, mixed>
     */
    public function export(Household $household): array
    {
        $accounts = Account::query()
            ->where('household_id', $household->id)
            ->get()
            ->map(fn (Account $a) => [
                'id' => $a->id,
                'name' => $a->name,
                'type' => $a->type->value,
                'opening_balance' => $a->opening_balance,
                'opening_date' => $a->opening_date->toDateString(),
                'credit_limit' => $a->credit_limit,
                'color' => $a->color,
                'icon' => $a->icon,
                'include_in_total' => $a->include_in_total,
                'archived_at' => $a->archived_at?->toIso8601String(),
            ]);

        $categories = Category::query()
            ->where('household_id', $household->id)
            ->get()
            ->map(fn (Category $c) => [
                'id' => $c->id,
                'parent_id' => $c->parent_id,
                'name' => $c->name,
                'type' => $c->type->value,
                'system_key' => $c->system_key,
                'default_allocation' => $c->default_allocation?->value,
                'sort_order' => $c->sort_order,
                'archived_at' => $c->archived_at?->toIso8601String(),
            ]);

        $transactions = Transaction::query()
            ->where('household_id', $household->id)
            ->get()
            ->map(fn (Transaction $t) => [
                'id' => $t->id,
                'type' => $t->type->value,
                'account_id' => $t->account_id,
                'to_account_id' => $t->to_account_id,
                'category_id' => $t->category_id,
                'amount' => $t->amount,
                'occurred_on' => $t->occurred_on->toDateString(),
                'note' => $t->note,
                'payee' => $t->payee,
                'planned_item_id' => $t->planned_item_id,
                'allocation' => $t->allocation?->value,
                'allocation_detail' => $t->allocation_detail,
                'created_at' => $t->created_at?->toIso8601String(),
            ]);

        $budgets = Budget::query()
            ->where('household_id', $household->id)
            ->get()
            ->map(fn (Budget $b) => [
                'id' => $b->id,
                'category_id' => $b->category_id,
                'period' => $b->period,
                'amount' => $b->amount,
            ]);

        $recurringBills = RecurringBill::query()
            ->where('household_id', $household->id)
            ->get()
            ->map(fn (RecurringBill $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'direction' => $r->direction->value,
                'category_id' => $r->category_id,
                'account_id' => $r->account_id,
                'amount' => $r->amount,
                'is_variable_amount' => $r->is_variable_amount,
                'rrule' => $r->rrule,
                'dtstart' => $r->dtstart->toDateString(),
                'until' => $r->until?->toDateString(),
                'is_active' => $r->is_active,
            ]);

        $loans = Loan::query()
            ->where('household_id', $household->id)
            ->get()
            ->map(fn (Loan $l) => [
                'id' => $l->id,
                'direction' => $l->direction->value,
                'name' => $l->name,
                'counterparty' => $l->counterparty,
                'principal' => $l->principal,
                'installment_count' => $l->installment_count,
                'installment_amount' => $l->installment_amount,
                'total_payable' => $l->total_payable,
                'frequency' => $l->frequency->value,
                'first_due_date' => $l->first_due_date->toDateString(),
                'account_id' => $l->account_id,
                'category_id' => $l->category_id,
                'status' => $l->status->value,
                'paid_off_at' => $l->paid_off_at?->toDateString(),
            ]);

        $plannedItems = PlannedItem::query()
            ->where('household_id', $household->id)
            ->get()
            ->map(fn (PlannedItem $p) => [
                'id' => $p->id,
                'direction' => $p->direction->value,
                'name' => $p->name,
                'category_id' => $p->category_id,
                'account_id' => $p->account_id,
                'amount' => $p->amount,
                'paid_amount' => $p->paid_amount,
                'due_date' => $p->due_date->toDateString(),
                'period' => $p->period,
                'status' => $p->status->value,
                'source_type' => $p->source_type->value,
                'source_id' => $p->source_id,
                'sequence' => $p->sequence,
            ]);

        $allocationPlans = AllocationPlan::query()
            ->where('household_id', $household->id)
            ->get()
            ->map(fn (AllocationPlan $ap) => [
                'id' => $ap->id,
                'period' => $ap->period,
                'income_base' => $ap->income_base,
                'living_bps' => $ap->living_bps,
                'tertiary_bps' => $ap->tertiary_bps,
                'debt_bps' => $ap->debt_bps,
                'savings_bps' => $ap->savings_bps,
                'social_bps' => $ap->social_bps,
            ]);

        return [
            'finance/accounts.json' => $accounts->all(),
            'finance/categories.json' => $categories->all(),
            'finance/transactions.json' => $transactions->all(),
            'finance/budgets.json' => $budgets->all(),
            'finance/recurring_bills.json' => $recurringBills->all(),
            'finance/loans.json' => $loans->all(),
            'finance/planned_items.json' => $plannedItems->all(),
            'finance/allocation_plans.json' => $allocationPlans->all(),
        ];
    }
}
