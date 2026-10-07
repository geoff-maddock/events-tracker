@component('mail::message', ['unsubscribeUrl' => $unsubscribeUrl ?? null])

Hi {{ $user->name }},

We haven't seen you on {{ $site }} in a while, so we've paused your {{ $digests }} to keep from filling your inbox with mail you may not want.

If you'd still like them, one click turns them back on:

@component('mail::button', ['url' => $resumeUrl])
Keep sending my updates
@endcomponent

If not, there's nothing to do: you won't get them again unless you log in or click the button above.

Thanks,<br>
{{ $site }}
@endcomponent
