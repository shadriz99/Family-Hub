<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('fin_telegram_links', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('household_id')->constrained('households')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('telegram_chat_id', 50)->nullable()->unique();
            $table->string('telegram_username', 100)->nullable();
            $table->string('link_code', 32)->nullable()->index();
            $table->timestamp('link_code_expires_at')->nullable();
            $table->text('pending_draft')->nullable();
            $table->timestamps();

            $table->unique(['household_id', 'user_id'], 'fin_tg_household_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_telegram_links');
    }
};
