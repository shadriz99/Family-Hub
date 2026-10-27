<?php

declare(strict_types=1);

namespace App\Core\Alerts;

final readonly class Alert
{
    public function __construct(
        public string $level,
        public string $title,
        public string $url,
        public string $sourceKey,
    ) {
    }
}
