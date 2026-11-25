<?php

declare(strict_types=1);

namespace App\Modules\Finance\Livewire;

use App\Core\Models\HouseholdFinancePeriodSetting;
use App\Core\Support\HouseholdContext;
use App\Core\Support\HouseholdScope;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Tetapkan Periode Keuangan')]
class PeriodSetup extends Component
{
    public string $start_date = '';

    public string $end_date = '';

    public ?string $previousStart = null;

    public ?string $previousEnd = null;

    public bool $isAlreadyActive = false;

    public function mount(): void
    {
        $household = app(HouseholdContext::class)->current();
        $this->isAlreadyActive = $household->hasActiveFinancePeriod();

        $expired = $household->latestExpiredFinancePeriod();
        if ($expired !== null) {
            $this->previousStart = $expired->effective_on->translatedFormat('d M Y');
            $this->previousEnd = $expired->ends_on?->translatedFormat('d M Y');
            $defaultStart = $expired->ends_on?->addDay() ?? CarbonImmutable::now($household->timezone)->startOfDay();
        } else {
            $defaultStart = CarbonImmutable::now($household->timezone)->startOfDay();
        }

        $active = $household->activeFinancePeriod();
        if ($active !== null) {
            $this->start_date = $active->effective_on->toDateString();
            $this->end_date = $active->ends_on?->toDateString() ?? $active->effective_on->addMonthNoOverflow()->subDay()->toDateString();
        } else {
            $this->start_date = $defaultStart->toDateString();
            $this->end_date = $defaultStart->addMonthNoOverflow()->subDay()->toDateString();
        }
    }

    public function save(): void
    {
        $household = app(HouseholdContext::class)->current();
        $user = Auth::user();

        if ($user === null) {
            return;
        }

        $validated = $this->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ], [
            'start_date.required' => __('Tanggal mulai periode wajib diisi.'),
            'end_date.required' => __('Tanggal selesai periode wajib diisi.'),
            'end_date.after_or_equal' => __('Tanggal selesai harus sama dengan atau setelah tanggal mulai.'),
        ]);

        $start = CarbonImmutable::parse($validated['start_date'], $household->timezone)->startOfDay();
        $end = CarbonImmutable::parse($validated['end_date'], $household->timezone)->startOfDay();
        $today = CarbonImmutable::now($household->timezone)->startOfDay();

        if ($end < $today) {
            $this->addError('end_date', __('Tanggal selesai periode harus mencakup hari ini atau setelahnya agar menu Keuangan dapat dibuka.'));

            return;
        }

        $overlapping = $household->financePeriodSettings()
            ->withoutGlobalScope(HouseholdScope::class)
            ->whereNotNull('ends_on')
            ->whereDate('effective_on', '!=', $start->toDateString())
            ->whereDate('effective_on', '<=', $end->toDateString())
            ->whereDate('ends_on', '>=', $start->toDateString())
            ->exists();

        if ($overlapping) {
            $this->addError('start_date', __('Rentang tanggal ini bertabrakan dengan periode keuangan yang sudah tercatat.'));

            return;
        }

        DB::transaction(function () use ($household, $user, $start, $end): void {
            $startDay = min(28, $start->day);

            $existing = HouseholdFinancePeriodSetting::query()
                ->withoutGlobalScope(HouseholdScope::class)
                ->where('household_id', $household->getKey())
                ->whereDate('effective_on', $start->toDateString())
                ->first();

            if ($existing !== null) {
                $existing->start_day = $startDay;
                $existing->ends_on = $end->toDateString();
                $existing->save();
            } else {
                $household->financePeriodSettings()->create([
                    'start_day' => $startDay,
                    'effective_on' => $start->toDateString(),
                    'ends_on' => $end->toDateString(),
                ]);
            }

            $household->finance_period_start_day = $startDay;
            $household->save();

            activity()
                ->performedOn($household)
                ->causedBy($user)
                ->withProperties([
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                ])
                ->log('fin.period.stated');
        });

        $this->redirectRoute('finance.index', navigate: false);
    }

    public function render(): View
    {
        return view('modules.finance.period-setup');
    }
}
