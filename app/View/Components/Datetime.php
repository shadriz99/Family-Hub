<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Core\Support\HouseholdContext;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use InvalidArgumentException;

final class Datetime extends Component
{
    public readonly string $datetime;

    public readonly string $label;

    public function __construct(DateTimeInterface|string $value, ?string $timezone = null, string $format = 'j M Y, H:i')
    {
        $currentHousehold = app(HouseholdContext::class)->currentOrNull();
        $targetTimezone = $timezone ?? ($currentHousehold !== null ? $currentHousehold->timezone : 'Asia/Jakarta');
        new DateTimeZone($targetTimezone);

        if (is_string($value)) {
            $parsed = strtotime($value);
            if ($parsed === false) {
                throw new InvalidArgumentException('Datetime string could not be parsed.');
            }
            $date = CarbonImmutable::parse($value)->setTimezone($targetTimezone);
        } else {
            $date = CarbonImmutable::instance($value)->setTimezone($targetTimezone);
        }

        $this->datetime = $date->toIso8601String();
        $this->label = $date->locale(app()->getLocale())->translatedFormat($format);
    }

    public function render(): View
    {
        return view('components.datetime');
    }
}
