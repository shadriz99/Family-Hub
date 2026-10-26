<?php

declare(strict_types=1);

namespace App\View\Components;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use InvalidArgumentException;

final class Date extends Component
{
    public readonly string $datetime;

    public readonly string $label;

    public function __construct(DateTimeInterface|string $value, string $timezone = 'Asia/Jakarta')
    {
        new DateTimeZone($timezone);

        if (is_string($value)) {
            if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $value, $parts)
                || ! checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
                throw new InvalidArgumentException('Date strings must be valid YYYY-MM-DD calendar dates.');
            }

            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, $timezone);
            $this->datetime = $value;
        } else {
            $date = CarbonImmutable::instance($value)->setTimezone($timezone);
            $this->datetime = $date->toIso8601String();
        }

        $this->label = $date->locale(app()->getLocale())->translatedFormat('j M Y');
    }

    public function render(): View
    {
        return view('components.date');
    }
}
