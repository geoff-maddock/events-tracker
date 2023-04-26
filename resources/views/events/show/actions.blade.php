@if ($user && $event->user && (Auth::user()->id == $event->user->id || $user->id == Config::get('app.superuser') ) )
    <a href="{!! route('events.edit', ['event' => $event->id]) !!}" class="btn btn-primary my-1">Edit Event</a>
@endif

@if (!$event->series_id && $event->user && $user && (Auth::user()->id == $event->user->id || $user->id == Config::get('app.superuser') ) )
    <a href="{!! route('events.createSeries', ['id' => $event->id]) !!}" title="Create an event series based on this event." class="btn btn-primary my-1">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-arrow-repeat" viewBox="0 0 16 16">
            <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/>
            <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"/>
          </svg>
           Create Series
    </a>
@endif

@if ($user && $event->user && (Auth::user()->id == $event->user->id || $user->id == Config::get('app.superuser')) )
    <a href="{!! route('events.duplicate', ['id' => $event->id]) !!}" title="Create a new event based on this event." class="btn btn-primary my-1">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard" viewBox="0 0 16 16">
            <path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1v-1z"/>
            <path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h3zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3z"/>
          </svg>
         Duplicate Event
    </a>
@endif

@if (!isset($thread) && $user && $event->user && (Auth::user()->id == $event->user->id || $user->id == Config::get('app.superuser')) )
    <a href="{!! route('events.createThread', ['id' => $event->id]) !!}" title="Create an thread related to this event." class="btn btn-primary my-1">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-chat-fill" viewBox="0 0 16 16">
            <path d="M8 15c4.418 0 8-3.134 8-7s-3.582-7-8-7-8 3.134-8 7c0 1.76.743 3.37 1.97 4.6-.097 1.016-.417 2.13-.771 2.966-.079.186.074.394.273.362 2.256-.37 3.597-.938 4.18-1.234A9.06 9.06 0 0 0 8 15z"/>
          </svg>
         Create Thread
    </a>
@endif

<a href="{!! URL::route('events.index') !!}" class="btn btn-info my-1">Return to list</a>
