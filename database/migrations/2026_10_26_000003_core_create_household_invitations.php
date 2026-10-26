<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('household_invitations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('household_id')->constrained();
            $table->char('token_hash', 64)->unique();
            $table->foreignUlid('invited_by')->constrained('users');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->foreignUlid('accepted_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->index(['household_id', 'accepted_at'], 'household_invitation_pending_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('household_invitations');
    }
};
