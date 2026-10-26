<?php

declare(strict_types=1);

namespace App\Core\Support;

use App\Core\Models\HouseholdInvitation;

/**
 * An issued invitation together with the plaintext token, which is never persisted.
 */
final readonly class IssuedInvitation
{
    public function __construct(
        public HouseholdInvitation $invitation,
        public string $token,
    ) {
    }
}
