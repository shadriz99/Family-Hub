<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Core\Services\TwoFactorAuthService;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

final class ProfileSettings extends Component
{
    public string $name = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $profileMessage = null;

    public ?string $passwordMessage = null;

    public ?string $telegramLinkCode = null;

    public bool $isTelegramLinked = false;

    public ?string $telegramUsername = null;

    public ?string $telegramMessage = null;

    public string $botUsername = 'FamilyHubBot';

    public bool $twoFactorEnabled = false;

    public array $plainRecoveryCodes = [];

    public bool $showRecoveryCodesModal = false;

    public string $twoFactorPasswordConfirm = '';

    public ?string $twoFactorMessage = null;

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user !== null) {
            $this->name = $user->name;
            $this->email = $user->email;
            $this->twoFactorEnabled = (bool) $user->two_factor_email_enabled;
            $this->loadTelegramStatus();
        }
    }

    public function loadTelegramStatus(): void
    {
        $user = Auth::user();
        $household = app(\App\Core\Support\HouseholdContext::class)->currentOrNull();

        if ($user === null || $household === null) {
            return;
        }

        $this->botUsername = (string) config('telegram.bot_username', 'FamilyHubBot');

        $link = \App\Modules\Finance\Models\TelegramLink::query()
            ->where('household_id', $household->id)
            ->where('user_id', $user->id)
            ->first();

        if ($link !== null && $link->isLinked()) {
            $this->isTelegramLinked = true;
            $this->telegramUsername = $link->telegram_username;
            $this->telegramLinkCode = null;
        } elseif ($link !== null && $link->link_code && $link->link_code_expires_at && $link->link_code_expires_at->isFuture()) {
            $this->isTelegramLinked = false;
            $this->telegramLinkCode = $link->link_code;
        } else {
            $this->isTelegramLinked = false;
            $this->telegramLinkCode = null;
        }
    }

    public function generateTelegramCode(): void
    {
        $user = Auth::user();
        $household = app(\App\Core\Support\HouseholdContext::class)->currentOrNull();

        if ($user === null || $household === null) {
            return;
        }

        $link = \App\Modules\Finance\Models\TelegramLink::firstOrCreate(
            ['household_id' => $household->id, 'user_id' => $user->id],
            ['telegram_chat_id' => null]
        );

        $this->telegramLinkCode = $link->generateLinkCode();
        $this->telegramMessage = __('Kode tautan baru telah dibuat (berlaku 15 menit).');
    }

    public function disconnectTelegram(): void
    {
        $user = Auth::user();
        $household = app(\App\Core\Support\HouseholdContext::class)->currentOrNull();

        if ($user === null || $household === null) {
            return;
        }

        \App\Modules\Finance\Models\TelegramLink::query()
            ->where('household_id', $household->id)
            ->where('user_id', $user->id)
            ->update([
                'telegram_chat_id' => null,
                'telegram_username' => null,
                'link_code' => null,
                'link_code_expires_at' => null,
                'pending_draft' => null,
            ]);

        $this->isTelegramLinked = false;
        $this->telegramUsername = null;
        $this->telegramLinkCode = null;
        $this->telegramMessage = __('Sambungan Telegram berhasil diputuskan.');
    }

    public function updateProfile(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user === null) {
            return;
        }

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->getKey()),
            ],
        ]);

        $user->name = trim($this->name);
        $user->email = mb_strtolower(trim($this->email));
        $user->save();

        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties(['name' => $user->name, 'email' => $user->email])
            ->log('core.profile.updated');

        $this->profileMessage = __('Informasi profil berhasil diperbarui.');
    }

    public function updatePassword(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user === null) {
            return;
        }

        $this->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->password = Hash::make($this->password);
        $user->save();

        $this->reset(['current_password', 'password', 'password_confirmation']);

        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->log('core.profile.password_changed');

        $this->passwordMessage = __('Kata sandi berhasil diperbarui.');
    }

    public function enableTwoFactor(TwoFactorAuthService $twoFactorService): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null) {
            return;
        }

        $this->validate([
            'twoFactorPasswordConfirm' => ['required', 'string', 'current_password'],
        ]);

        $this->plainRecoveryCodes = $twoFactorService->enableTwoFactor($user);
        $this->twoFactorEnabled = true;
        $this->showRecoveryCodesModal = true;
        $this->reset('twoFactorPasswordConfirm');
        $this->twoFactorMessage = __('Autentikasi dua faktor berhasil diaktifkan. Simpan kode pemulihan berikut di tempat yang aman.');
    }

    public function disableTwoFactor(TwoFactorAuthService $twoFactorService): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null) {
            return;
        }

        $this->validate([
            'twoFactorPasswordConfirm' => ['required', 'string', 'current_password'],
        ]);

        $twoFactorService->disableTwoFactor($user);
        $this->twoFactorEnabled = false;
        $this->plainRecoveryCodes = [];
        $this->showRecoveryCodesModal = false;
        $this->reset('twoFactorPasswordConfirm');
        $this->twoFactorMessage = __('Autentikasi dua faktor berhasil dinonaktifkan.');
    }

    public function closeRecoveryModal(): void
    {
        $this->showRecoveryCodesModal = false;
    }

    public function render(): View
    {
        return view('livewire.settings.profile-settings')
            ->layout('components.app-layout', ['title' => __('Profil & Keamanan')]);
    }
}
