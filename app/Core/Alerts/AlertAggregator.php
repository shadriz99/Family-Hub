<?php

declare(strict_types=1);

namespace App\Core\Alerts;

use App\Core\Models\Household;
use Illuminate\Contracts\Container\Container;

final class AlertAggregator
{
    public const DEFAULT_LIMIT = 5;

    private const LEVEL_PRIORITY = [
        'danger' => 1,
        'warning' => 2,
        'info' => 3,
    ];

    /**
     * @param iterable<AlertSource>|null $sources
     */
    public function __construct(
        private readonly ?Container $container = null,
        private readonly ?iterable $sources = null,
    ) {
    }

    /**
     * @return array<Alert>
     */
    public function for(Household $household, ?int $limit = self::DEFAULT_LIMIT): array
    {
        $activeSources = $this->resolveSources();

        $alerts = [];
        foreach ($activeSources as $source) {
            foreach ($source->alerts($household) as $alert) {
                $alerts[] = $alert;
            }
        }

        usort($alerts, function (Alert $a, Alert $b): int {
            $pA = self::LEVEL_PRIORITY[$a->level] ?? 99;
            $pB = self::LEVEL_PRIORITY[$b->level] ?? 99;

            if ($pA !== $pB) {
                return $pA <=> $pB;
            }

            return strcmp($a->title, $b->title);
        });

        if ($limit !== null && $limit > 0) {
            return array_slice($alerts, 0, $limit);
        }

        return $alerts;
    }

    /**
     * @return iterable<AlertSource>
     */
    private function resolveSources(): iterable
    {
        if ($this->sources !== null) {
            return $this->sources;
        }

        if ($this->container !== null) {
            /** @var iterable<AlertSource> $tagged */
            $tagged = $this->container->tagged('alert.sources');

            return $tagged;
        }

        return [];
    }
}
