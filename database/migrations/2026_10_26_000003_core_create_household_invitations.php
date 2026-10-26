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
            // Null until the invitee registers for an admin-created new household.
            $table->foreignUlid('household_id')->nullable()->constrained();
            $table->string('email');
            $table->enum('type', ['new_household', 'partner']);
            $table->char('token_hash', 64)->unique();
            $table->foreignUlid('invited_by')->constrained('users');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['household_id', 'accepted_at'], 'household_invitation_pending_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('household_invitations');
    }
};
