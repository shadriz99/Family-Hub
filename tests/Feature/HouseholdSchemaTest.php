<?php

declare(strict_types=1);

use App\Core\Enums\MemberRole;
use App\Core\Models\Household;
use App\Core\Models\HouseholdMember;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'test-password']);
    $this->household = Household::create(['name' => 'Synthetic household']);
});

it('uses ULIDs and exposes real household relationships and period history', function (): void {
    $member = new HouseholdMember(['role' => MemberRole::Owner, 'color' => MemberRole::Owner->color(), 'slot' => 1, 'joined_at' => now()]);
    $member->user()->associate($this->user);
    $this->household->members()->save($member);
    $this->household->financePeriodSettings()->create(['start_day' => 1, 'effective_on' => '2026-01-01']);

    expect(Str::isUlid($this->user->id))->toBeTrue()
        ->and(Str::isUlid($this->household->id))->toBeTrue()
        ->and($this->user->householdMembership->household->is($this->household))->toBeTrue()
        ->and($member->fresh()->role)->toBe(MemberRole::Owner)
        ->and($this->household->financePeriodSettings()->firstOrFail()->start_day)->toBe(1)
        ->and($this->user->fresh()->is_system_admin)->toBeFalse();
});

it('enforces slot and unique household membership directly in the database', function (string $violation): void {
    $row = [
        'id' => (string) Str::ulid(), 'household_id' => $this->household->id,
        'user_id' => $this->user->id, 'role' => 'owner', 'color' => '#059669',
        'joined_at' => now(), 'slot' => 1,
    ];
    DB::table('household_members')->insert($row);
    $other = User::create(['name' => 'Partner', 'email' => 'partner@example.test', 'password' => 'test-password']);
    $row['id'] = (string) Str::ulid();

    if ($violation === 'user') {
        $row['slot'] = 2;
    } else {
        $row['user_id'] = $other->id;
        $row['slot'] = $violation === 'slot' ? 1 : 3;
    }

    expect(fn () => DB::table('household_members')->insert($row))->toThrow(QueryException::class);
})->with(['user', 'slot', 'capacity']);

it('allows exactly two distinct slots and rejects a third member', function (): void {
    foreach ([1, 2] as $slot) {
        $user = User::create(['name' => 'Member', 'email' => "member{$slot}@example.test", 'password' => 'test-password']);
        DB::table('household_members')->insert([
            'id' => (string) Str::ulid(), 'household_id' => $this->household->id,
            'user_id' => $user->id, 'role' => $slot === 1 ? 'owner' : 'partner',
            'color' => '#059669', 'joined_at' => now(), 'slot' => $slot,
        ]);
    }

    expect($this->household->members()->count())->toBe(2);
    expect(fn () => DB::table('household_members')->insert([
        'id' => (string) Str::ulid(), 'household_id' => $this->household->id,
        'user_id' => $this->user->id, 'role' => 'partner',
        'color' => '#7C3AED', 'joined_at' => now(), 'slot' => 3,
    ]))->toThrow(QueryException::class);
});

it('rejects duplicate history effective dates in one household', function (): void {
    $this->household->financePeriodSettings()->create(['start_day' => 1, 'effective_on' => '2026-01-01']);
    expect(fn () => $this->household->financePeriodSettings()->create([
        'start_day' => 25, 'effective_on' => '2026-01-01',
    ]))->toThrow(QueryException::class);
});

it('soft deletes households', function (): void {
    $id = $this->household->id;
    $this->household->delete();
    expect(Household::find($id))->toBeNull()
        ->and(Household::withTrashed()->findOrFail($id)->trashed())->toBeTrue();
});
