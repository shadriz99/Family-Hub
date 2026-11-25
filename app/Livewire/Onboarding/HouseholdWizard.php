<?php

declare(strict_types=1);

namespace App\Livewire\Onboarding;

use App\Core\Actions\InviteHouseholdMember;
use App\Core\Enums\MemberRole;
use App\Core\Models\Household;
use App\Models\User;
use DateTimeZone;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

final class HouseholdWizard extends Component
{
    public int $step = 1;

    public string $householdName = '';

    public string $timezone = 'Asia/Jakarta';

    public int $startDay = 1;

    public string $partnerEmail = '';

    public function mount(): void
    {
        $user = Auth::user();

        if ($user === null) {
            $this->redirect(route('login'));

            return;
        }

        $household = $this->resolveOwnedHousehold($user);

        if ($household === null || $household->isOnboarded()) {
            $this->redirect('/');

            return;
        }

        $this->householdName = $household->name;
        $this->timezone = $household->timezone;
        $this->startDay = $household->finance_period_start_day;
    }

    public function previousStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
            $this->resetValidation();
        }
    }

    public function saveStep1(): void
    {
        $this->validate([
            'householdName' => ['required', 'string', 'min:1', 'max:100'],
        ], [
            'householdName.required' => __('household.name_invalid'),
            'householdName.max' => __('household.name_invalid'),
        ]);

        $household = $this->household();
        $household->name = trim($this->householdName);
        $household->save();

        $this->step = 2;
        $this->resetValidation();
    }

    public function saveStep2(): void
    {
        $this->validate([
            'timezone' => ['required', 'string', Rule::in(DateTimeZone::listIdentifiers())],
            'startDay' => ['required', 'integer', 'between:1,28'],
        ], [
            'timezone.*' => __('household.timezone_invalid'),
            'startDay.*' => __('household.start_day_invalid'),
        ]);

        $household = $this->household();
        $household->timezone = $this->timezone;
        $household->finance_period_start_day = (int) $this->startDay;
        $household->save();

        $context = app(\App\Core\Support\HouseholdContext::class);
        $previous = $context->currentOrNull();
        $context->use($household);

        try {
            $now = \Carbon\CarbonImmutable::now($this->timezone);
            $initialPeriod = (new \App\Core\Support\PeriodResolver([['effective_on' => '2020-01-01', 'start_day' => (int) $this->startDay]], $this->timezone))->forDate($now);
            $setting = $household->financePeriodSettings()->first();

            if ($setting !== null) {
                $setting->update([
                    'start_day' => (int) $this->startDay,
                    'effective_on' => $initialPeriod->start->toDateString(),
                    'ends_on' => $initialPeriod->end->toDateString(),
                ]);
            } else {
                $household->financePeriodSettings()->create([
                    'start_day' => (int) $this->startDay,
                    'effective_on' => $initialPeriod->start->toDateString(),
                    'ends_on' => $initialPeriod->end->toDateString(),
                ]);
            }
        } finally {
            if ($previous === null) {
                $context->forget();
            } else {
                $context->use($previous);
            }
        }

        $this->step = 3;
        $this->resetValidation();
    }

    public function finish(): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            $this->redirect(route('login'));

            return;
        }

        $household = $this->household();
        $trimmedEmail = trim($this->partnerEmail);

        if ($trimmedEmail !== '') {
            $this->validate([
                'partnerEmail' => ['required', 'string', 'email', 'max:255'],
            ], [
                'partnerEmail.*' => __('household.email_invalid'),
            ]);

            try {
                app(InviteHouseholdMember::class)->handle($user, $household, $trimmedEmail);
            } catch (ValidationException $e) {
                $this->setErrorBag($e->validator->getMessageBag());

                return;
            }
        }

        $this->completeOnboarding($user, $household);
    }

    public function skip(): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            $this->redirect(route('login'));

            return;
        }

        $this->completeOnboarding($user, $this->household());
    }

    public function render(): View
    {
        return view('livewire.onboarding.household-wizard', [
            'timezones' => DateTimeZone::listIdentifiers(),
        ]);
    }

    private function completeOnboarding(User $user, Household $household): void
    {
        $household->onboarded_at = now()->toImmutable();
        $household->save();

        activity()->performedOn($household)->causedBy($user)
            ->withProperties([
                'name' => $household->name,
                'timezone' => $household->timezone,
                'finance_period_start_day' => $household->finance_period_start_day,
            ])
            ->log('core.household.onboarded');

        session()->flash('status', __('household.onboarding.completed_success'));

        $this->redirect('/');
    }

    private function household(): Household
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            throw new \RuntimeException('Authenticated user required.');
        }

        $household = $this->resolveOwnedHousehold($user);

        if ($household === null) {
            throw new \RuntimeException('Owned household not found.');
        }

        return $household;
    }

    private function resolveOwnedHousehold(User $user): ?Household
    {
        return Household::query()
            ->whereHas('members', fn (Builder $query): Builder => $query->where('user_id', $user->getKey())->where('role', MemberRole::Owner->value))
            ->first();
    }
}
