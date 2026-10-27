<?php

declare(strict_types=1);

namespace App\Core\Contracts;

use App\Core\Models\Household;

interface HouseholdExporter
{
    /**
     * Unique module key (e.g. 'core', 'finance', 'organizer', 'asset').
     */
    public function module(): string;

    /**
     * Export household records as an associative array where key is the relative file path
     * (e.g. 'core/household.json') and value is a string or array to be JSON-encoded.
     *
     * @return array<string, mixed>
     */
    public function export(Household $household): array;
}
