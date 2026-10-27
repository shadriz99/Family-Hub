<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // 1. Tasks
        Schema::create('org_tasks', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('household_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('created_by')->constrained('users')->cascadeOnDelete();
            $table->ulid('series_id')->nullable()->index();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->foreignUlid('assignee_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable()->index();
            $table->time('due_time')->nullable();
            $table->string('priority', 20)->default('normal');
            $table->string('rrule')->nullable();
            $table->boolean('rotate_assignee')->default(false);
            $table->string('reminder_offset', 30)->nullable();
            $table->string('status', 20)->default('open')->index();
            $table->dateTime('completed_at')->nullable();
            $table->foreignUlid('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('expense_on_complete')->default(false);
            $table->bigInteger('expense_estimate')->nullable();
            $table->ulid('expense_category_id')->nullable();
            $table->ulid('expense_transaction_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['household_id', 'status', 'due_date']);
        });

        // 2. Task Items (Checklist)
        Schema::create('org_task_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('task_id')->constrained('org_tasks')->cascadeOnDelete();
            $table->string('text', 200);
            $table->boolean('is_done')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 3. Notes
        Schema::create('org_notes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('household_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('type', 20)->default('text');
            $table->string('title', 150)->nullable();
            $table->text('body')->nullable();
            $table->string('color', 20)->default('default');
            $table->text('labels')->nullable(); // json cast array
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_private')->default(false)->index();
            $table->dateTime('archived_at')->nullable();
            $table->foreignUlid('last_edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['household_id', 'is_pinned', 'archived_at']);
        });

        // 4. Note Items
        Schema::create('org_note_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('note_id')->constrained('org_notes')->cascadeOnDelete();
            $table->string('text', 300);
            $table->boolean('is_done')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 5. Shopping Lists
        Schema::create('org_shopping_lists', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('household_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('store', 80)->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->dateTime('completed_at')->nullable();
            $table->foreignUlid('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->bigInteger('actual_total')->nullable();
            $table->ulid('transaction_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['household_id', 'status']);
        });

        // 6. Shopping Items
        Schema::create('org_shopping_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('shopping_list_id')->constrained('org_shopping_lists')->cascadeOnDelete();
            $table->string('name', 100);
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit', 20)->nullable();
            $table->bigInteger('estimated_price')->nullable();
            $table->string('group', 30)->default('other');
            $table->string('note', 150)->nullable();
            $table->boolean('is_checked')->default(false)->index();
            $table->foreignUlid('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('checked_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['shopping_list_id', 'is_checked']);
        });

        // 7. Events
        Schema::create('org_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('household_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('kind', 20)->default('event');
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->string('location', 200)->nullable();
            $table->boolean('all_day')->default(false);
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->date('start_date')->index();
            $table->date('end_date')->nullable();
            $table->string('rrule')->nullable();
            $table->foreignUlid('for_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('color', 7)->nullable();
            $table->text('reminder_offsets')->nullable(); // json cast array
            $table->smallInteger('birth_year')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['household_id', 'start_date']);
        });

        // 8. Event Exceptions
        Schema::create('org_event_exceptions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('event_id')->constrained('org_events')->cascadeOnDelete();
            $table->date('original_date');
            $table->boolean('is_cancelled')->default(false);
            $table->text('override')->nullable(); // json cast array
            $table->timestamps();

            $table->unique(['event_id', 'original_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('org_event_exceptions');
        Schema::dropIfExists('org_events');
        Schema::dropIfExists('org_shopping_items');
        Schema::dropIfExists('org_shopping_lists');
        Schema::dropIfExists('org_note_items');
        Schema::dropIfExists('org_notes');
        Schema::dropIfExists('org_task_items');
        Schema::dropIfExists('org_tasks');
    }
};
