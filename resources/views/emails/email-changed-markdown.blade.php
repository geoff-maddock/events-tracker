@component('mail::message')
Hello {{ $user->name }},

The email address on your {{ $site }} account was just changed, so we'll no longer send account email to this address.

If you made this change, you can ignore this message. The new address needs to be verified before you can add or edit content.

If you didn't make this change, someone else may have access to your account. Please [contact the site admin]({{ 'mailto:'.$admin_email }}) right away.

Thanks!
{{ $site }}
{{ $url }}
@endcomponent
