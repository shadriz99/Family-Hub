<?php

declare(strict_types=1);

namespace App\Core\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;

final class QuietHoursCalculator
{
    /**
     * Calculates the effective reminder instant for a recipient.
     * If the target instant falls inside recipient quiet hours, shifts to the end of quiet hours.
     *
     * @param DateTimeInterface $targetTime UTC target time
     * @param string|null $quietStart Format 'H:i' or 'H:i:s'
     * @param string|null $quietEnd Format 'H:i' or 'H:i:s'
     * @param string $timezone Recipient or household timezone (e.g. 'Asia/Jakarta')
     * @return CarbonImmutable UTC effective time
     */
    public function calculateEffectiveTime(
        DateTimeInterface $targetTime,
        ?string $quietStart,
        ?string $quietEnd,
        string $timezone = 'Asia/Jakarta'
    ): CarbonImmutable {
        $targetUtc = CarbonImmutable::instance($targetTime)->setTimezone('UTC');

        if ($quietStart === null || $quietEnd === null || trim($quietStart) === '' || trim($quietEnd) === '') {
            return $targetUtc;
        }

        $startParts = explode(':', trim($quietStart));
        $endParts = explode(':', trim($quietEnd));

        if (count($startParts) < 2 || count($endParts) < 2) {
            return $targetUtc;
        }

        $startHour = (int) $startParts[0];
        $startMinute = (int) $startParts[1];
        $endHour = (int) $endParts[0];
        $endMinute = (int) $endParts[1];

        $startMinutesOfDay = ($startHour * 60) + $startMinute;
        $endMinutesOfDay = ($endHour * 60) + $endMinute;

        if ($startMinutesOfDay === $endMinutesOfDay) {
            return $targetUtc;
        }

        $local = $targetUtc->setTimezone($timezone);
        $localMinutesOfDay = ($local->hour * 60) + $local->minute;

        $inQuietHours = false;
        $shiftToNextDay = false;

        if ($startMinutesOfDay > $endMinutesOfDay) {
            // Window crosses midnight (e.g. 21:00 to 06:00)
            if ($localMinutesOfDay >= $startMinutesOfDay) {
                $inQuietHours = true;
                $shiftToNextDay = true;
            } elseif ($localMinutesOfDay < $endMinutesOfDay) {
                $inQuietHours = true;
                $shiftToNextDay = false;
            }
        } else {
            // Window within same calendar day (e.g. 13:00 to 14:00)
            if ($localMinutesOfDay >= $startMinutesOfDay && $localMinutesOfDay < $endMinutesOfDay) {
                $inQuietHours = true;
                $shiftToNextDay = false;
            }
        }

        if (! $inQuietHours) {
            return $targetUtc;
        }

        $effectiveLocal = $shiftToNextDay ? $local->addDay() : $local;
        $effectiveLocal = $effectiveLocal->setTime($endHour, $endMinute, 0);

        return $effectiveLocal->setTimezone('UTC');
    }
}
