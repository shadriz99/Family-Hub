<?php

declare(strict_types=1);

namespace App\Core\Actions;

use App\Core\Models\HouseholdInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class RegisterInvitedPartner
{
    /**
     * Create the invited account and admit it in one transaction, so a failed admission
     * never leaves an account behind.
     */
    public function handle(HouseholdInvitation $invitation, string $token, string $name, string $password): User
    {
        return DB::transaction(function () use ($invitation, $token, $name, $password): User {
            $user = User::create([
                'name' => trim($name),
                'email' => $invitation->email,
                'password' => $password,
            ]);

            app(AcceptInvitation::class)->handle($user, $token);

            return $user;
        });
    }
}
