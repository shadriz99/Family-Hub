<?php

declare(strict_types=1);

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Spatie\Activitylog\Models\Activity as BaseActivity;

class Activity extends BaseActivity
{
    use HasUlids;
}
