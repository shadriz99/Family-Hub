<?php

declare(strict_types=1);

namespace App\Modules\Asset\Models;

use App\Core\Support\BelongsToHousehold;
use App\Models\User;
use App\Modules\Asset\Enums\IntervalUnit;
use App\Modules\Asset\Enums\MaintenanceTrigger;
use App\Modules\Asset\Enums\PlanStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $household_id
 * @property string $asset_id
 * @property string $name
 * @property MaintenanceTrigger $trigger
 * @property int|null $interval_value
 * @property IntervalUnit|null $interval_unit
 * @property int|null $meter_interval
 * @property CarbonImmutable|null $last_done_on
 * @property int|null $last_done_meter
 * @property CarbonImmutable|null $next_due_on
 * @property int|null $next_due_meter
 * @property CarbonImmutable|null $snoozed_until
 * @property int|null $estimated_cost
 * @property string|null $vendor_id
 * @property int $remind_days_before
 * @property bool $add_to_finance_plan
 * @property string|null $instructions
 * @property bool $is_active
 * @property string|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Asset|null $asset
 * @property-read Vendor|null $vendor
 */
final class MaintenancePlan extends Model
{
    use BelongsToHousehold;
    use HasUlids;
    use SoftDeletes;

    protected $table = 'ast_maintenance_plans';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'household_id',
        'asset_id',
        'name',
        'trigger',
        'interval_value',
        'interval_unit',
        'meter_interval',
        'last_done_on',
        'last_done_meter',
        'next_due_on',
        'next_due_meter',
        'snoozed_until',
        'estimated_cost',
        'vendor_id',
        'remind_days_before',
        'add_to_finance_plan',
        'instructions',
        'is_active',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trigger' => MaintenanceTrigger::class,
            'interval_unit' => IntervalUnit::class,
            'interval_value' => 'integer',
            'meter_interval' => 'integer',
            'last_done_meter' => 'integer',
            'next_due_meter' => 'integer',
            'estimated_cost' => 'integer',
            'remind_days_before' => 'integer',
            'add_to_finance_plan' => 'boolean',
            'is_active' => 'boolean',
            'last_done_on' => 'immutable_date',
            'next_due_on' => 'immutable_date',
            'snoozed_until' => 'immutable_date',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ServiceRecord, $this>
     */
    public function serviceRecords(): HasMany
    {
        return $this->hasMany(ServiceRecord::class, 'maintenance_plan_id');
    }

    /**
     * Tanggal efektif dengan mempertimbangkan snooze (BR-AST-013).
     */
    public function effectiveDueDate(): ?CarbonImmutable
    {
        if ($this->snoozed_until && $this->next_due_on && $this->snoozed_until->isAfter($this->next_due_on)) {
            return $this->snoozed_until;
        }

        return $this->next_due_on;
    }

    /**
     * Hitung status rencana saat ini (BR-AST-014).
     */
    public function computedStatus(?CarbonImmutable $today = null, ?int $currentMeter = null): PlanStatus
    {
        $today = $today ?? CarbonImmutable::today();

        if ($currentMeter === null && $this->asset) {
            $currentMeter = $this->asset->current_meter;
        }

        $statusByTime = null;
        $statusByMeter = null;

        $hasTime = in_array($this->trigger, [MaintenanceTrigger::Time, MaintenanceTrigger::TimeOrMeter], true);
        $hasMeter = in_array($this->trigger, [MaintenanceTrigger::Meter, MaintenanceTrigger::TimeOrMeter], true);

        if ($hasTime) {
            $effectiveDate = $this->effectiveDueDate();
            if ($effectiveDate !== null) {
                if ($effectiveDate->isBefore($today)) {
                    $statusByTime = PlanStatus::Overdue;
                } elseif ($effectiveDate->isSameDay($today)) {
                    $statusByTime = PlanStatus::Due;
                } elseif ($effectiveDate->isBefore($today->addDays($this->remind_days_before + 1))) {
                    $statusByTime = PlanStatus::Soon;
                } else {
                    $statusByTime = PlanStatus::Ok;
                }
            }
        }

        if ($hasMeter && $this->next_due_meter !== null && $currentMeter !== null) {
            $remaining = $this->next_due_meter - $currentMeter;
            $tenPercent = (int) ceil(($this->meter_interval ?? 1000) * 0.1);

            if ($currentMeter > $this->next_due_meter) {
                $statusByMeter = PlanStatus::Overdue;
            } elseif ($currentMeter === $this->next_due_meter) {
                $statusByMeter = PlanStatus::Due;
            } elseif ($remaining <= $tenPercent) {
                $statusByMeter = PlanStatus::Soon;
            } else {
                $statusByMeter = PlanStatus::Ok;
            }
        }

        if ($this->trigger === MaintenanceTrigger::Time) {
            return $statusByTime ?? PlanStatus::Ok;
        }

        if ($this->trigger === MaintenanceTrigger::Meter) {
            return $statusByMeter ?? PlanStatus::Ok;
        }

        // TimeOrMeter: ambil yang paling mendesak
        $order = [
            PlanStatus::Overdue->value => 1,
            PlanStatus::Due->value => 2,
            PlanStatus::Soon->value => 3,
            PlanStatus::Ok->value => 4,
        ];

        $timeRank = $statusByTime ? $order[$statusByTime->value] : 4;
        $meterRank = $statusByMeter ? $order[$statusByMeter->value] : 4;

        if ($timeRank <= $meterRank && $statusByTime !== null) {
            return $statusByTime;
        }

        return $statusByMeter ?? PlanStatus::Ok;
    }

    /**
     * Hitung next_due_on dari basis waktu (BR-AST-010).
     */
    public function calculateNextDueDate(?CarbonImmutable $baseDate = null): ?CarbonImmutable
    {
        if (! in_array($this->trigger, [MaintenanceTrigger::Time, MaintenanceTrigger::TimeOrMeter], true)) {
            return null;
        }

        $base = $baseDate
            ?? $this->last_done_on
            ?? $this->asset->purchase_date
            ?? ($this->created_at ? CarbonImmutable::parse($this->created_at)->startOfDay() : CarbonImmutable::today());

        $val = $this->interval_value ?? 1;

        return match ($this->interval_unit) {
            IntervalUnit::Day => $base->addDays($val),
            IntervalUnit::Week => $base->addWeeks($val),
            IntervalUnit::Month => $base->addMonthsNoOverflow($val),
            IntervalUnit::Year => $base->addYearsNoOverflow($val),
            default => $base->addMonthsNoOverflow($val),
        };
    }

    /**
     * Hitung next_due_meter dari basis meter (BR-AST-011).
     */
    public function calculateNextDueMeter(?int $baseMeter = null): ?int
    {
        if (! in_array($this->trigger, [MaintenanceTrigger::Meter, MaintenanceTrigger::TimeOrMeter], true)) {
            return null;
        }

        $base = $baseMeter ?? $this->last_done_meter ?? $this->asset->current_meter ?? 0;

        return $base + ($this->meter_interval ?? 0);
    }
}
