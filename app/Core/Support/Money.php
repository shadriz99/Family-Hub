<?php

declare(strict_types=1);

namespace App\Core\Support;

use InvalidArgumentException;

final class Money
{
    public static function format(int $amount): string
    {
        $digits = ltrim((string) $amount, '-');
        $grouped = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $digits);

        return ($amount < 0 ? '-Rp ' : 'Rp ').$grouped;
    }

    public static function parse(string $input): int
    {
        $input = trim($input);

        if (! preg_match('/^(-)?(?:Rp\s*)?(\d+|\d{1,3}(?:\.\d{3})+)(?:,(\d+))?\s*(rb|jt)?$/iD', $input, $matches)) {
            throw new InvalidArgumentException('Invalid Rupiah amount.');
        }

        $fraction = $matches[3] ?? '';
        $scale = match (strtolower($matches[4] ?? '')) {
            'rb' => 3,
            'jt' => 6,
            default => 0,
        };

        if (trim(substr($fraction, $scale), '0') !== '') {
            throw new InvalidArgumentException('Rupiah amounts must be whole integers.');
        }

        $digits = str_replace('.', '', $matches[2])
            .str_pad(substr($fraction, 0, $scale), $scale, '0');
        $digits = ltrim($digits, '0');
        $digits = $digits === '' ? '0' : $digits;
        $negative = $matches[1] === '-';
        $limit = $negative ? substr((string) PHP_INT_MIN, 1) : (string) PHP_INT_MAX;

        if (strlen($digits) > strlen($limit)
            || (strlen($digits) === strlen($limit) && strcmp($digits, $limit) > 0)) {
            throw new InvalidArgumentException('Rupiah amount exceeds the supported integer range.');
        }

        return (int) (($negative ? '-' : '').$digits);
    }
}
