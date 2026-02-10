@component('mail::message')

# Activity Summary Report

This is your activity summary for **{{ $site }}** covering the past **{{ $days }}** day{{ $days > 1 ? 's' : '' }} ({{ $startDate->format('M j, Y') }} to {{ $endDate->format('M j, Y') }}).

## Overview

@component('mail::table')
| Category | Count |
|:---------|------:|
| Logins | {{ $counts['logins'] }} |
| Deletions | {{ $counts['deletions'] }} |
| New Users | {{ $counts['new_users'] }} |
| New Events | {{ $counts['new_events'] }} |
| New Entities | {{ $counts['new_entities'] }} |
| New Series | {{ $counts['new_series'] }} |
| Other Activities | {{ $counts['other'] }} |
| **Total Activities** | **{{ array_sum($counts) }}** |
@endcomponent

---

@if (count($summary['logins']) > 0)
## Logins ({{ $counts['logins'] }})

@foreach ($summary['logins'] as $activity)
- **{{ $activity->user_name }}** logged in on {{ $activity->created_at->format('M j, Y g:i A') }}
@endforeach

---

@endif

@if (count($summary['deletions']) > 0)
## Deletions ({{ $counts['deletions'] }})

@foreach ($summary['deletions'] as $activity)
- **{{ $activity->object_table }}**: {{ $activity->object_name }} deleted by {{ $activity->user_name }} on {{ $activity->created_at->format('M j, Y g:i A') }}
@endforeach

---

@endif

@if (count($summary['new_users']) > 0)
## New Users ({{ $counts['new_users'] }})

@foreach ($summary['new_users'] as $activity)
- **{{ $activity->object_name }}** created on {{ $activity->created_at->format('M j, Y g:i A') }}
@endforeach

---

@endif

@if (count($summary['new_events']) > 0)
## New Events ({{ $counts['new_events'] }})

@foreach ($summary['new_events'] as $activity)
- [{{ $activity->object_name }}]({{ $url }}/events/{{ $activity->object_id }}) created by {{ $activity->user_name }} on {{ $activity->created_at->format('M j, Y g:i A') }}
@endforeach

---

@endif

@if (count($summary['new_entities']) > 0)
## New Entities ({{ $counts['new_entities'] }})

@foreach ($summary['new_entities'] as $activity)
- [{{ $activity->object_name }}]({{ $url }}/entities/{{ $activity->object_id }}) created by {{ $activity->user_name }} on {{ $activity->created_at->format('M j, Y g:i A') }}
@endforeach

---

@endif

@if (count($summary['new_series']) > 0)
## New Series ({{ $counts['new_series'] }})

@foreach ($summary['new_series'] as $activity)
- [{{ $activity->object_name }}]({{ $url }}/series/{{ $activity->object_id }}) created by {{ $activity->user_name }} on {{ $activity->created_at->format('M j, Y g:i A') }}
@endforeach

---

@endif

@if (count($summary['other']) > 0)
## Other Activities ({{ $counts['other'] }})

@foreach (array_slice($summary['other'], 0, 20) as $activity)
- **{{ $activity->action ? $activity->action->name : 'Unknown' }}** {{ $activity->object_table }}: {{ $activity->object_name }} by {{ $activity->user_name }} on {{ $activity->created_at->format('M j, Y g:i A') }}
@endforeach

@if (count($summary['other']) > 20)
*... and {{ count($summary['other']) - 20 }} more activities*
@endif

---

@endif

@if (array_sum($counts) == 0)
No activities recorded during this period.
@endif

This summary was generated on {{ \Carbon\Carbon::now()->format('l, F j, Y \a\t g:i A') }}.

Thanks!  
{{ $site }}  
{{ $url }}  

<img src="{{ asset('images/arcane-city-icon-96x96.png') }}">
@endcomponent
