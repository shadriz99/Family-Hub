<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('fin_budgets', function (Blueprint $table): void {
            $table->unsignedSmallInteger('percentage')->default(0)->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('fin_budgets', function (Blueprint $table): void {
            $table->dropColumn('percentage');
        });
    }
};
