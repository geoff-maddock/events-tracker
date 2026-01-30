@extends('layouts.app-tw')

@section('title', "This Week's Events")

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <h1 class="text-3xl font-bold text-foreground">This Week's Events</h1>

        <div class="flex flex-wrap gap-2 mt-4 sm:mt-0">
            <x-ui.button variant="secondary" href="{{ route('events.index') }}">
                <i class="bi bi-list-ul mr-2"></i>Event Index
            </x-ui.button>
            <x-ui.button variant="secondary" href="{{ route('calendar') }}">
                <i class="bi bi-calendar3 mr-2"></i>Calendar
            </x-ui.button>
            @auth
            <x-ui.button variant="default" href="{{ route('events.create') }}">
                <i class="bi bi-plus-lg mr-2"></i>Add Event
            </x-ui.button>
            <x-ui.button variant="default" href="{{ route('series.create') }}">
                <i class="bi bi-plus-lg mr-2"></i>Add Series
            </x-ui.button>
            @endauth
        </div>
    </div>

    @php $today = \Carbon\Carbon::now(); @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        @for ($i = 0; $i < 6; $i++)
            @php $day = \Carbon\Carbon::parse($today)->addDay($i); @endphp
            <div class="card-tw">
                <div class="bg-primary text-primary-foreground px-4 py-3 rounded-t-lg">
                    <h3 class="font-semibold text-center">
                        @if ($i == 0)
                            Today
                        @else
                            {{ $day->format('l') }}
                        @endif
                    </h3>
                    <p class="text-xs text-center opacity-80">{{ $day->format('M j') }}</p>
                </div>

                <div class="p-4 space-y-4">
                    @php $events = App\Models\Event::starting($day->format('Y-m-d'))->visible(auth()->user())->get(); @endphp

                    @if (count($events) > 0)
                        <div>
                            <h4 class="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-2">Events</h4>
                            <ul class="space-y-2">
                                @foreach ($events as $event)
                                    <li class="text-sm">
                                        <a href="{{ route('events.show', $event->slug) }}" class="text-foreground hover:text-primary transition-colors font-medium">
                                            {{ $event->name }}
                                        </a>
                                        @if ($event->start_at)
                                            <div class="text-xs text-muted-foreground">
                                                {{ $event->start_at->format('g:i A') }}
                                                @if ($event->venue)
                                                    @ {{ $event->venue->name }}
                                                @endif
                                            </div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <p class="text-sm text-muted-foreground italic">No events</p>
                    @endif

                    @php $series = App\Models\Series::byNextDate($day->format('Y-m-d')); @endphp

                    @if (count($series) > 0)
                        <div>
                            <h4 class="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-2">Series</h4>
                            <ul class="space-y-2">
                                @php $type = null; @endphp
                                @foreach ($series as $s)
                                    @if ($type !== $s->occurrence_type_id)
                                        @php $type = $s->occurrence_type_id; @endphp
                                        <li class="text-xs font-semibold text-primary mt-2 first:mt-0">
                                            {{ $s->occurrenceType->name }}
                                        </li>
                                    @endif
                                    <li class="text-sm">
                                        <a href="{{ route('series.show', $s->slug) }}" class="text-foreground hover:text-primary transition-colors">
                                            {{ $s->name }}
                                        </a>
                                        @if ($s->venue)
                                            <div class="text-xs text-muted-foreground">
                                                @ {{ $s->venue->name }}
                                            </div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <p class="text-sm text-muted-foreground italic">No series</p>
                    @endif
                </div>
            </div>
        @endfor
    </div>
</div>
@stop
