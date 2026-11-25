<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_transaction_splits', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('household_id')->constrained('households')->cascadeOnDelete();
            $table->foreignUlid('transaction_id')->constrained('fin_transactions')->cascadeOnDelete();
            $table->foreignUlid('category_id')->constrained('fin_categories')->cascadeOnDelete();
            $table->bigInteger('amount');
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index(['household_id', 'transaction_id'], 'fin_tx_splits_tx_idx');
            $table->index(['household_id', 'category_id'], 'fin_tx_splits_cat_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_transaction_splits');
    }
};
