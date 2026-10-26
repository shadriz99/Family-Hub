<?php

declare(strict_types=1);

use App\Core\Actions\RemoveHouseholdMember;
use App\Core\Actions\TransferHouseholdOwnership;
use App\Core\Enums\MemberRole;
use App\Core\Models\Activity;
use App\Core\Models\Household;
use App\Core\Models\HouseholdMember;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->household = Household::create(['name' => 'Synthetic household']);
    $this->owner = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'test-password']);
    $this->partner = User::create(['name' => 'Partner', 'email' => 'partner@example.test', 'password' => 'test-password']);
    $this->outsider = User::create(['name' => 'Other', 'email' => 'other@example.test', 'password' => 'test-password']);
    foreach ([[$this->owner, MemberRole::Owner, 1], [$this->partner, MemberRole::Partner, 2]] as [$user, $role, $slot]) {
        $member = new HouseholdMember(['role' => $role, 'slot' => $slot, 'color' => $role->color(), 'joined_at' => now()]);
        $member->user()->associate($user);
        $this->household->members()->save($member);
    }
});

it('removes only the partner and records the actual actor and household', function (bool $leaving): void {
    $actor = $leaving ? $this->partner : $this->owner;
    app(RemoveHouseholdMember::class)->handle($actor, $this->household, $this->partner);

    expect($this->household->members()->count())->toBe(1)
        ->and($this->owner->householdMembership()->exists())->toBeTrue()
        ->and($this->partner->householdMembership()->exists())->toBeFalse()
        ->and(User::find($this->partner->id))->not->toBeNull();
    $log = Activity::query()->sole();
    expect(Str::isUlid($log->getKey()))->toBeTrue()
        ->and($log->subject->is($this->household))->toBeTrue()
        ->and($log->causer->is($actor))->toBeTrue()
        ->and($log->getProperty('member_user_id'))->toBe($this->partner->id)
        ->and($log->description)->toBe($leaving ? 'core.household.member_left' : 'core.household.partner_removed');
})->with([true, false]);

it('transfers roles atomically while preserving slots and calendar colors', function (): void {
    $before = $this->household->members()->orderBy('slot')->get()->map(fn ($member) => [$member->slot, $member->color, $member->joined_at->toISOString()])->all();
    app(TransferHouseholdOwnership::class)->handle($this->owner, $this->household, $this->partner);

    expect($this->owner->householdMembership()->firstOrFail()->role)->toBe(MemberRole::Partner)
        ->and($this->partner->householdMembership()->firstOrFail()->role)->toBe(MemberRole::Owner)
        ->and($this->household->members()->orderBy('slot')->get()->map(fn ($member) => [$member->slot, $member->color, $member->joined_at->toISOString()])->all())->toBe($before)
        ->and(Gate::forUser($this->owner)->allows('delete', $this->household))->toBeFalse()
        ->and(Gate::forUser($this->partner)->allows('delete', $this->household))->toBeTrue()
        ->and(Activity::query()->sole()->getProperty('new_owner_id'))->toBe($this->partner->id);
});

it('denies owner leaving, partner removing owner, and outsider mutation including system admin', function (string $attempt): void {
    $this->outsider->forceFill(['is_system_admin' => true])->save();
    [$actor, $target] = match ($attempt) {
        'owner-leave' => [$this->owner, $this->owner],
        'partner-remove' => [$this->partner, $this->owner],
        default => [$this->outsider, $this->partner],
    };
    expect(fn () => app(RemoveHouseholdMember::class)->handle($actor, $this->household, $target))->toThrow(AuthorizationException::class);
    expect($this->household->members()->count())->toBe(2)->and(Activity::count())->toBe(0);
})->with(['owner-leave', 'partner-remove', 'admin-remove']);

it('rejects transfer by anyone except the current owner', function (bool $admin): void {
    $actor = $admin ? $this->outsider : $this->partner;
    $actor->forceFill(['is_system_admin' => $admin])->save();
    expect(fn () => app(TransferHouseholdOwnership::class)->handle($actor, $this->household, $this->partner))->toThrow(AuthorizationException::class);
    expect(Activity::count())->toBe(0);
})->with([true, false]);

it('rejects invalid targets without mutating memberships', function (string $action, bool $self): void {
    $target = $self ? $this->owner : $this->outsider;
    $class = $action === 'transfer' ? TransferHouseholdOwnership::class : RemoveHouseholdMember::class;
    $exception = $action === 'remove' && $self ? AuthorizationException::class : ValidationException::class;
    expect(fn () => app($class)->handle($this->owner, $this->household, $target))->toThrow($exception);
    expect($this->household->members()->count())->toBe(2)
        ->and($this->owner->householdMembership()->firstOrFail()->role)->toBe(MemberRole::Owner)
        ->and(Activity::count())->toBe(0);
})->with([['transfer', true], ['transfer', false], ['remove', true], ['remove', false]]);

it('rechecks current membership instead of trusting stale actor data', function (): void {
    $this->owner->load('householdMembership');
    app(TransferHouseholdOwnership::class)->handle($this->owner, $this->household, $this->partner);
    expect(fn () => app(TransferHouseholdOwnership::class)->handle($this->owner, $this->household, $this->partner))->toThrow(AuthorizationException::class);
    expect(Activity::count())->toBe(1);
});

it('never mutates a partner belonging to another household', function (string $action): void {
    $other = Household::create(['name' => 'Other household']);
    $member = new HouseholdMember(['role' => MemberRole::Partner, 'slot' => 2, 'color' => '#7C3AED', 'joined_at' => now()]);
    $member->user()->associate($this->outsider);
    $other->members()->save($member);

    expect(fn () => app($action)->handle($this->owner, $this->household, $this->outsider))->toThrow(ValidationException::class);
    expect($other->members()->count())->toBe(1)
        ->and($this->household->members()->count())->toBe(2)
        ->and(Activity::count())->toBe(0);
})->with([RemoveHouseholdMember::class, TransferHouseholdOwnership::class]);

it('rejects mutations of deleted households', function (string $action): void {
    $this->household->delete();
    expect(fn () => app($action)->handle($this->owner, $this->household, $this->partner))->toThrow(ModelNotFoundException::class);
    expect(Activity::count())->toBe(0);
})->with([RemoveHouseholdMember::class, TransferHouseholdOwnership::class]);

it('rolls membership writes back if audit persistence fails', function (string $action): void {
    $event = 'eloquent.creating: '.Activity::class;
    Event::listen($event, function (): never {
        throw new RuntimeException('Synthetic audit failure');
    });
    try {
        expect(fn () => app($action)->handle($this->owner, $this->household, $this->partner))->toThrow(RuntimeException::class, 'Synthetic audit failure');
    } finally {
        Event::forget($event);
    }

    expect($this->household->members()->count())->toBe(2)
        ->and($this->owner->householdMembership()->firstOrFail()->role)->toBe(MemberRole::Owner)
        ->and($this->partner->householdMembership()->firstOrFail()->role)->toBe(MemberRole::Partner)
        ->and(Activity::count())->toBe(0);
})->with([RemoveHouseholdMember::class, TransferHouseholdOwnership::class]);
