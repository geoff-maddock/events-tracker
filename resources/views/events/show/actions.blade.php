@if ($user && (Auth::user()->id == $event->user->id || $user->id == Config::get('app.superuser') ) )
    <a href="{!! route('events.edit', ['id' => $event->id]) !!}" class="btn btn-primary">Edit Event</a>
@endif

@if (!$event->series_id && $user && (Auth::user()->id == $event->user->id || $user->id == Config::get('app.superuser') ) )
    <a href="{!! route('events.createSeries', ['id' => $event->id]) !!}" title="Create an event series based on this event." class="btn btn-primary"><span class='glyphicon glyphicon-fire'></span> Create Series</a>
@endif

@if (!$thread && $user && (Auth::user()->id == $event->user->id || $user->id == Config::get('app.superuser')) )
    <a href="{!! route('events.createThread', ['id' => $event->id]) !!}" title="Create an thread related to this event." class="btn btn-primary"><span class='glyphicon glyphicon-comment'></span> Create Thread</a>
@endif

<a href="{!! URL::route('events.index') !!}" class="btn btn-info">Return to list</a>
