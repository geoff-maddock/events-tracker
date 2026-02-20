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
                <span class="text-primary">- {{ $slug }}</span>
            @endif
            @if(isset($tag))
                <span class="text-primary">- {{ $tag->name }}</span>
            @endif
            @if(isset($related))
                <span class="text-primary">- {{ $related->name }}</span>
            @endif
        </h1>
    </div>

    <!-- Filters Section -->
    <div class="mb-6">
        <button id="filters-toggle-btn" class="inline-flex items-center px-4 py-2 bg-accent text-foreground border border-primary rounded-lg hover:bg-accent/80 transition-colors">
            <i class="bi bi-funnel mr-2"></i>
            <span id="filters-toggle-text">@if(isset($hasFilter) && $hasFilter) Hide @else Show @endif Filters</span>
            <i class="bi bi-chevron-down ml-2 transition-transform @if(isset($hasFilter) && $hasFilter) rotate-180 @endif" id="filters-chevron"></i>
        </button>
        
        <!-- Active Filters Badges (shown when filters are hidden) -->
        @if(isset($hasFilter) && $hasFilter)
        <div id="active-filters-badges" class="@if(isset($hasFilter) && $hasFilter) hidden @endif inline-flex flex-wrap items-center gap-2 ml-4">
            @if(!empty($filters['name']))
            <span class="px-3 py-1 text-sm bg-muted text-muted-foreground rounded-lg border border-border">
                Name: {{ $filters['name'] }}
            </span>
            @endif
            @if(!empty($filters['venue']))
            <span class="px-3 py-1 text-sm bg-muted text-muted-foreground rounded-lg border border-border">
                Venue: {{ $filters['venue'] }}
            </span>
            @endif
            @if(!empty($filters['tag']))
            <span class="px-3 py-1 text-sm bg-muted text-muted-foreground rounded-lg border border-border">
                Tag: {{ collect((array) $filters['tag'])->filter()->map(fn ($tag) => $tagOptions[$tag] ?? $tag)->implode(', ') }}
            </span>
            @endif
            @if(!empty($filters['related']))
            <span class="px-3 py-1 text-sm bg-muted text-muted-foreground rounded-lg border border-border">
                Entity: {{ $filters['related'] }}
            </span>
            @endif
            @if(!empty($filters['event_type']))
            <span class="px-3 py-1 text-sm bg-muted text-muted-foreground rounded-lg border border-border">
                Type: {{ $filters['event_type'] }}
            </span>
            @endif
        </div>
        @endif
    </div>

    <!-- Filter Panel -->
    <div id="filter-panel" class="@if(!isset($hasFilter) || !$hasFilter) hidden @endif bg-card border border-border rounded-lg p-4 mb-6 overflow-hidden">
        <form method="GET" action="{{ route('calendar') }}">
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
                    {!! Form::select('filters[tag][]', array_filter($tagOptions ?? [], fn($key) => $key !== '', ARRAY_FILTER_USE_KEY), ($filters['tag'] ?? null),
                    [
                        'data-theme' => 'tailwind',
                        'class' => 'form-select-tw select2',
                        'data-placeholder' => 'Select tags',
                        'id' => 'filter_tag',
                        'multiple' => true
                  ])
                    !!}
                </div>

                <!-- Venue Filter -->
                <div class="min-w-0">
                    <label for="filter_venue" class="block text-sm font-medium text-muted-foreground mb-1">Venue</label>
                    {!! Form::select('filter_venue', $venueOptions ?? [''=>''], ($filters['venue'] ?? null),
                    [
                        'data-theme' => 'tailwind',
                        'class' => 'form-select-tw select2',
                        'data-placeholder' => 'Select a venue',
                        'name' => 'filters[venue]',
                        'id' => 'filter_venue'
                    ])
                    !!}
                </div>

                <!-- Related Entity Filter -->
                <div class="min-w-0">
                    <label for="filter_related" class="block text-sm font-medium text-muted-foreground mb-1">Related Entity</label>
                    {!! Form::select('filter_related', $relatedOptions ?? [''=>''], ($filters['related'] ?? null),
                    [
                        'data-theme' => 'tailwind',
                        'class' => 'form-select-tw select2',
                        'data-placeholder' => 'Select an entity',
                        'name' => 'filters[related]',
                        'id' => 'filter_related'
                    ])
                    !!}
                </div>

                <!-- Event Type Filter -->
                <div class="min-w-0">
                    <label for="filter_event_type" class="block text-sm font-medium text-muted-foreground mb-1">Type</label>
                    {!! Form::select('filter_event_type', $eventTypeOptions ?? [''=>''], ($filters['event_type'] ?? null),
                    [
                        'data-theme' => 'tailwind',
                        'class' => 'form-select-tw select2',
                        'data-placeholder' => 'Select a type',
                        'name' => 'filters[event_type]',
                        'id' => 'filter_event_type'
                    ])
                    !!}
                </div>
            </div>

            <!-- Filter Actions -->
            <div class="flex gap-2 mt-4">
                <button type="submit" class="px-4 py-2 bg-accent text-foreground border border-primary rounded-lg hover:bg-accent/80 transition-colors">
                    Apply
                </button>
                <a href="{{ route('calendar') }}" class="inline-flex items-center px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="bg-card rounded-lg border border-border shadow-sm p-4">
        <div id='calendar'></div>
    </div>
</div>
@stop

@section('footer')
<script>
    // Toggle filter section with localStorage persistence
    document.addEventListener('DOMContentLoaded', function() {
        const filterToggleBtn = document.getElementById('filters-toggle-btn');
        const filterPanel = document.getElementById('filter-panel');
        const filterChevron = document.getElementById('filters-chevron');
        const filterToggleText = document.getElementById('filters-toggle-text');
        const activeBadges = document.getElementById('active-filters-badges');
        
        if (filterToggleBtn && filterPanel && filterChevron && filterToggleText) {
            // Check localStorage for saved state or keep open if filters are active
            const hasActiveFilters = {{ isset($hasFilter) && $hasFilter ? 'true' : 'false' }};
            const isCollapsed = localStorage.getItem('calendarFiltersCollapsed') === 'true' && !hasActiveFilters;
            
            // Set initial state
            if (!isCollapsed || hasActiveFilters) {
                filterPanel.classList.remove('hidden');
                filterChevron.classList.add('rotate-180');
                filterToggleText.textContent = 'Hide Filters';
                if (activeBadges) activeBadges.classList.add('hidden');
            }

            // Toggle functionality
            filterToggleBtn.addEventListener('click', function() {
                const willBeCollapsed = !filterPanel.classList.contains('hidden');
                
                filterPanel.classList.toggle('hidden');
                filterChevron.classList.toggle('rotate-180');

                if (willBeCollapsed) {
                    filterToggleText.textContent = 'Show Filters';
                    if (activeBadges) activeBadges.classList.remove('hidden');
                    localStorage.setItem('calendarFiltersCollapsed', 'true');
                } else {
                    filterToggleText.textContent = 'Hide Filters';
                    if (activeBadges) activeBadges.classList.add('hidden');
                    localStorage.setItem('calendarFiltersCollapsed', 'false');
                }
            });
        }
    });

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
            events: {!! $eventList !!},
            height: getCalendarHeight(),
            initialDate: '{{ $initialDate }}',
            eventDisplay: 'block',
            eventTimeFormat: {
                hour: 'numeric',
                minute: '2-digit',
                meridiem: 'short'
            },
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
</script>
@include('partials.filter-js')
@endsection
