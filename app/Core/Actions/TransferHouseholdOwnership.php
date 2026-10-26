<?php

declare(strict_types=1);

namespace App\Core\Actions;

use App\Core\Enums\MemberRole;
use App\Core\Models\Household;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class TransferHouseholdOwnership
{
    public function handle(User $actor, Household $household, User $target): void
    {
        DB::transaction(function () use ($actor, $household, $target): void {
            $household = Household::query()->lockForUpdate()->findOrFail($household->getKey());
            Gate::forUser($actor)->authorize('transferOwnership', $household);
            $members = $household->members()->orderBy('slot')->lockForUpdate()->get();
            $owner = $members->firstWhere('user_id', $actor->getKey());
            $partner = $members->firstWhere('user_id', $target->getKey());

            if ($owner === null || $partner === null || $partner->role !== MemberRole::Partner) {
                throw ValidationException::withMessages(['member' => __('household.partner_required')]);
            }

            $owner->role = MemberRole::Partner;
            $owner->save();
            $partner->role = MemberRole::Owner;
            $partner->save();
            activity()->performedOn($household)->causedBy($actor)
                ->withProperties(['previous_owner_id' => $actor->getKey(), 'new_owner_id' => $target->getKey()])
                ->log('core.household.ownership_transferred');
        });
    }
}
