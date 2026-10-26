<?php

declare(strict_types=1);

namespace App\Core\Actions;

use App\Core\Enums\MemberRole;
use App\Core\Models\Household;
use App\Core\Models\HouseholdMember;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateHousehold
{
    /**
     * Bootstrap a household with its owner membership and initial finance period.
     * Deliberately independent of the ambient tenant context so jobs can call it.
     */
    public function handle(User $owner, string $name, int $startDay = 1, string $timezone = 'Asia/Jakarta'): Household
    {
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > 100) {
            throw ValidationException::withMessages(['name' => __('household.name_invalid')]);
        }

        if ($startDay < 1 || $startDay > 28) {
            throw ValidationException::withMessages(['start_day' => __('household.start_day_invalid')]);
        }

        if (! in_array($timezone, timezone_identifiers_list(), true)) {
            throw ValidationException::withMessages(['timezone' => __('household.timezone_invalid')]);
        }

        // Reviewer decision (LOG-041): a system admin is a management account and never
        // becomes a household member, so it cannot own a household either.
        if ($owner->is_system_admin) {
            throw ValidationException::withMessages(['owner' => __('household.admin_not_member')]);
        }

        return DB::transaction(function () use ($owner, $name, $startDay, $timezone): Household {
            $existing = HouseholdMember::query()
                ->where('user_id', $owner->getKey())
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                throw ValidationException::withMessages(['owner' => __('household.already_member')]);
            }

            $household = new Household([
                'name' => $name,
                'timezone' => $timezone,
                'finance_period_start_day' => $startDay,
            ]);
            $household->created_by = $owner->getKey();
            $household->save();

            $member = new HouseholdMember([
                'role' => MemberRole::Owner,
                'color' => MemberRole::Owner->color(),
                'slot' => 1,
                'joined_at' => now(),
            ]);
            $member->user()->associate($owner);
            $household->members()->save($member);

            // Tenant-owned writes require the explicit boundary for this new household.
            $context = app(HouseholdContext::class);
            $previous = $context->currentOrNull();
            $context->use($household);

            try {
                $household->financePeriodSettings()->create([
                    'start_day' => $startDay,
                    'effective_on' => now($timezone)->toDateString(),
                ]);
            } finally {
                if ($previous === null) {
                    $context->forget();
                } else {
                    $context->use($previous);
                }
            }

            activity()->performedOn($household)->causedBy($owner)
                ->withProperties(['timezone' => $timezone, 'start_day' => $startDay])
                ->log('core.household.created');

            return $household;
        });
    }
}
