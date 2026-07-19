<x-mail::message>
# Welcome to DeQode

Hi {{ $user->name }},

Your account is ready.

@if ($plainPassword)
Your temporary password is:

**{{ $plainPassword }}**

Please sign in and change it when you can.
@endif

<x-mail::button :url="$loginUrl">
Sign in
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
