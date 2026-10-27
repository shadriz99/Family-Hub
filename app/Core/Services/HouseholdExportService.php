<?php

declare(strict_types=1);

namespace App\Core\Services;

use App\Core\Contracts\HouseholdExporter;
use App\Core\Models\Household;
use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

final class HouseholdExportService
{
    /**
     * @param iterable<HouseholdExporter> $exporters
     */
    public function __construct(private readonly iterable $exporters)
    {
    }

    /**
     * Build a ZIP archive containing all exported files from enabled modules.
     * Returns the absolute path to the generated ZIP archive.
     */
    public function exportToZip(Household $household): string
    {
        $exportDir = storage_path('app/private/exports');

        if (! File::exists($exportDir)) {
            File::makeDirectory($exportDir, 0755, true);
        }

        $timestamp = now($household->timezone)->format('Ymd_His');
        $zipFilename = sprintf('household_%s_%s.zip', $household->getKey(), $timestamp);
        $zipPath = $exportDir.DIRECTORY_SEPARATOR.$zipFilename;

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Gagal membuat arsip ZIP pada path: {$zipPath}");
        }

        // Add root metadata
        $metadata = [
            'household_id' => $household->getKey(),
            'name' => $household->name,
            'exported_at' => now()->toIso8601String(),
            'app_version' => config('app.version', '1.0.0'),
        ];
        $zip->addFromString('manifest.json', json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');

        foreach ($this->exporters as $exporter) {
            $module = $exporter->module();

            // Skip if module is disabled (core is always enabled)
            if ($module !== 'core' && ! (bool) config("modules.{$module}", false)) {
                continue;
            }

            $files = $exporter->export($household);

            foreach ($files as $relativePath => $content) {
                $payload = is_string($content)
                    ? $content
                    : (json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');

                $zip->addFromString($relativePath, $payload);
            }
        }

        $zip->close();

        return $zipPath;
    }
}
