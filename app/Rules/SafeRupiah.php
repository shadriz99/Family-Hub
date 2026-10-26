<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class SafeRupiah implements ValidationRule
{
    public const int MAX = 9007199254740991;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_int($value) || $value < -self::MAX || $value > self::MAX) {
            $fail('money.range')->translate();
        }
    }
}
