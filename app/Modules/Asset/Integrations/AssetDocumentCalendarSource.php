<?php

declare(strict_types=1);

namespace App\Modules\Asset\Integrations;

use App\Core\Calendar\CalendarItem;
use App\Core\Calendar\CalendarSource;
use App\Core\Models\Household;
use App\Core\Support\HouseholdContext;
use App\Modules\Asset\Enums\DocumentStatus;
use App\Modules\Asset\Models\AssetDocument;
use Carbon\CarbonImmutable;
use Throwable;

final class AssetDocumentCalendarSource implements CalendarSource
{
    public function key(): string
    {
        return 'asset.documents';
    }

    public function label(): string
    {
        return 'Dokumen Aset';
    }

    public function items(Household $h, CarbonImmutable $from, CarbonImmutable $to): iterable
    {
        if (! config('modules.asset', false)) {
            return [];
        }

        return app(HouseholdContext::class)->runInContext($h, function () use ($h, $from, $to): iterable {
            try {
                $docs = AssetDocument::query()
                    ->where('household_id', $h->id)
                    ->where('status', DocumentStatus::Active)
                    ->whereNotNull('expires_on')
                    ->whereBetween('expires_on', [$from->toDateString(), $to->toDateString()])
                    ->get();

                $items = [];
                foreach ($docs as $doc) {
                    if (! $doc->expires_on) {
                        continue;
                    }

                    $items[] = new CalendarItem(
                        uid: "ast-doc-{$doc->id}",
                        sourceKey: $this->key(),
                        title: "📄 {$doc->title} berakhir",
                        start: $doc->expires_on->startOfDay(),
                        end: $doc->expires_on->endOfDay(),
                        allDay: true,
                        color: '#9333EA',
                        url: url("/assets/documents/{$doc->id}"),
                        status: $doc->isExpired() ? 'expired' : 'active',
                        description: $doc->notes,
                    );
                }

                return $items;
            } catch (Throwable) {
                return [];
            }
        });
    }
}
