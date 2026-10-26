<?php

declare(strict_types=1);

namespace App\Core\Actions;

use App\Core\Enums\MemberRole;
use App\Core\Models\Household;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class RemoveHouseholdMember
{
    public function handle(User $actor, Household $household, User $target): void
    {
        DB::transaction(function () use ($actor, $household, $target): void {
            $household = Household::query()->lockForUpdate()->findOrFail($household->getKey());
            $leaving = $actor->is($target);
            Gate::forUser($actor)->authorize($leaving ? 'leave' : 'removePartner', $household);
            $member = $household->members()->where('user_id', $target->getKey())->lockForUpdate()->first();

            if ($member === null || $member->role !== MemberRole::Partner) {
                throw ValidationException::withMessages(['member' => __('household.partner_required')]);
            }

            $member->delete();
            activity()->performedOn($household)->causedBy($actor)
                ->withProperties(['member_user_id' => $target->getKey()])
                ->log($leaving ? 'core.household.member_left' : 'core.household.partner_removed');
        });
    }
}
