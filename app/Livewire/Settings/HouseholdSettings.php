<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Core\Actions\InviteHouseholdMember;
use App\Core\Actions\RemoveHouseholdMember;
use App\Core\Actions\RevokeHouseholdInvitation;
use App\Core\Actions\TransferHouseholdOwnership;
use App\Core\Enums\MemberRole;
use App\Core\Models\Household;
use App\Core\Models\HouseholdInvitation;
use App\Core\Models\HouseholdMember;
use App\Core\Services\HouseholdExportService;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class HouseholdSettings extends Component
{
    public string $household_name = '';

    public string $timezone = 'Asia/Jakarta';

    public int $finance_period_start_day = 1;

    public string $partner_email = '';

    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public ?string $latestInvitationLink = null;

    public function mount(): void
    {
        $household = $this->household();

        if ($household !== null) {
            $this->household_name = $household->name;
            $this->timezone = $household->timezone;
            $this->finance_period_start_day = $household->finance_period_start_day;
        }
    }

    public function updateHousehold(): void
    {
        $household = $this->household();
        /** @var User|null $user */
        $user = Auth::user();

        if ($household === null || $user === null) {
            return;
        }

        Gate::forUser($user)->authorize('update', $household);

        $this->validate([
            'household_name' => ['required', 'string', 'max:100'],
            'timezone' => ['required', Rule::in(timezone_identifiers_list())],
            'finance_period_start_day' => ['required', 'integer', 'min:1', 'max:28'],
        ]);

        $startDayChanged = $household->finance_period_start_day !== $this->finance_period_start_day;

        DB::transaction(function () use ($household, $user, $startDayChanged): void {
            $household->name = trim($this->household_name);
            $household->timezone = $this->timezone;
            $household->finance_period_start_day = $this->finance_period_start_day;
            $household->save();

            if ($startDayChanged) {
                $todayDate = now($this->timezone)->toDateString();
                $existing = $household->financePeriodSettings()
                    ->whereDate('effective_on', $todayDate)
                    ->first();

                if ($existing !== null) {
                    $existing->start_day = $this->finance_period_start_day;
                    $existing->save();
                } else {
                    $household->financePeriodSettings()->create([
                        'start_day' => $this->finance_period_start_day,
                        'effective_on' => $todayDate,
                    ]);
                }
            }

            activity()
                ->performedOn($household)
                ->causedBy($user)
                ->withProperties([
                    'name' => $household->name,
                    'timezone' => $household->timezone,
                    'finance_period_start_day' => $household->finance_period_start_day,
                ])
                ->log('core.household.updated');
        });

        $this->feedbackMessage = __('Pengaturan keluarga berhasil diperbarui.');
    }

    public function invitePartner(InviteHouseholdMember $action): void
    {
        $household = $this->household();
        /** @var User|null $user */
        $user = Auth::user();

        if ($household === null || $user === null) {
            return;
        }

        $this->validate([
            'partner_email' => ['required', 'string', 'email', 'max:255'],
        ]);

        try {
            $issued = $action->handle($user, $household, $this->partner_email);
            $this->latestInvitationLink = route('invitation.show', $issued->token);
            $this->partner_email = '';
            $this->feedbackMessage = __('Undangan berhasil dikirim ke pasangan Anda. Anda dapat langsung menyalin tautannya di bawah.');
            $this->errorMessage = null;
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function cancelInvitation(string $invitationId, RevokeHouseholdInvitation $action): void
    {
        $household = $this->household();
        /** @var User|null $user */
        $user = Auth::user();

        if ($household === null || $user === null) {
            return;
        }

        $invitation = $household->invitations()->findOrFail($invitationId);

        try {
            $action->handle($user, $household, $invitation);
            $this->feedbackMessage = __('Undangan berhasil dibatalkan.');
            $this->errorMessage = null;
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function removePartner(string $partnerUserId, RemoveHouseholdMember $action): void
    {
        $household = $this->household();
        /** @var User|null $user */
        $user = Auth::user();

        if ($household === null || $user === null) {
            return;
        }

        $target = User::findOrFail($partnerUserId);

        try {
            $action->handle($user, $household, $target);
            $this->feedbackMessage = __('Partner berhasil dihapus dari household.');
            $this->errorMessage = null;
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function transferOwnership(string $partnerUserId, TransferHouseholdOwnership $action): void
    {
        $household = $this->household();
        /** @var User|null $user */
        $user = Auth::user();

        if ($household === null || $user === null) {
            return;
        }

        $target = User::findOrFail($partnerUserId);

        try {
            $action->handle($user, $household, $target);
            $this->feedbackMessage = __('Kepemilikan household berhasil dialihkan.');
            $this->errorMessage = null;
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function leaveHousehold(RemoveHouseholdMember $action): void
    {
        $household = $this->household();
        /** @var User|null $user */
        $user = Auth::user();

        if ($household === null || $user === null) {
            return;
        }

        try {
            $action->handle($user, $household, $user);
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();
            $this->redirect('/', navigate: true);
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function exportData(HouseholdExportService $service): BinaryFileResponse
    {
        $household = $this->household();
        /** @var User|null $user */
        $user = Auth::user();

        if ($household === null || $user === null) {
            abort(403);
        }

        Gate::forUser($user)->authorize('view', $household);

        $zipPath = $service->exportToZip($household);

        activity()
            ->performedOn($household)
            ->causedBy($user)
            ->log('core.household.exported');

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    private function household(): ?Household
    {
        return app(HouseholdContext::class)->currentOrNull();
    }

    public function render(): View
    {
        $household = $this->household();
        /** @var User|null $user */
        $user = Auth::user();

        /** @var Collection<int, HouseholdMember> $members */
        $members = $household !== null
            ? $household->members()->with('user')->orderBy('slot')->get()
            : new Collection();

        /** @var Collection<int, HouseholdInvitation> $pendingInvitations */
        $pendingInvitations = $household !== null
            ? $household->invitations()
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->latest()
                ->get()
            : new Collection();

        $currentMember = $members->firstWhere('user_id', $user?->getKey());
        $isOwner = $currentMember?->role === MemberRole::Owner;
        $isPartner = $currentMember?->role === MemberRole::Partner;
        $partnerMember = $members->firstWhere('role', MemberRole::Partner);

        return view('livewire.settings.household-settings', [
            'household' => $household,
            'members' => $members,
            'pendingInvitations' => $pendingInvitations,
            'isOwner' => $isOwner,
            'isPartner' => $isPartner,
            'partnerMember' => $partnerMember,
            'timezones' => timezone_identifiers_list(),
        ])->layout('components.app-layout', ['title' => __('Keluarga & Anggota')]);
    }
}
