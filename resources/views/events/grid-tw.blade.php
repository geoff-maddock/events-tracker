@extends('app')

@section('title', 'Events - Grid')

@section('content')

<div class="flex flex-col gap-6">
    <!-- Header & Actions -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <h1 class="text-2xl font-bold text-white flex items-center gap-2">
            Events
            @include('events.crumbs-tw')
        </h1>
        @include('events.index._actions-tw', ['user' => $user])
    </div>

    <!-- Filters Section -->
    <div class="bg-dark-surface rounded-lg border border-dark-border p-4">
        <div class="flex justify-between items-center mb-4">
            <button id="filters-toggle-btn" class="flex items-center gap-2 text-primary hover:text-primary-hover font-medium transition-colors">
                <i id="filters-icon" class="bi {{ $hasFilter ? 'bi-chevron-up' : 'bi-chevron-down' }}"></i>
                Filters
            </button>
            
            <!-- Sort Controls (Visible on Desktop) -->
            <div class="hidden lg:flex items-center gap-3">
                <a href="{{ action('EventsController@rppReset') }}" class="text-gray-400 hover:text-white transition-colors" title="Reset">
                    <i class="bi bi-arrow-clockwise"></i>
                </a>
                <!-- We'll use the existing form structure but styled -->
                <form action="{{ url()->current() }}" method="GET" class="flex items-center gap-2">
                    {!! Form::select('limit', $limitOptions, ($limit ?? 24), ['class' =>'bg-dark-card border border-dark-border text-white text-sm rounded px-2 py-1 focus:outline-none focus:ring-1 focus:ring-primary auto-submit']) !!}
                    {!! Form::select('sort', $sortOptions, ($sort ?? 'events.start_at'), ['class' =>'bg-dark-card border border-dark-border text-white text-sm rounded px-2 py-1 focus:outline-none focus:ring-1 focus:ring-primary auto-submit'])!!}
                    {!! Form::select('direction', $directionOptions, ($direction ?? 'desc'), ['class' =>'bg-dark-card border border-dark-border text-white text-sm rounded px-2 py-1 focus:outline-none focus:ring-1 focus:ring-primary auto-submit']) !!}
                </form>
            </div>
        </div>

        <!-- Filter Form -->
        <div id="filters-content" class="{{ $hasFilter ? '' : 'hidden' }} transition-all duration-300">
            {!! Form::open(['route' => ['events.grid'], 'name' => 'filters', 'method' => 'POST']) !!}
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                <!-- Name -->
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-300">Name</label>
                    {!! Form::text('filter_name', (isset($filters['name']) ? $filters['name'] : NULL), [
                        'class' => 'w-full px-3 py-2 bg-dark-card border border-dark-border rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent',
                        'placeholder' => 'Event name'
                    ]) !!}
                </div>

                <!-- Venue -->
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-300">Venue</label>
                    {!! Form::select('filter_venue', $venueOptions, (isset($filters['venue']) ? $filters['venue'] : NULL), [
                        'class' => 'w-full px-3 py-2 bg-dark-card border border-dark-border rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent select2',
                        'placeholder' => 'Select a venue'
                    ]) !!}
                </div>

                <!-- Tag -->
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-300">Tag</label>
                    {!! Form::select('filter_tag', $tagOptions, (isset($filters['tag']) ? $filters['tag'] : NULL), [
                        'class' => 'w-full px-3 py-2 bg-dark-card border border-dark-border rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent select2',
                        'placeholder' => 'Select a tag'
                    ]) !!}
                </div>

                <!-- Related Entity -->
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-300">Related Entity</label>
                    {!! Form::select('filter_related', $relatedOptions, (isset($filters['related']) ? $filters['related'] : NULL), [
                        'class' => 'w-full px-3 py-2 bg-dark-card border border-dark-border rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent select2',
                        'placeholder' => 'Select an entity'
                    ]) !!}
                </div>

                <!-- Type -->
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-300">Type</label>
                    {!! Form::select('filter_event_type', $eventTypeOptions, (isset($filters['event_type']) ? $filters['event_type'] : NULL), [
                        'class' => 'w-full px-3 py-2 bg-dark-card border border-dark-border rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent select2',
                        'placeholder' => 'Select a type'
                    ]) !!}
                </div>

                <!-- Date Range -->
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-300">Date Range</label>
                    <div class="flex gap-2">
                        {!! Form::date('start_at[start]', (isset($filters['start_at']['start']) ? $filters['start_at']['start'] : NULL), [
                            'class' => 'w-full px-2 py-2 bg-dark-card border border-dark-border rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent'
                        ]) !!}
                        {!! Form::date('start_at[end]', (isset($filters['start_at']['end']) ? $filters['start_at']['end'] : NULL), [
                            'class' => 'w-full px-2 py-2 bg-dark-card border border-dark-border rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent'
                        ]) !!}
                    </div>
                </div>
            </div>

            <div class="flex gap-3 pt-4 border-t border-dark-border mt-4">
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-hover transition-colors font-medium">
                    Apply Filters
                </button>
                <a href="{{ route('events.reset', ['redirect' => 'events.grid', 'key' => 'internal_event_grid']) }}" class="px-4 py-2 bg-dark-card text-white border border-dark-border rounded-lg hover:bg-dark-border transition-colors font-medium">
                    Reset
                </a>
            </div>
            {!! Form::close() !!}
        </div>
    </div>

    <!-- Grid Content -->
    @if (isset($events) && count($events) > 0)
        <div class="grid gap-4 w-full" style="grid-template-columns: repeat(auto-fill, minmax(max(120px, calc((100% - 15 * 1rem) / 16)), 1fr));">
            @php $lastDate = ''; @endphp
            @foreach ($events as $event)
                @php
                    $currentDate = $event->start_at->format('Y-m-d');
                    $showDateBar = $currentDate !== $lastDate;
                    if ($showDateBar) {
                        $lastDate = $currentDate;
                    }
                    $isWeekend = $event->start_at->isWeekend();
                    $dateLabel = $event->start_at->format('D, M j, Y');
                @endphp
                @include('events.cell-compact-tw', [
                    'event' => $event,
                    'showDateBar' => $showDateBar,
                    'dateLabel' => $dateLabel,
                    'isWeekend' => $isWeekend
                ])
            @endforeach
        </div>
        
        <div class="mt-6">
            {!! $events->render() !!}
        </div>
    @else
        <div class="text-center py-12 bg-dark-surface rounded-lg border border-dark-border">
            <i class="bi bi-calendar-x text-4xl text-gray-500 mb-3 block"></i>
            <p class="text-gray-400">No events found matching your criteria.</p>
        </div>
    @endif
</div>

@stop

@section('footer')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('filters-toggle-btn');
        const content = document.getElementById('filters-content');
        const icon = document.getElementById('filters-icon');

        if(toggleBtn && content && icon) {
            toggleBtn.addEventListener('click', function() {
                content.classList.toggle('hidden');
                if(content.classList.contains('hidden')) {
                    icon.classList.remove('bi-chevron-up');
                    icon.classList.add('bi-chevron-down');
                } else {
                    icon.classList.remove('bi-chevron-down');
                    icon.classList.add('bi-chevron-up');
                }
            });
        }
        
        // Initialize Select2 if available
        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
            jQuery('.select2').select2({
                theme: "bootstrap-5",
                width: '100%'
            });
        }
    });
</script>
@include('partials.filter-js')
@endsection