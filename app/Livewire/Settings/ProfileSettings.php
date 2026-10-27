<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

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

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user !== null) {
            $this->name = $user->name;
            $this->email = $user->email;
        }
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

    public function render(): View
    {
        return view('livewire.settings.profile-settings')
            ->layout('components.app-layout', ['title' => __('Profil & Keamanan')]);
    }
}
