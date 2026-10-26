<?php

declare(strict_types=1);

namespace App\Core\Enums;

enum InvitationType: string
{
    case NewHousehold = 'new_household';
    case Partner = 'partner';
}
