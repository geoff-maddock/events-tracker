@if ($user && $event->user && (Auth::user()->id == $event->user->id || $user->id == Config::get('app.superuser') ) )
    <x-ui.button variant="default" href="{!! route('events.show', ['event' => $event->id]) !!}">
        <i class="bi bi-eye mr-2"></i>
        Show Event
    </x-ui.button>
@endif

@if (!$event->series_id && $event->user && $user && (Auth::user()->id == $event->user->id || $user->id == Config::get('app.superuser') ) )
    <x-ui.button variant="secondary" href="{!! route('events.createSeries', ['id' => $event->id]) !!}" title="Create an event series based on this event.">
        <i class="bi bi-arrow-repeat mr-2"></i>
        Create Series
    </x-ui.button>
@endif

@if ($user && $event->user && (Auth::user()->id == $event->user->id || $user->id == Config::get('app.superuser')) )
    <x-ui.button variant="secondary" href="{!! route('events.duplicate', ['id' => $event->id]) !!}" title="Create a new event based on this event.">
        <i class="bi bi-clipboard mr-2"></i>
        Duplicate Event
    </x-ui.button>
@endif

@if (!isset($thread) && $user && $event->user && (Auth::user()->id == $event->user->id || $user->id == Config::get('app.superuser')) )
    <x-ui.button variant="secondary" href="{!! route('events.createThread', ['id' => $event->id]) !!}" title="Create a thread related to this event.">
        <i class="bi bi-chat-fill mr-2"></i>
        Create Thread
    </x-ui.button>
@endif

<x-ui.button variant="ghost" href="{!! URL::route('events.index') !!}">
    <i class="bi bi-list mr-2"></i>
    Return to list
</x-ui.button>
