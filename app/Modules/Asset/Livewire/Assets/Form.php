<?php

declare(strict_types=1);

namespace App\Modules\Asset\Livewire\Assets;

use App\Core\Support\HouseholdContext;
use App\Modules\Asset\Enums\AssetStatus;
use App\Modules\Asset\Enums\DocumentStatus;
use App\Modules\Asset\Enums\DocumentType;
use App\Modules\Asset\Enums\IntervalUnit;
use App\Modules\Asset\Enums\MaintenanceTrigger;
use App\Modules\Asset\Enums\MeterUnit;
use App\Modules\Asset\Models\Asset;
use App\Modules\Asset\Models\AssetCategory;
use App\Modules\Asset\Models\AssetDocument;
use App\Modules\Asset\Models\Location;
use App\Modules\Asset\Models\MaintenancePlan;
use App\Modules\Asset\Services\UpdateMeter;
use App\Modules\Finance\Contracts\FinanceGateway;
use App\Modules\Finance\DTO\ExpenseData;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class Form extends Component
{
    public ?string $assetId = null;

    // Fields
    public string $name = '';
    public string $category_id = '';
    public ?string $location_id = null;
    public string $brand = '';
    public string $model = '';
    public string $serial_number = '';
    public string $plate_number = '';
    public ?string $purchase_date = null;
    public ?int $purchase_price = null;
    public string $purchased_from = '';
    public string $status = 'active';
    public ?string $meter_unit = null;
    public ?int $initial_meter = null;
    public bool $is_favorite = false;
    public string $notes = '';

    // Quick warranty (AC3)
    public ?string $warranty_until_date = null;
    public ?int $warranty_months = null;

    // Finance integration (AC4)
    public bool $record_expense = false;
    public ?string $account_id = null;

    /**
     * @var array<int, bool>
     */
    public array $selected_templates = [];

    public function mount(?string $asset = null): void
    {
        $household = app(HouseholdContext::class)->currentOrNull();

        if ($asset) {
            $existing = Asset::where('household_id', $household?->id)->where('id', $asset)->firstOrFail();
            $this->assetId = $existing->id;
            $this->name = $existing->name;
            $this->category_id = $existing->category_id;
            $this->location_id = $existing->location_id;
            $this->brand = $existing->brand ?? '';
            $this->model = $existing->model ?? '';
            $this->serial_number = $existing->serial_number ?? '';
            $this->plate_number = $existing->plate_number ?? '';
            $this->purchase_date = $existing->purchase_date?->toDateString();
            $this->purchase_price = $existing->purchase_price;
            $this->purchased_from = $existing->purchased_from ?? '';
            $this->status = $existing->status->value;
            $this->meter_unit = $existing->meter_unit?->value;
            $this->initial_meter = $existing->current_meter;
            $this->is_favorite = $existing->is_favorite;
            $this->notes = $existing->notes ?? '';
        } else {
            $firstCategory = AssetCategory::where('household_id', $household?->id)->first();
            if ($firstCategory) {
                $this->category_id = $firstCategory->id;
                $this->updatedCategoryId($firstCategory->id);
            }
        }
    }

    public function updatedCategoryId(string $categoryId): void
    {
        $cat = AssetCategory::find($categoryId);
        if ($cat) {
            if ($cat->is_vehicle) {
                $this->meter_unit = $cat->meter_unit->value ?? 'km';
            }

            // Load templates from config
            if (! $this->assetId && $cat->system_key) {
                $templates = config("asset_templates.{$cat->system_key}", []);
                $this->selected_templates = [];
                foreach ($templates as $idx => $t) {
                    $this->selected_templates[$idx] = true;
                }
            }
        }
    }

    public function updatedWarrantyMonths(?int $months): void
    {
        if ($months && $months > 0) {
            $base = $this->purchase_date ? CarbonImmutable::parse($this->purchase_date) : CarbonImmutable::today();
            $this->warranty_until_date = $base->addMonthsNoOverflow($months)->toDateString();
        }
    }

    public function save(FinanceGateway $financeGateway, UpdateMeter $updateMeter): mixed
    {
        $household = app(HouseholdContext::class)->currentOrNull();
        if (! $household) {
            return null;
        }

        $this->validate([
            'name' => 'required|string|max:100',
            'category_id' => 'required|exists:ast_categories,id',
            'location_id' => 'nullable|exists:ast_locations,id',
            'brand' => 'nullable|string|max:60',
            'model' => 'nullable|string|max:60',
            'serial_number' => 'nullable|string|max:80',
            'plate_number' => 'nullable|string|max:20',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|integer|min:0',
            'purchased_from' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $cat = AssetCategory::find($this->category_id);
        $plate = $this->plate_number !== '' ? strtoupper(trim(preg_replace('/\s+/', ' ', $this->plate_number) ?? '')) : null;

        $createdAsset = DB::transaction(function () use ($household, $cat, $plate, $financeGateway, $updateMeter) {
            $user = auth()->user();

            $pDate = $this->purchase_date ? CarbonImmutable::parse($this->purchase_date) : null;

            if ($this->assetId) {
                $asset = Asset::where('household_id', $household->id)->findOrFail($this->assetId);
                $asset->update([
                    'name' => $this->name,
                    'category_id' => $this->category_id,
                    'location_id' => $this->location_id ?: null,
                    'brand' => $this->brand ?: null,
                    'model' => $this->model ?: null,
                    'serial_number' => $this->serial_number ?: null,
                    'plate_number' => $plate,
                    'purchase_date' => $pDate,
                    'purchase_price' => $this->purchase_price,
                    'purchased_from' => $this->purchased_from ?: null,
                    'status' => AssetStatus::from($this->status),
                    'meter_unit' => $this->meter_unit ? MeterUnit::from($this->meter_unit) : null,
                    'is_favorite' => $this->is_favorite,
                    'notes' => $this->notes ?: null,
                ]);

                return $asset;
            }

            // Create new asset
            $asset = Asset::create([
                'household_id' => $household->id,
                'name' => $this->name,
                'category_id' => $this->category_id,
                'location_id' => $this->location_id ?: null,
                'brand' => $this->brand ?: null,
                'model' => $this->model ?: null,
                'serial_number' => $this->serial_number ?: null,
                'plate_number' => $plate,
                'purchase_date' => $pDate,
                'purchase_price' => $this->purchase_price,
                'purchased_from' => $this->purchased_from ?: null,
                'status' => AssetStatus::from($this->status),
                'meter_unit' => $this->meter_unit ? MeterUnit::from($this->meter_unit) : null,
                'current_meter' => $this->initial_meter,
                'meter_updated_on' => $this->initial_meter !== null ? ($pDate ?? CarbonImmutable::today()) : null,
                'is_favorite' => $this->is_favorite,
                'notes' => $this->notes ?: null,
                'created_by' => $user?->id,
            ]);

            // If initial meter is set, write to meter readings
            if ($this->initial_meter !== null && $this->initial_meter > 0) {
                $updateMeter->handle(
                    asset: $asset,
                    rawReading: $this->initial_meter,
                    readOn: $pDate ?? CarbonImmutable::today(),
                    isReset: false,
                    source: 'manual',
                    actor: $user,
                );
            }

            // Quick warranty (AC3)
            if (! empty($this->warranty_until_date)) {
                AssetDocument::create([
                    'household_id' => $household->id,
                    'asset_id' => $asset->id,
                    'type' => DocumentType::Warranty,
                    'title' => 'Garansi ' . $asset->name,
                    'issued_on' => $pDate,
                    'expires_on' => CarbonImmutable::parse($this->warranty_until_date),
                    'remind_days' => [30, 7, 0],
                    'status' => DocumentStatus::Active,
                    'created_by' => $user?->id,
                ]);
            }

            // Finance recording (AC4)
            if ($this->record_expense && $this->purchase_price && $this->purchase_price > 0 && ! empty($this->account_id)) {
                $catId = $financeGateway->defaultCategoryId('asset_purchase')
                    ?? $financeGateway->defaultCategoryId('asset_maintenance')
                    ?? '';

                if ($catId !== '') {
                    $txId = $financeGateway->recordExpense(new ExpenseData(
                        accountId: $this->account_id,
                        categoryId: $catId,
                        amount: $this->purchase_price,
                        occurredOn: $pDate ?? CarbonImmutable::today(),
                        note: 'Pembelian aset: ' . $asset->name,
                        sourceType: 'ast_asset',
                        sourceId: $asset->id,
                    ));

                    if ($txId) {
                        $asset->update(['purchase_transaction_id' => $txId]);
                    }
                }
            }

            // Create selected template maintenance plans (AC5)
            if ($cat?->system_key) {
                $templates = config("asset_templates.{$cat->system_key}", []);
                foreach ($templates as $idx => $tmpl) {
                    if (! empty($this->selected_templates[$idx])) {
                        $trigger = MaintenanceTrigger::from($tmpl['trigger']);
                        $plan = new MaintenancePlan([
                            'household_id' => $household->id,
                            'asset_id' => $asset->id,
                            'name' => $tmpl['name'],
                            'trigger' => $trigger,
                            'interval_value' => $tmpl['interval_value'] ?? null,
                            'interval_unit' => isset($tmpl['interval_unit']) ? IntervalUnit::from($tmpl['interval_unit']) : null,
                            'meter_interval' => $tmpl['meter_interval'] ?? null,
                            'instructions' => $tmpl['instructions'] ?? null,
                            'is_active' => true,
                            'created_by' => $user?->id,
                        ]);

                        $plan->next_due_on = $plan->calculateNextDueDate($pDate ?? CarbonImmutable::today());
                        $plan->next_due_meter = $plan->calculateNextDueMeter($asset->current_meter ?? 0);
                        $plan->save();
                    }
                }
            }

            return $asset;
        });

        return redirect()->route('assets.show', $createdAsset->id);
    }

    public function render(FinanceGateway $financeGateway): View
    {
        $household = app(HouseholdContext::class)->currentOrNull();

        $categories = AssetCategory::query()
            ->where('household_id', $household?->id)
            ->orderBy('name')
            ->get();

        $locations = Location::query()
            ->where('household_id', $household?->id)
            ->orderBy('sort_order')
            ->get();

        $selectedCategory = $categories->firstWhere('id', $this->category_id);
        $templates = $selectedCategory?->system_key
            ? config("asset_templates.{$selectedCategory->system_key}", [])
            : [];

        $accountOptions = $financeGateway->accountOptions();

        return view('modules.asset.assets.form', [
            'categories' => $categories,
            'locations' => $locations,
            'selectedCategory' => $selectedCategory,
            'templates' => $templates,
            'accountOptions' => $accountOptions,
        ]);
    }
}
