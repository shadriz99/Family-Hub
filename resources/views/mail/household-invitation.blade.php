<x-mail::message>
# {{ __('auth.mail_heading', ['household' => $householdName]) }}

{{ __('auth.mail_intro', ['household' => $householdName]) }}

<x-mail::button :url="$url">
{{ __('auth.mail_action') }}
</x-mail::button>

{{ __('auth.mail_expiry', ['date' => $expiresAt]) }}

{{ __('auth.mail_ignore') }}
</x-mail::message>
