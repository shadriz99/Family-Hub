<?php

declare(strict_types=1);

namespace App\Modules\Asset\Integrations;

use App\Core\Contracts\HouseholdExporter;
use App\Core\Models\Household;
use App\Modules\Asset\Models\Asset;
use App\Modules\Asset\Models\AssetCategory;
use App\Modules\Asset\Models\AssetDocument;
use App\Modules\Asset\Models\Issue;
use App\Modules\Asset\Models\Location;
use App\Modules\Asset\Models\MaintenancePlan;
use App\Modules\Asset\Models\MeterReading;
use App\Modules\Asset\Models\ServiceRecord;
use App\Modules\Asset\Models\Vendor;

final class AssetHouseholdExporter implements HouseholdExporter
{
    public function module(): string
    {
        return 'asset';
    }

    /**
     * @return array<string, mixed>
     */
    public function export(Household $household): array
    {
        $locations = Location::query()
            ->where('household_id', $household->id)
            ->get()
            ->map(fn (Location $l) => [
                'id' => $l->id,
                'name' => $l->name,
                'icon' => $l->icon,
                'sort_order' => $l->sort_order,
            ]);

        $categories = AssetCategory::query()
            ->where('household_id', $household->id)
            ->get()
            ->map(fn (AssetCategory $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'icon' => $c->icon,
                'meter_unit' => $c->meter_unit?->value,
                'is_vehicle' => $c->is_vehicle,
                'system_key' => $c->system_key,
            ]);

        $assets = Asset::query()
            ->where('household_id', $household->id)
            ->get()
            ->map(fn (Asset $a) => [
                'id' => $a->id,
                'name' => $a->name,
                'category_id' => $a->category_id,
                'location_id' => $a->location_id,
                'brand' => $a->brand,
                'model' => $a->model,
                'serial_number' => $a->serial_number,
                'plate_number' => $a->plate_number,
                'purchase_date' => $a->purchase_date?->toDateString(),
                'purchase_price' => $a->purchase_price,
                'purchased_from' => $a->purchased_from,
                'status' => $a->status->value,
                'disposed_on' => $a->disposed_on?->toDateString(),
                'meter_unit' => $a->meter_unit?->value,
                'current_meter' => $a->current_meter,
                'meter_updated_on' => $a->meter_updated_on?->toDateString(),
                'is_favorite' => $a->is_favorite,
            ]);

        $documents = AssetDocument::query()
            ->where('household_id', $household->id)
            ->get()
            ->map(fn (AssetDocument $d) => [
                'id' => $d->id,
                'asset_id' => $d->asset_id,
                'type' => $d->type->value,
                'title' => $d->title,
                'number' => $d->number,
                'issuer' => $d->issuer,
                'issued_on' => $d->issued_on?->toDateString(),
                'expires_on' => $d->expires_on?->toDateString(),
                'status' => $d->status->value,
                'renewal_cost_estimate' => $d->renewal_cost_estimate,
                'add_to_finance_plan' => $d->add_to_finance_plan,
            ]);

        $plans = MaintenancePlan::query()
            ->where('household_id', $household->id)
            ->get()
            ->map(fn (MaintenancePlan $p) => [
                'id' => $p->id,
                'asset_id' => $p->asset_id,
                'name' => $p->name,
                'trigger' => $p->trigger->value,
                'interval_value' => $p->interval_value,
                'interval_unit' => $p->interval_unit?->value,
                'meter_interval' => $p->meter_interval,
                'last_done_on' => $p->last_done_on?->toDateString(),
                'last_done_meter' => $p->last_done_meter,
                'next_due_on' => $p->next_due_on?->toDateString(),
                'next_due_meter' => $p->next_due_meter,
                'snoozed_until' => $p->snoozed_until?->toDateString(),
                'estimated_cost' => $p->estimated_cost,
                'is_active' => $p->is_active,
            ]);

        $records = ServiceRecord::query()
            ->where('household_id', $household->id)
            ->get()
            ->map(fn (ServiceRecord $r) => [
                'id' => $r->id,
                'asset_id' => $r->asset_id,
                'maintenance_plan_id' => $r->maintenance_plan_id,
                'issue_id' => $r->issue_id,
                'type' => $r->type->value,
                'performed_on' => $r->performed_on->toDateString(),
                'meter_reading' => $r->meter_reading,
                'cost' => $r->cost,
                'description' => $r->description,
                'parts' => $r->parts,
            ]);

        $readings = MeterReading::query()
            ->where('household_id', $household->id)
            ->get()
            ->map(fn (MeterReading $m) => [
                'id' => $m->id,
                'asset_id' => $m->asset_id,
                'raw_reading' => $m->raw_reading,
                'cumulative_offset' => $m->cumulative_offset,
                'reading' => $m->reading,
                'read_on' => $m->read_on->toDateString(),
                'is_reset' => $m->is_reset,
                'source' => $m->source,
            ]);

        $vendors = Vendor::query()
            ->where('household_id', $household->id)
            ->get()
            ->map(fn (Vendor $v) => [
                'id' => $v->id,
                'name' => $v->name,
                'services' => $v->services,
                'phone' => $v->phone,
                'whatsapp' => $v->whatsapp,
                'address' => $v->address,
                'rating' => $v->rating,
                'is_favorite' => $v->is_favorite,
            ]);

        $issues = Issue::query()
            ->where('household_id', $household->id)
            ->get()
            ->map(fn (Issue $i) => [
                'id' => $i->id,
                'title' => $i->title,
                'description' => $i->description,
                'asset_id' => $i->asset_id,
                'location_id' => $i->location_id,
                'priority' => $i->priority->value,
                'status' => $i->status->value,
                'scheduled_on' => $i->scheduled_on?->toDateString(),
                'resolved_on' => $i->resolved_on?->toDateString(),
            ]);

        return [
            'locations.json' => $locations->values()->all(),
            'categories.json' => $categories->values()->all(),
            'assets.json' => $assets->values()->all(),
            'documents.json' => $documents->values()->all(),
            'maintenance_plans.json' => $plans->values()->all(),
            'service_records.json' => $records->values()->all(),
            'meter_readings.json' => $readings->values()->all(),
            'vendors.json' => $vendors->values()->all(),
            'issues.json' => $issues->values()->all(),
        ];
    }
}
