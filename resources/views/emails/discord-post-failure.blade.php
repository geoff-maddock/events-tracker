@component('mail::message')

# Discord Post Failure

A post to a Discord channel failed permanently — retrying will not help.

**Channel:** {{ $targetName }}
**Webhook:** {{ $webhook }}
**Error:** {{ $errorMessage }}

@if ($wasDisabled)
This channel has been **automatically disabled** and will receive no further
posts until it is re-enabled. The usual cause is a webhook that was deleted in
Discord — create a new one and update the target with the new URL.
@else
The channel is still enabled. It will be disabled automatically if permanent
failures continue.
@endif

@component('mail::button', ['url' => rtrim(config('app.url'), '/') . '/discord-targets'])
Manage Discord Targets
@endcomponent

Thanks,<br>
{{ $site }}

@endcomponent
