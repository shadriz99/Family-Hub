<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Core\Actions\AcceptInvitation;
use App\Core\Actions\RegisterInvitedPartner;
use App\Core\Enums\InvitationType;
use App\Core\Models\HouseholdInvitation;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class AcceptInvitationForm extends Component
{
    public string $token = '';

    public string $name = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $email = '';

    public string $householdName = '';

    public bool $existingAccount = false;

    public bool $signedInAsInvitee = false;

    public ?string $problem = null;

    public function mount(string $token): void
    {
        $this->token = $token;
        $invitation = $this->invitation();

        if ($invitation === null) {
            $this->problem = __('auth.invite_invalid');

            return;
        }

        $this->email = $invitation->email;
        $this->householdName = (string) $invitation->household?->name;
        $this->existingAccount = User::query()->where('email', $invitation->email)->exists();
        $this->signedInAsInvitee = $this->actorMatches($invitation);
        $this->problem = $this->problemFor($invitation);
    }

    public function submit(): void
    {
        $invitation = $this->invitation();
        $problem = $invitation === null ? __('auth.invite_invalid') : $this->problemFor($invitation);

        if ($problem !== null) {
            $this->problem = $problem;

            return;
        }

        $existing = User::query()->where('email', $invitation->email)->first();

        if ($existing !== null) {
            if (! $this->actorMatches($invitation)) {
                $this->addError('invitation', __('auth.invite_login_required'));

                return;
            }

            app(AcceptInvitation::class)->handle($existing, $this->token);
            $this->redirect('/');

            return;
        }

        $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = app(RegisterInvitedPartner::class)->handle($invitation, $this->token, $this->name, $this->password);

        Auth::login($user);
        session()->regenerate();
        $this->redirect('/');
    }

    public function render(): View
    {
        return view('livewire.auth.accept-invitation-form');
    }

    private function invitation(): ?HouseholdInvitation
    {
        return HouseholdInvitation::findByToken($this->token);
    }

    private function problemFor(HouseholdInvitation $invitation): ?string
    {
        if ($invitation->type !== InvitationType::Partner || $invitation->household === null) {
            return __('auth.invite_invalid');
        }

        if ($invitation->revoked_at !== null) {
            return __('auth.invite_invalid');
        }

        if ($invitation->accepted_at !== null) {
            return __('auth.invite_used');
        }

        if ($invitation->expires_at->isPast()) {
            return __('auth.invite_expired');
        }

        return null;
    }

    private function actorMatches(HouseholdInvitation $invitation): bool
    {
        $actor = Auth::user();

        return $actor instanceof User && strcasecmp((string) $actor->email, $invitation->email) === 0;
    }
}
