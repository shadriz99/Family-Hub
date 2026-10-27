<?php

declare(strict_types=1);

namespace App\Modules\Asset\Actions;

use App\Core\Models\Household;
use App\Models\User;
use App\Modules\Asset\Enums\MeterUnit;
use App\Modules\Asset\Models\AssetCategory;
use App\Modules\Asset\Models\Location;

final class SeedDefaultAssetData
{
    /**
     * @return array{locations: int, categories: int}
     */
    public function handle(Household $household, ?User $actor = null): array
    {
        return app(\App\Core\Support\HouseholdContext::class)->runInContext($household, function () use ($household, $actor): array {
            $locations = [
                ['name' => 'Ruang Tamu', 'icon' => 'sofa', 'sort_order' => 1],
                ['name' => 'Ruang Keluarga', 'icon' => 'tv', 'sort_order' => 2],
                ['name' => 'Dapur', 'icon' => 'utensils', 'sort_order' => 3],
                ['name' => 'Kamar Utama', 'icon' => 'bed', 'sort_order' => 4],
                ['name' => 'Kamar Anak', 'icon' => 'baby', 'sort_order' => 5],
                ['name' => 'Kamar Mandi', 'icon' => 'bath', 'sort_order' => 6],
                ['name' => 'Garasi / Carport', 'icon' => 'warehouse', 'sort_order' => 7],
                ['name' => 'Halaman', 'icon' => 'trees', 'sort_order' => 8],
                ['name' => 'Atap & Plafon', 'icon' => 'home', 'sort_order' => 9],
                ['name' => 'Lainnya', 'icon' => 'box', 'sort_order' => 10],
            ];

            $locationsCreated = 0;
            foreach ($locations as $loc) {
                $existing = Location::withoutGlobalScopes()
                    ->where('household_id', $household->id)
                    ->where('name', $loc['name'])
                    ->first();

                if (! $existing) {
                    Location::create([
                        'household_id' => $household->id,
                        'name' => $loc['name'],
                        'icon' => $loc['icon'],
                        'sort_order' => $loc['sort_order'],
                        'created_by' => $actor?->id,
                    ]);
                    $locationsCreated++;
                }
            }

            $categories = [
                ['name' => 'Elektronik', 'icon' => 'tv', 'meter_unit' => null, 'is_vehicle' => false, 'system_key' => 'electronics'],
                ['name' => 'AC', 'icon' => 'wind', 'meter_unit' => null, 'is_vehicle' => false, 'system_key' => 'air_conditioner'],
                ['name' => 'Peralatan Dapur', 'icon' => 'refrigerator', 'meter_unit' => null, 'is_vehicle' => false, 'system_key' => 'kitchen'],
                ['name' => 'Mesin Cuci & Laundry', 'icon' => 'shirt', 'meter_unit' => null, 'is_vehicle' => false, 'system_key' => 'laundry'],
                ['name' => 'Kendaraan Motor', 'icon' => 'bike', 'meter_unit' => MeterUnit::Km, 'is_vehicle' => true, 'system_key' => 'motorcycle'],
                ['name' => 'Kendaraan Mobil', 'icon' => 'car', 'meter_unit' => MeterUnit::Km, 'is_vehicle' => true, 'system_key' => 'car'],
                ['name' => 'Gadget & Komputer', 'icon' => 'laptop', 'meter_unit' => null, 'is_vehicle' => false, 'system_key' => 'gadget'],
                ['name' => 'Furnitur', 'icon' => 'armchair', 'meter_unit' => null, 'is_vehicle' => false, 'system_key' => 'furniture'],
                ['name' => 'Instalasi Air & Pompa', 'icon' => 'droplet', 'meter_unit' => null, 'is_vehicle' => false, 'system_key' => 'plumbing'],
                ['name' => 'Instalasi Listrik', 'icon' => 'zap', 'meter_unit' => null, 'is_vehicle' => false, 'system_key' => 'electrical'],
                ['name' => 'Bangunan', 'icon' => 'building', 'meter_unit' => null, 'is_vehicle' => false, 'system_key' => 'building'],
                ['name' => 'Taman', 'icon' => 'flower', 'meter_unit' => null, 'is_vehicle' => false, 'system_key' => 'garden'],
                ['name' => 'Lainnya', 'icon' => 'boxes', 'meter_unit' => null, 'is_vehicle' => false, 'system_key' => 'other'],
            ];

            $categoriesCreated = 0;
            foreach ($categories as $cat) {
                $existing = AssetCategory::withoutGlobalScopes()
                    ->where('household_id', $household->id)
                    ->where('system_key', $cat['system_key'])
                    ->first();

                if (! $existing) {
                    AssetCategory::create([
                        'household_id' => $household->id,
                        'name' => $cat['name'],
                        'icon' => $cat['icon'],
                        'meter_unit' => $cat['meter_unit'],
                        'is_vehicle' => $cat['is_vehicle'],
                        'system_key' => $cat['system_key'],
                        'created_by' => $actor?->id,
                    ]);
                    $categoriesCreated++;
                }
            }

            return [
                'locations' => $locationsCreated,
                'categories' => $categoriesCreated,
            ];
        });
    }
}
