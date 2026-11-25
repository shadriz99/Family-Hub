<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('two_factor_email_enabled')->default(false)->after('remember_token');
            $table->string('two_factor_otp_code')->nullable()->after('two_factor_email_enabled');
            $table->timestamp('two_factor_otp_expires_at')->nullable()->after('two_factor_otp_code');
            $table->unsignedTinyInteger('two_factor_attempts')->default(0)->after('two_factor_otp_expires_at');
            $table->json('two_factor_recovery_codes')->nullable()->after('two_factor_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'two_factor_email_enabled',
                'two_factor_otp_code',
                'two_factor_otp_expires_at',
                'two_factor_attempts',
                'two_factor_recovery_codes',
            ]);
        });
    }
};
