@extends('layouts.app-tw')

@section('title','Event Calendar')

@section('calendar.include')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<style>
    /* FullCalendar dark mode overrides for Tailwind theme */
    .fc {
        --fc-border-color: hsl(var(--border));
        --fc-page-bg-color: hsl(var(--card));
        --fc-neutral-bg-color: hsl(var(--muted));
        --fc-today-bg-color: hsl(var(--accent));
    }
    .fc .fc-toolbar-title {
        color: hsl(var(--foreground));
    }
    .fc .fc-button-primary {
        background-color: hsl(var(--primary));
        border-color: hsl(var(--primary));
        color: hsl(var(--primary-foreground));
    }
    .fc .fc-button-primary:hover {
        background-color: hsl(var(--primary) / 0.9);
        color: hsl(var(--primary-foreground));
    }
    .fc .fc-button-primary:not(:disabled).fc-button-active,
    .fc .fc-button-primary:not(:disabled):active {
        background-color: hsl(var(--primary) / 0.8);
        color: hsl(var(--primary-foreground));
    }
    .fc .fc-button-primary:disabled {
        background-color: hsl(var(--muted));
        border-color: hsl(var(--border));
        color: hsl(var(--muted-foreground));
    }
    .fc .fc-col-header-cell-cushion,
    .fc .fc-daygrid-day-number {
        color: hsl(var(--foreground));
    }
    .fc .fc-daygrid-day.fc-day-today {
        background-color: hsl(var(--accent));
    }
    .fc-theme-standard td, .fc-theme-standard th {
        border-color: hsl(var(--border));
    }
    .fc-theme-standard .fc-scrollgrid {
        border-color: hsl(var(--border));
    }
    .fc .fc-timegrid-slot-label-cushion {
        color: hsl(var(--muted-foreground));
    }
    .fc-event {
        cursor: pointer;
    }
</style>
@endsection

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
@endsection

@section('content')
<div class="w-full">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <h1 class="text-3xl font-bold text-foreground">
            Events Calendar
            @if(isset($slug))
                <span class="text-primary">/ {{ $slug }}</span>
            @endif
            @if(isset($tag))
                <span class="text-primary">/ {{ $tag->name }}</span>
            @endif
            @if(isset($related))
                <span class="text-primary">/ {{ $related->name }}</span>
            @endif
        </h1>
        <div class="flex flex-wrap gap-2 items-center mt-3 sm:mt-0">
            <a href="{!! URL::route('events.create') !!}" class="inline-flex items-center px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors">
                <i class="bi bi-plus-lg mr-2"></i>
                Add Event
            </a>
            <div class="ml-auto relative" id="actions-menu-container">
                <button id="actions-menu-btn" class="inline-flex items-center px-3 py-2 bg-card border border-border text-muted-foreground rounded-lg hover:bg-accent transition-colors text-sm" aria-haspopup="true" aria-expanded="false">
                    <i class="bi bi-three-dots"></i>
                </button>
                <div id="actions-menu-dropdown" class="hidden absolute right-0 mt-1 w-44 bg-card border border-border rounded-lg shadow-lg z-10">
                    <a href="{!! URL::route('events.export') !!}" class="flex items-center px-4 py-2 text-sm text-muted-foreground hover:bg-accent rounded-t-lg transition-colors" target="_blank" rel="noopener noreferrer">
                        <i class="bi bi-download mr-2"></i>
                        Export TXT
                    </a>
                    <a href="{!! URL::route('events.indexIcal') !!}" class="flex items-center px-4 py-2 text-sm text-muted-foreground hover:bg-accent rounded-b-lg transition-colors" target="_blank" rel="noopener noreferrer">
                        <i class="bi bi-calendar-event mr-2"></i>
                        Export iCal
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <x-filter-panel :action="route('calendar')" method="GET" :reset-url="route('calendar')" :has-filter="!empty($hasFilter)" :open="true">
        <x-slot:badges>
            @if(!empty($filters['name']))
            <x-filter-badge>Name: {{ $filters['name'] }}</x-filter-badge>
            @endif
            @if(!empty($filters['venue']))
            <x-filter-badge>Venue: {{ $filters['venue'] }}</x-filter-badge>
            @endif
            @if(!empty($filters['tag']))
            <x-filter-badge>Tag: {{ collect((array) $filters['tag'])->filter()->map(fn ($tag) => $tagOptions[$tag] ?? $tag)->implode(', ') }}</x-filter-badge>
            @endif
            @if(!empty($filters['related']))
            <x-filter-badge>Entity: {{ $filters['related'] }}</x-filter-badge>
            @endif
            @if(!empty($filters['event_type']))
            <x-filter-badge>Type: {{ $filters['event_type'] }}</x-filter-badge>
            @endif
            @include('events.filters.audience-badges')
            @if(!empty($filters['display_type']) && $filters['display_type'] !== 'all')
            <x-filter-badge>{{ \App\Filters\EventFilters::DISPLAY_TYPES[$filters['display_type']] ?? $filters['display_type'] }}</x-filter-badge>
            @endif
        </x-slot:badges>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
            <!-- Name Filter -->
            <div class="min-w-0">
                <label for="filter_name" class="block text-sm font-medium text-muted-foreground mb-1">Name</label>
                <input type="text" 
                    name="filters[name]" 
                    id="filter_name" 
                    value="{{ $filters['name'] ?? '' }}"
                    placeholder="Event name..."
                    class="form-input-tw">
            </div>

            <!-- Tag Filter -->
            <div class="min-w-0">
                <label for="filter_tag" class="block text-sm font-medium text-muted-foreground mb-1">Tags</label>
                <x-ui.select-field name="filters[tag][]" :options="array_filter($tagOptions ?? [], fn($key) => $key !== '', ARRAY_FILTER_USE_KEY)" :selected="$filters['tag'] ?? null"
                    data-theme="tailwind" class="form-select-tw select2" data-placeholder="Select tags" id="filter_tag" multiple />
            </div>

            <!-- Venue Filter -->
            <div class="min-w-0">
                <label for="filter_venue" class="block text-sm font-medium text-muted-foreground mb-1">Venue</label>
                <x-ui.select-field name="filters[venue]" :options="$venueOptions ?? ['' => '']" :selected="$filters['venue'] ?? null"
                    data-theme="tailwind" class="form-select-tw select2" data-placeholder="Select a venue" id="filter_venue" />
            </div>

            <!-- Related Entity Filter -->
            <div class="min-w-0">
                <label for="filter_related" class="block text-sm font-medium text-muted-foreground mb-1">Related Entity</label>
                <x-ui.select-field name="filters[related]" :options="$relatedOptions ?? ['' => '']" :selected="$filters['related'] ?? null"
                    data-theme="tailwind" class="form-select-tw select2" data-placeholder="Select an entity" id="filter_related" />
            </div>

            <!-- Event Type Filter -->
            <div class="min-w-0">
                <label for="filter_event_type" class="block text-sm font-medium text-muted-foreground mb-1">Event Type</label>
                <x-ui.select-field name="filters[event_type]" :options="$eventTypeOptions ?? ['' => '']" :selected="$filters['event_type'] ?? null"
                    data-theme="tailwind" class="form-select-tw select2" data-placeholder="Select a type" id="filter_event_type" />
            </div>

            @include('events.filters.audience-fields')

            @auth
            <!-- Display Type Filter -->
            <div class="min-w-0">
                <label for="filter_display_type" class="block text-sm font-medium text-muted-foreground mb-1">Display Type</label>
                <select name="filters[display_type]" id="filter_display_type" class="form-select-tw">
                    @foreach(\App\Filters\EventFilters::DISPLAY_TYPES as $value => $label)
                    <option value="{{ $value }}" {{ ($filters['display_type'] ?? 'all') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @endauth
        </div>

        <x-slot:actions>
            @if(isset($hasFilter) && $hasFilter)
            <button type="button" id="copy-filter-url-btn" class="px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors flex items-center gap-2">
                <i class="bi bi-link-45deg"></i>
                <span>Copy Filter URL</span>
            </button>
            @endif
        </x-slot:actions>
    </x-filter-panel>

    <div class="bg-card rounded-lg border border-border shadow-sm p-4">
        <div id='calendar'></div>
    </div>
</div>
@stop

@section('footer')
@include('partials.calendar-event-content')
<script>
    // Actions menu toggle
    (function() {
        const btn = document.getElementById('actions-menu-btn');
        const dropdown = document.getElementById('actions-menu-dropdown');
        if (!btn || !dropdown) return;

        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const open = !dropdown.classList.contains('hidden');
            dropdown.classList.toggle('hidden', open);
            btn.setAttribute('aria-expanded', String(!open));
        });

        document.addEventListener('click', function() {
            dropdown.classList.add('hidden');
            btn.setAttribute('aria-expanded', 'false');
        });
    })();

    // Check the current viewport size for initial view
    function checkViewport() {
        if (window.innerWidth < 768) {
            return 'timeGridDay';
        } else if (window.innerWidth < 1024) {
            return 'timeGridWeek';
        } else {
            return 'dayGridMonth';
        }
    }

    // Calculate available height for calendar
    function getCalendarHeight() {
        // Get viewport height and subtract space for header, toolbar, padding
        var viewportHeight = window.innerHeight;
        var offset = 200; // Space for header, nav, margins, padding
        var minHeight = 500; // Minimum height
        var calculatedHeight = viewportHeight - offset;
        return Math.max(calculatedHeight, minHeight);
    }

    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            headerToolbar: { center: 'dayGridMonth,timeGridWeek,timeGridDay' },
            initialView: checkViewport(),
            // fetched per visible range with the page's filters (FullCalendar adds start/end)
            events: { url: @json($calendarFeedUrl) },
            height: getCalendarHeight(),
            initialDate: '{{ $initialDate }}',
            eventDisplay: 'block',
            // Require an event to run until at least 6 AM of the next day before it
            // renders on that day, so late-night events that spill past midnight
            // don't appear duplicated on the following calendar cell.
            nextDayThreshold: '06:00:00',
            eventTimeFormat: {
                hour: 'numeric',
                minute: '2-digit',
                meridiem: 'short'
            },
            eventContent: calendarEventContent,
        });
        calendar.render();

        // Update calendar height on window resize
        var resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                calendar.setOption('height', getCalendarHeight());
            }, 150);
        });
    });

    // Copy filter URL functionality
    document.getElementById('copy-filter-url-btn')?.addEventListener('click', function() {
        const filters = @json($filters ?? []);

        const params = new URLSearchParams();

        if (filters && Object.keys(filters).length > 0) {
            for (const [key, value] of Object.entries(filters)) {
                if (value !== null && value !== '' && value !== undefined) {
                    if (Array.isArray(value)) {
                        value.forEach(v => {
                            if (v !== null && v !== '') {
                                params.append(`filters[${key}][]`, v);
                            }
                        });
                    } else if (typeof value === 'object' && value !== null) {
                        for (const [subKey, subValue] of Object.entries(value)) {
                            if (subValue !== null && subValue !== '') {
                                params.append(`filters[${key}][${subKey}]`, subValue);
                            }
                        }
                    } else {
                        params.append(`filters[${key}]`, value);
                    }
                }
            }
        }

        const baseUrl = '{{ route('calendar') }}';
        const fullUrl = `${baseUrl}?${params.toString()}`;

        navigator.clipboard.writeText(fullUrl).then(function() {
            const btn = document.getElementById('copy-filter-url-btn');
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check2"></i><span>Copied!</span>';
            btn.classList.add('bg-green-100', 'border-green-500', 'text-green-700');

            setTimeout(function() {
                btn.innerHTML = originalContent;
                btn.classList.remove('bg-green-100', 'border-green-500', 'text-green-700');
            }, 2000);
        }).catch(function(err) {
            alert('Failed to copy URL. Please copy manually: ' + fullUrl);
            console.error('Could not copy text: ', err);
        });
    });
</script>
@endsection
