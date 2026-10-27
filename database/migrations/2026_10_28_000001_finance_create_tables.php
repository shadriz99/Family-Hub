<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('fin_accounts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('household_id')->constrained('households')->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('type', 20);
            $table->bigInteger('opening_balance')->default(0);
            $table->date('opening_date');
            $table->bigInteger('credit_limit')->nullable();
            $table->string('color', 7)->default('#10b981');
            $table->string('icon', 40)->default('wallet');
            $table->boolean('include_in_total')->default(true);
            $table->integer('sort_order')->default(0);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['household_id', 'archived_at'], 'fin_acc_household_archived_idx');
            $table->index(['household_id', 'sort_order'], 'fin_acc_household_sort_idx');
        });

        Schema::create('fin_categories', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('household_id')->constrained('households')->cascadeOnDelete();
            $table->ulid('parent_id')->nullable();
            $table->string('name', 50);
            $table->string('type', 20);
            $table->string('icon', 40)->nullable();
            $table->string('color', 7)->nullable();
            $table->string('system_key', 40)->nullable();
            $table->string('default_allocation', 30)->nullable();
            $table->integer('sort_order')->default(0);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('fin_categories')->nullOnDelete();
            $table->index(['household_id', 'parent_id'], 'fin_cat_household_parent_idx');
            $table->index(['household_id', 'type'], 'fin_cat_household_type_idx');
            $table->unique(['household_id', 'system_key', 'type'], 'fin_cat_system_key_unique');
        });

        Schema::create('fin_recurring_bills', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('household_id')->constrained('households')->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('direction', 20);
            $table->foreignUlid('category_id')->constrained('fin_categories')->restrictOnDelete();
            $table->foreignUlid('account_id')->constrained('fin_accounts')->restrictOnDelete();
            $table->bigInteger('amount');
            $table->boolean('is_variable_amount')->default(false);
            $table->string('rrule', 255);
            $table->date('dtstart');
            $table->date('until')->nullable();
            $table->unsignedTinyInteger('remind_days_before')->default(3);
            $table->boolean('is_active')->default(true);
            $table->date('generated_until')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['household_id', 'is_active'], 'fin_rb_household_active_idx');
        });

        Schema::create('fin_loans', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('household_id')->constrained('households')->cascadeOnDelete();
            $table->string('direction', 20);
            $table->string('name', 80);
            $table->string('counterparty', 100);
            $table->bigInteger('principal');
            $table->unsignedSmallInteger('installment_count');
            $table->bigInteger('installment_amount');
            $table->bigInteger('total_payable');
            $table->string('frequency', 20)->default('monthly');
            $table->date('first_due_date');
            $table->foreignUlid('account_id')->constrained('fin_accounts')->restrictOnDelete();
            $table->foreignUlid('category_id')->constrained('fin_categories')->restrictOnDelete();
            $table->ulid('disbursement_transaction_id')->nullable();
            $table->string('interest_note', 100)->nullable();
            $table->unsignedTinyInteger('remind_days_before')->default(3);
            $table->string('status', 20)->default('active');
            $table->date('paid_off_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['household_id', 'status'], 'fin_loans_household_status_idx');
        });

        Schema::create('fin_planned_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('household_id')->constrained('households')->cascadeOnDelete();
            $table->string('direction', 20);
            $table->string('name', 100);
            $table->foreignUlid('category_id')->constrained('fin_categories')->restrictOnDelete();
            $table->foreignUlid('account_id')->nullable()->constrained('fin_accounts')->nullOnDelete();
            $table->bigInteger('amount');
            $table->bigInteger('paid_amount')->default(0);
            $table->date('due_date');
            $table->char('period', 10);
            $table->string('status', 20)->default('planned');
            $table->string('status_reason', 150)->nullable();
            $table->string('source_type', 30)->default('manual');
            $table->ulid('source_id')->nullable();
            $table->integer('sequence')->nullable();
            $table->boolean('paid_before_tracking')->default(false);
            $table->dateTime('settled_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['household_id', 'due_date'], 'fin_pi_due_date_idx');
            $table->index(['household_id', 'period'], 'fin_pi_period_idx');
            $table->index(['household_id', 'status'], 'fin_pi_status_idx');
            $table->unique(['household_id', 'source_type', 'source_id', 'sequence'], 'fin_pi_loan_seq_unique');
            $table->unique(['household_id', 'source_type', 'source_id', 'due_date'], 'fin_pi_rec_bill_unique');
        });

        Schema::create('fin_transactions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('household_id')->constrained('households')->cascadeOnDelete();
            $table->string('type', 20);
            $table->foreignUlid('account_id')->constrained('fin_accounts')->restrictOnDelete();
            $table->foreignUlid('to_account_id')->nullable()->constrained('fin_accounts')->restrictOnDelete();
            $table->foreignUlid('category_id')->nullable()->constrained('fin_categories')->nullOnDelete();
            $table->bigInteger('amount');
            $table->date('occurred_on');
            $table->string('note', 255)->nullable();
            $table->string('payee', 100)->nullable();
            $table->foreignUlid('planned_item_id')->nullable()->constrained('fin_planned_items')->nullOnDelete();
            $table->foreignUlid('related_transaction_id')->nullable()->constrained('fin_transactions')->nullOnDelete();
            $table->string('source_type', 60)->nullable();
            $table->ulid('source_id')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('allocation', 30)->nullable();
            $table->string('allocation_detail', 100)->nullable();
            $table->foreignUlid('allocation_reversal_of_id')->nullable()->constrained('fin_transactions')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['household_id', 'occurred_on'], 'fin_tx_occurred_on_idx');
            $table->index(['household_id', 'account_id'], 'fin_tx_account_idx');
            $table->index(['household_id', 'category_id'], 'fin_tx_category_idx');
            $table->index(['household_id', 'planned_item_id'], 'fin_tx_planned_item_idx');
        });

        Schema::create('fin_budgets', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('household_id')->constrained('households')->cascadeOnDelete();
            $table->foreignUlid('category_id')->constrained('fin_categories')->cascadeOnDelete();
            $table->char('period', 10);
            $table->bigInteger('amount')->default(0);
            $table->dateTime('alert_80_sent_at')->nullable();
            $table->dateTime('alert_100_sent_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['household_id', 'category_id', 'period'], 'fin_budgets_unique');
        });

        Schema::create('fin_allocation_plans', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('household_id')->constrained('households')->cascadeOnDelete();
            $table->char('period', 10);
            $table->bigInteger('income_base')->default(0);
            $table->unsignedInteger('living_bps')->default(0);
            $table->unsignedInteger('tertiary_bps')->default(0);
            $table->unsignedInteger('debt_bps')->default(0);
            $table->unsignedInteger('savings_bps')->default(0);
            $table->unsignedInteger('social_bps')->default(0);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['household_id', 'period'], 'fin_alloc_plans_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_allocation_plans');
        Schema::dropIfExists('fin_budgets');
        Schema::dropIfExists('fin_transactions');
        Schema::dropIfExists('fin_planned_items');
        Schema::dropIfExists('fin_loans');
        Schema::dropIfExists('fin_recurring_bills');
        Schema::dropIfExists('fin_categories');
        Schema::dropIfExists('fin_accounts');
    }
};
