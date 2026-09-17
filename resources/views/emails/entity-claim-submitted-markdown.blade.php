@component('mail::message')
**{{ $claim->user->name }}** ({{ $claim->user->email }}) is asking to take over **[{{ $claim->entity->name }}]({{ $entityUrl }})**.

**How they're affiliated**

{{ $claim->message }}

@if ($claim->evidence_url)
**Evidence:** {{ $claim->evidence_url }}
@endif

@if ($claim->entity->owners->isNotEmpty())
Approving will remove the current owners: {{ $claim->entity->owners->pluck('name')->implode(', ') }}.
@endif

@component('mail::button', ['url' => $reviewUrl])
Review claims
@endcomponent

{{ $site }}
@endcomponent
