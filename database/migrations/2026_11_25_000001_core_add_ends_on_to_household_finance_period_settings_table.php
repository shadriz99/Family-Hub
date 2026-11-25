<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('household_finance_period_settings', function (Blueprint $table): void {
            $table->date('ends_on')->nullable()->after('effective_on');
        });
    }

    public function down(): void
    {
        Schema::table('household_finance_period_settings', function (Blueprint $table): void {
            $table->dropColumn('ends_on');
        });
    }
};
