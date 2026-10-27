<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Core\Actions\InviteNewHousehold;
use App\Core\Models\Household;
use App\Core\Models\HouseholdInvitation;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class AdminDashboard extends Component
{
    public string $invite_email = '';

    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public ?string $latestInvitationLink = null;

    public function inviteNewHousehold(InviteNewHousehold $action): void
    {
        /** @var User|null $admin */
        $admin = Auth::user();

        if ($admin === null || ! $admin->is_system_admin) {
            abort(403);
        }

        $this->validate([
            'invite_email' => ['required', 'string', 'email', 'max:255'],
        ]);

        try {
            $issued = $action->handle($admin, $this->invite_email);
            $this->latestInvitationLink = route('invitation.show', $issued->token);
            $this->invite_email = '';
            $this->feedbackMessage = __('Undangan household baru berhasil dikirim. Anda dapat langsung menyalin tautannya di bawah.');
            $this->errorMessage = null;
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function revokeInvitation(string $invitationId): void
    {
        /** @var User|null $admin */
        $admin = Auth::user();

        if ($admin === null || ! $admin->is_system_admin) {
            abort(403);
        }

        /** @var HouseholdInvitation $invitation */
        $invitation = HouseholdInvitation::whereNull('household_id')->findOrFail($invitationId);

        $invitation->revoked_at = now()->toImmutable();
        $invitation->save();

        activity()
            ->causedBy($admin)
            ->withProperties(['invitation_id' => $invitation->getKey(), 'email' => $invitation->email])
            ->log('core.admin.new_household_invitation_revoked');

        $this->feedbackMessage = __('Undangan household berhasil dibatalkan.');
    }

    public function toggleUserStatus(string $userId): void
    {
        /** @var User|null $admin */
        $admin = Auth::user();

        if ($admin === null || ! $admin->is_system_admin) {
            abort(403);
        }

        if ($admin->getKey() === $userId) {
            $this->errorMessage = __('Anda tidak dapat menonaktifkan akun sendiri.');

            return;
        }

        /** @var User $target */
        $target = User::findOrFail($userId);

        if ($target->is_system_admin) {
            $this->errorMessage = __('Tidak dapat menonaktifkan sesama administrator.');

            return;
        }

        if ($target->isDisabled()) {
            $target->disabled_at = null;
            $target->save();

            activity()
                ->performedOn($target)
                ->causedBy($admin)
                ->log('core.admin.user_enabled');

            $this->feedbackMessage = __('Pengguna berhasil diaktifkan kembali.');
        } else {
            $target->disabled_at = now();
            $target->save();

            activity()
                ->performedOn($target)
                ->causedBy($admin)
                ->log('core.admin.user_disabled');

            $this->feedbackMessage = __('Pengguna berhasil dinonaktifkan.');
        }

        $this->errorMessage = null;
    }

    public function render(): View
    {
        /** @var Collection<int, Household> $households */
        $households = Household::withCount('members')->latest()->get();

        /** @var Collection<int, User> $users */
        $users = User::latest()->get();

        /** @var Collection<int, HouseholdInvitation> $pendingInvitations */
        $pendingInvitations = HouseholdInvitation::whereNull('household_id')
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->get();

        return view('livewire.admin.admin-dashboard', [
            'households' => $households,
            'users' => $users,
            'pendingInvitations' => $pendingInvitations,
        ])->layout('layouts.admin', ['title' => __('Panel Administrator')]);
    }
}
