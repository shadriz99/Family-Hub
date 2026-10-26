<?php

declare(strict_types=1);

use App\Core\Support\Money;

it('parses Rupiah without floating point arithmetic', function (string $input, int $expected): void {
    expect(Money::parse($input))->toBe($expected);
})->with([
    ['1250000', 1250000],
    ['1.250.000', 1250000],
    ['Rp 1.250.000', 1250000],
    ['1,25jt', 1250000],
    ['500rb', 500000],
    ['0', 0],
    ['000', 0],
    ['-0', 0],
    ['-Rp 50.000', -50000],
    ['  rp 12.345  ', 12345],
    ['1,5RB', 1500],
    ['0,000001jt', 1],
    ['1,0000rb', 1000],
    ['123,00', 123],
    [(string) PHP_INT_MAX, PHP_INT_MAX],
    [(string) PHP_INT_MIN, PHP_INT_MIN],
]);

it('rejects invalid fractional or overflowing amounts', function (string $input): void {
    expect(fn () => Money::parse($input))->toThrow(InvalidArgumentException::class);
})->with([
    '', ' ', 'Rp', '1.25', '12.34.567', '1,25', '0,0001rb',
    '0,0000001jt', '1e6', '1/2', 'Rp -100', '--100', '1 000',
    '1,000.00', '100usd', '<script>', 'NaN', '1jt extra',
    '9223372036854775808', '-9223372036854775809',
]);

it('formats integer Rupiah exactly', function (int $amount, string $expected): void {
    expect(Money::format($amount))->toBe($expected)
        ->and(Money::parse(Money::format($amount)))->toBe($amount);
})->with([
    [0, 'Rp 0'],
    [1250000, 'Rp 1.250.000'],
    [-50000, '-Rp 50.000'],
    [PHP_INT_MAX, 'Rp 9.223.372.036.854.775.807'],
    [PHP_INT_MIN, '-Rp 9.223.372.036.854.775.808'],
]);
