<?php

declare(strict_types=1);

namespace App\Modules\Asset\Services;

use App\Models\User;
use App\Modules\Asset\Models\Asset;
use App\Modules\Asset\Models\MeterReading;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

final class UpdateMeter
{
    public function handle(
        Asset $asset,
        int $rawReading,
        CarbonImmutable $readOn,
        bool $isReset = false,
        string $source = 'manual',
        ?User $actor = null,
    ): MeterReading {
        // Ambil pembacaan fisik terakhir berdasarkan read_on desc lalu created_at desc
        $latestReading = MeterReading::withoutGlobalScopes()
            ->where('household_id', $asset->household_id)
            ->where('asset_id', $asset->id)
            ->orderByDesc('read_on')
            ->orderByDesc('created_at')
            ->first();

        $offset = $latestReading->cumulative_offset ?? 0;

        if (! $isReset) {
            if ($latestReading && $rawReading < $latestReading->raw_reading) {
                throw ValidationException::withMessages([
                    'raw_reading' => 'Angka meter tidak boleh lebih kecil dari pembacaan sebelumnya (' . number_format($latestReading->raw_reading, 0, ',', '.') . '). Jika odometer diganti atau direset, gunakan opsi reset meter.',
                ]);
            }
        } else {
            // BR-AST-003: cumulative_offset = max(latest cumulative reading - new raw_reading, 0)
            $prevCumulative = $latestReading->reading ?? 0;
            $offset = max(0, $prevCumulative - $rawReading);
        }

        $cumulativeReading = $rawReading + $offset;

        $readingRecord = MeterReading::create([
            'household_id' => $asset->household_id,
            'asset_id' => $asset->id,
            'raw_reading' => $rawReading,
            'cumulative_offset' => $offset,
            'reading' => $cumulativeReading,
            'read_on' => $readOn,
            'is_reset' => $isReset,
            'source' => $source,
            'created_by' => $actor?->id,
        ]);

        // Perbarui snapshot aset secara atomik (BR-AST-002)
        $asset->update([
            'current_meter' => $cumulativeReading,
            'meter_updated_on' => $readOn,
        ]);

        return $readingRecord;
    }
}
