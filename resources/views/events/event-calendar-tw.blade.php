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
    }
    .fc .fc-button-primary:hover {
        background-color: hsl(var(--primary) / 0.9);
    }
    .fc .fc-button-primary:not(:disabled).fc-button-active,
    .fc .fc-button-primary:not(:disabled):active {
        background-color: hsl(var(--primary) / 0.8);
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

@section('content')
<div class="max-w-7xl mx-auto">
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

    <div class="bg-card rounded-lg border border-border shadow-sm p-4">
        <div id='calendar'></div>
    </div>
</div>
@stop

@section('footer')
<script>
    // check the current viewport size
    function checkViewport() {
        if (window.innerWidth < 768) {
            return 'timeGridDay';
        } else if (window.innerWidth < 1024) {
            return 'timeGridWeek';
        } else {
            return 'dayGridMonth';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            headerToolbar: { center: 'dayGridMonth,timeGridWeek,timeGridDay' },
            initialView: checkViewport(),
            events: {!! $eventList !!},
            height: 'auto',
            aspectRatio: 1.8,
            initialDate: '{{ $initialDate }}',
            eventDisplay: 'block',
            eventTimeFormat: {
                hour: 'numeric',
                minute: '2-digit',
                meridiem: 'short'
            },
        });
        calendar.render();
    });
</script>
@endsection
