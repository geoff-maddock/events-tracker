@component('mail::message')

# Instagram Post Failure

An automated Instagram post attempt failed for the following event:

**Event ID:** {{ $eventId }}  
**Event Name:** {{ $eventName }}  
**Error Message:** {{ $errorMessage }}

Please review the event and Instagram configuration to troubleshoot the issue.

@component('mail::button', ['url' => config('app.url') . '/events/' . $eventId])
View Event
@endcomponent

Thanks,<br>
{{ $site }}

@endcomponent
