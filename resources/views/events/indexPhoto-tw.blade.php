@extends('layouts.app-tw')

@section('title', 'Events - Photos')

@section('select2.include')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
@endsection

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <h1 class="text-3xl font-bold text-foreground">Event Photos</h1>

        <div class="flex flex-wrap gap-2 mt-4 sm:mt-0">
            <x-ui.button variant="secondary" href="{{ route('events.index') }}">
                <i class="bi bi-list-ul mr-2"></i>Index
            </x-ui.button>
            <x-ui.button variant="secondary" href="{{ route('calendar') }}">
                <i class="bi bi-calendar3 mr-2"></i>Calendar
            </x-ui.button>
            <x-ui.button variant="secondary" href="{{ route('events.week') }}">
                <i class="bi bi-calendar-week mr-2"></i>Week
            </x-ui.button>
            <x-ui.button variant="default" href="{{ route('events.create') }}">
                <i class="bi bi-plus-lg mr-2"></i>Add Event
            </x-ui.button>
            <x-ui.button variant="default" href="{{ route('series.create') }}">
                <i class="bi bi-plus-lg mr-2"></i>Add Series
            </x-ui.button>
        </div>
    </div>

    <!-- Filters -->
    <div x-data="{ showFilters: {{ $hasFilter ? 'true' : 'false' }} }" class="mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <button @click="showFilters = !showFilters" class="btn-tw btn-primary-tw flex items-center gap-2">
                <i class="bi bi-funnel"></i>
                Filters
                <i class="bi" :class="showFilters ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
            </button>

            <form action="{{ url()->current() }}" method="GET" class="flex items-center gap-2">
                <a href="{{ action('EventsController@rppReset') }}" class="btn-tw btn-secondary-tw" title="Reset">
                    <i class="bi bi-arrow-clockwise"></i>
                </a>
                <select name="limit" class="input-tw text-sm w-20 auto-submit">
                    @foreach($limitOptions as $value => $label)
                        <option value="{{ $value }}" {{ ($limit ?? 24) == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="sort" class="input-tw text-sm w-32 auto-submit">
                    @foreach($sortOptions as $value => $label)
                        <option value="{{ $value }}" {{ ($sort ?? 'events.start_at') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="direction" class="input-tw text-sm w-20 auto-submit">
                    @foreach($directionOptions as $value => $label)
                        <option value="{{ $value }}" {{ ($direction ?? 'desc') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div x-show="showFilters" x-collapse class="mt-4">
            <form action="{{ route('events.grid') }}" method="POST" class="card-tw p-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Name</label>
                        <input type="text" name="filters[name]" value="{{ $filters['name'] ?? '' }}"
                            class="input-tw w-full" placeholder="Search name...">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Venue</label>
                        <select name="filters[venue]" class="select2 w-full" data-placeholder="Select a venue" data-theme="tailwind">
                            <option value=""></option>
                            @foreach($venueOptions as $value => $label)
                                <option value="{{ $value }}" {{ ($filters['venue'] ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Tag</label>
                        <select name="filters[tag]" class="select2 w-full" data-placeholder="Select a tag" data-theme="tailwind">
                            <option value=""></option>
                            @foreach($tagOptions as $value => $label)
                                <option value="{{ $value }}" {{ ($filters['tag'] ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Related Entity</label>
                        <select name="filters[related]" class="select2 w-full" data-placeholder="Select an entity" data-theme="tailwind">
                            <option value=""></option>
                            @foreach($relatedOptions as $value => $label)
                                <option value="{{ $value }}" {{ ($filters['related'] ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Type</label>
                        <select name="filters[event_type]" class="select2 w-full" data-placeholder="Select a type" data-theme="tailwind">
                            <option value=""></option>
                            @foreach($eventTypeOptions as $value => $label)
                                <option value="{{ $value }}" {{ ($filters['event_type'] ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Start Date</label>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-muted-foreground w-10">From:</span>
                                <input type="date" name="filters[start_at][start]"
                                    value="{{ $filters['start_at']['start'] ?? '' }}"
                                    class="input-tw flex-1">
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-muted-foreground w-10">To:</span>
                                <input type="date" name="filters[start_at][end]"
                                    value="{{ $filters['start_at']['end'] ?? '' }}"
                                    class="input-tw flex-1">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2 mt-4">
                    <button type="submit" class="btn-tw btn-primary-tw">
                        <i class="bi bi-check-lg mr-1"></i>Apply
                    </button>
                    <a href="{{ route('events.reset', ['redirect' => 'events.grid', 'key' => 'internal_event_grid']) }}" class="btn-tw btn-secondary-tw">
                        <i class="bi bi-x-lg mr-1"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Photo Grid -->
    @if (isset($events) && count($events) > 0)
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
            @foreach ($events as $event)
                @include('events.photo-tw', ['event' => $event])
            @endforeach
        </div>

        <div class="mt-6">
            {!! $events->links('vendor.pagination.tailwind') !!}
        </div>
    @else
        <div class="card-tw p-8 text-center text-muted-foreground">
            <i class="bi bi-image text-4xl mb-3"></i>
            <p>No event photos found</p>
        </div>
    @endif
</div>
@stop

@section('footer')
<script>
    document.querySelectorAll('.auto-submit').forEach(function(el) {
        el.addEventListener('change', function() {
            this.closest('form').submit();
        });
    });
</script>
@include('partials.filter-js')
@endsection
