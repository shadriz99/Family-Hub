<x-mail::message>
@if ($isNewHousehold)
# {{ __('auth.mail_new_household_heading') }}

{{ __('auth.mail_new_household_intro') }}

<x-mail::button :url="$url">
{{ __('auth.mail_new_household_action') }}
</x-mail::button>
@else
# {{ __('auth.mail_heading', ['household' => $householdName]) }}

{{ __('auth.mail_intro', ['household' => $householdName]) }}

<x-mail::button :url="$url">
{{ __('auth.mail_action') }}
</x-mail::button>
@endif

{{ __('auth.mail_expiry', ['date' => $expiresAt]) }}

{{ __('auth.mail_ignore') }}
</x-mail::message>
