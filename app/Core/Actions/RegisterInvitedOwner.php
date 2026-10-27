<?php

declare(strict_types=1);

namespace App\Core\Actions;

use App\Core\Models\HouseholdInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class RegisterInvitedOwner
{
    /**
     * Create the invited owner account and bootstrap the household in one transaction.
     */
    public function handle(HouseholdInvitation $invitation, string $token, string $name, string $password, string $householdName): User
    {
        return DB::transaction(function () use ($invitation, $token, $name, $password, $householdName): User {
            $user = User::create([
                'name' => trim($name),
                'email' => $invitation->email,
                'password' => $password,
            ]);

            app(AcceptNewHouseholdInvitation::class)->handle($user, $token, $householdName);

            return $user;
        });
    }
}
