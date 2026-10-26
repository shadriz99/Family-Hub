<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('households', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name', 100);
            $table->string('timezone')->default('Asia/Jakarta');
            $table->unsignedTinyInteger('finance_period_start_day')->default(1);
            $table->boolean('finance_integration_enabled')->default(true);
            $table->foreignUlid('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        // Inline CHECK works on SQLite and MariaDB; SQLite cannot add it with ALTER TABLE.
        DB::statement("CREATE TABLE household_members (
            id CHAR(26) NOT NULL PRIMARY KEY,
            household_id CHAR(26) NOT NULL,
            user_id CHAR(26) NOT NULL UNIQUE,
            role VARCHAR(16) NOT NULL CHECK (role IN ('owner', 'partner')),
            color VARCHAR(7) NOT NULL,
            joined_at TIMESTAMP NOT NULL,
            slot SMALLINT NOT NULL CHECK (slot IN (1, 2)),
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            UNIQUE (household_id, slot),
            FOREIGN KEY (household_id) REFERENCES households(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )");

        Schema::create('household_finance_period_settings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('household_id')->constrained();
            $table->unsignedTinyInteger('start_day');
            $table->date('effective_on');
            $table->foreignUlid('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->unique(['household_id', 'effective_on'], 'household_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('household_finance_period_settings');
        Schema::dropIfExists('household_members');
        Schema::dropIfExists('households');
    }
};
