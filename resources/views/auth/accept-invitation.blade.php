<x-auth-layout :title="__('auth.invite_title')" :intro="__('auth.invite_intro')">
    <livewire:auth.accept-invitation-form :token="$token" />
</x-auth-layout>
