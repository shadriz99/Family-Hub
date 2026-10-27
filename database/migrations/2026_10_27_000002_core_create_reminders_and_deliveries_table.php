<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('household_id')->constrained('households')->cascadeOnDelete();
            $table->string('remindable_type');
            $table->string('remindable_id', 36);
            $table->string('key', 50);
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('url', 2048);
            $table->dateTime('remind_at');
            $table->foreignUlid('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 16)->default('scheduled');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['remindable_type', 'remindable_id']);
            $table->unique(['remindable_type', 'remindable_id', 'key'], 'reminders_remindable_key_unique');
            $table->index(['household_id', 'status', 'remind_at']);
        });

        Schema::create('reminder_deliveries', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('household_id')->constrained('households')->cascadeOnDelete();
            $table->foreignUlid('reminder_id')->constrained('reminders')->cascadeOnDelete();
            $table->foreignUlid('recipient_user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('remind_at');
            $table->string('status', 16)->default('pending');
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->dateTime('next_attempt_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->string('last_error_code', 50)->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['reminder_id', 'recipient_user_id'], 'reminder_deliveries_recipient_unique');
            $table->index(['status', 'remind_at']);
            $table->index(['household_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_deliveries');
        Schema::dropIfExists('reminders');
    }
};
