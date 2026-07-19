<x-mail::message>
# Confirm your email

Thanks for signing up for DeQode. Click the button below to verify your email and finish creating your account.

<x-mail::button :url="$verifyUrl">
Verify email
</x-mail::button>

This link expires {{ $intent->expires_at->diffForHumans() }}. If you did not request this, you can ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
