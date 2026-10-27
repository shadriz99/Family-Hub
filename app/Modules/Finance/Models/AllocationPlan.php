<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Core\Models\Household;
use App\Core\Support\BelongsToHousehold;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $household_id
 * @property string $period
 * @property int $income_base
 * @property int $living_bps
 * @property int $tertiary_bps
 * @property int $debt_bps
 * @property int $savings_bps
 * @property int $social_bps
 * @property string|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Household $household
 * @property-read User|null $creator
 */
class AllocationPlan extends Model
{
    use BelongsToHousehold;
    use HasUlids;

    protected $table = 'fin_allocation_plans';

    protected $fillable = [
        'household_id',
        'period',
        'income_base',
        'living_bps',
        'tertiary_bps',
        'debt_bps',
        'savings_bps',
        'social_bps',
        'created_by',
    ];

    protected $attributes = [
        'income_base' => 0,
        'living_bps' => 0,
        'tertiary_bps' => 0,
        'debt_bps' => 0,
        'savings_bps' => 0,
        'social_bps' => 0,
    ];

    protected function casts(): array
    {
        return [
            'income_base' => 'integer',
            'living_bps' => 'integer',
            'tertiary_bps' => 'integer',
            'debt_bps' => 'integer',
            'savings_bps' => 'integer',
            'social_bps' => 'integer',
        ];
    }

    public function livingTarget(): int
    {
        return (int) floor(($this->income_base * $this->living_bps) / 10000);
    }

    public function tertiaryTarget(): int
    {
        return (int) floor(($this->income_base * $this->tertiary_bps) / 10000);
    }

    public function debtTarget(): int
    {
        return (int) floor(($this->income_base * $this->debt_bps) / 10000);
    }

    public function savingsTarget(): int
    {
        return (int) floor(($this->income_base * $this->savings_bps) / 10000);
    }

    public function socialTarget(): int
    {
        return (int) floor(($this->income_base * $this->social_bps) / 10000);
    }

    public function totalTarget(): int
    {
        return $this->livingTarget()
            + $this->tertiaryTarget()
            + $this->debtTarget()
            + $this->savingsTarget()
            + $this->socialTarget();
    }

    public function unallocatedTarget(): int
    {
        return max(0, $this->income_base - $this->totalTarget());
    }

    public function totalBps(): int
    {
        return $this->living_bps
            + $this->tertiary_bps
            + $this->debt_bps
            + $this->savings_bps
            + $this->social_bps;
    }

    public function unallocatedBps(): int
    {
        return max(0, 10000 - $this->totalBps());
    }

    /**
     * Ratio of debt target to income base as percentage.
     * Returns null if income_base is 0 (as per US-FIN-04A AC5).
     */
    public function debtRatioPercent(): ?float
    {
        if ($this->income_base <= 0) {
            return null;
        }

        return round(($this->debtTarget() / $this->income_base) * 100, 2);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
