@extends('layouts.app-tw')

@section('title', 'User Events Attending')

@if (isset($past_events) && count($past_events) > 0)
    @php
        $first = $past_events[0];
        if ($primary = $first->getPrimaryPhoto()) {
            $ogImage = Storage::disk('external')->url($primary->getStorageThumbnail());
        }
    @endphp
@endif

@if (isset($future_events) && count($future_events) > 0)
    @php
        $first = $future_events[0];
        if ($primary = $first->getPrimaryPhoto()) {
            $ogImage = Storage::disk('external')->url($primary->getStorageThumbnail());
        }
    @endphp
@endif

@if (isset($ogImage))
    @section('og-image')
        {!! url('/').$ogImage !!}
    @endsection
@endif

@section('select2.include')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
@endsection

@section('content')

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-foreground mb-2">Events Attending</h1>
        <div class="text-sm text-muted-foreground">
            @include('users.crumbs')
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('users.show', ['user' => $user->id]) }}" class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
            <i class="bi bi-person mr-2"></i>
            Profile
        </a>

        @if ($signedIn && (Auth::user()->id == $user->id || Auth::user()->id == Config::get('app.superuser')))
            <a href="{{ route('users.edit', ['user' => $user->id]) }}" class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                <i class="bi bi-pencil mr-2"></i>
                Edit Profile
            </a>

            @can('grant_access')
                @if (!$user->isActive)
                    <a href="{{ route('users.activate', ['id' => $user->id]) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors confirm">
                        <i class="bi bi-check-circle mr-2"></i>
                        Activate
                    </a>
                @endif
                @if ($user->isActive)
                    <a href="{{ route('users.reminder', ['id' => $user->id]) }}" class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors confirm">
                        <i class="bi bi-bell mr-2"></i>
                        Send Reminder
                    </a>
                @endif
            @endcan

            @can('impersonate_user')
                <a href="{{ route('user.impersonate', ['user' => $user->id]) }}" title="Impersonate user" class="inline-flex items-center px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors confirm">
                    <i class="bi bi-person-badge mr-2"></i>
                    Impersonate
                </a>
            @endcan

            @if ($user->isActive)
                <a href="{{ route('users.weekly', ['id' => $user->id]) }}" class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors confirm">
                    <i class="bi bi-envelope mr-2"></i>
                    Send Weekly Update
                </a>
            @endif

            <a href="{{ url('/password/reset') }}" class="inline-flex items-center px-4 py-2 bg-muted text-muted-foreground rounded-lg hover:bg-muted/80 transition-colors">
                <i class="bi bi-key mr-2"></i>
                Reset Password
            </a>
        @endif

        <a href="{{ URL::route('users.index') }}" class="inline-flex items-center px-4 py-2 bg-muted text-muted-foreground rounded-lg hover:bg-muted/80 transition-colors">
            <i class="bi bi-arrow-left mr-2"></i>
            Return to list
        </a>
    </div>

    <!-- Filters Panel -->
    <div class="card-tw mb-6">
        <div class="p-4">
            <button type="button" id="filters-toggle-btn" class="flex items-center gap-2 text-foreground hover:text-primary transition-colors">
                <i class="bi bi-funnel text-lg"></i>
                <span class="font-medium">Filters</span>
                <i class="bi bi-chevron-down transition-transform {{ $hasFilter ? 'rotate-180' : '' }}" id="filters-icon"></i>
            </button>

            <div id="filters-content" class="{{ $hasFilter ? '' : 'hidden' }} mt-4">
                <form action="{{ route('users.attending', $user->id) }}" method="POST">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-4">
                        <!-- Name -->
                        <div>
                            <label for="filter_name" class="block text-sm font-medium text-foreground mb-1">Name</label>
                            <input type="text" name="filters[name]" id="filter_name" value="{{ $filters['name'] ?? '' }}"
                                class="w-full px-3 py-2 bg-input border border-input rounded-lg text-foreground">
                        </div>

                        <!-- Venue -->
                        <div>
                            <label for="filter_venue" class="block text-sm font-medium text-foreground mb-1">Venue</label>
                            <select name="filters[venue]" id="filter_venue" class="select2 w-full" data-placeholder="Select a venue" data-theme="tailwind">
                                <option value=""></option>
                                @foreach($venueOptions as $id => $name)
                                    <option value="{{ $id }}" {{ ($filters['venue'] ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Tag -->
                        <div>
                            <label for="filter_tag" class="block text-sm font-medium text-foreground mb-1">Tag</label>
                            <select name="filters[tag]" id="filter_tag" class="select2 w-full" data-placeholder="Select a tag" data-theme="tailwind">
                                <option value=""></option>
                                @foreach($tagOptions as $id => $name)
                                    <option value="{{ $id }}" {{ ($filters['tag'] ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Related Entity -->
                        <div>
                            <label for="filter_related" class="block text-sm font-medium text-foreground mb-1">Related Entity</label>
                            <select name="filters[related]" id="filter_related" class="select2 w-full" data-placeholder="Select an entity" data-theme="tailwind">
                                <option value=""></option>
                                @foreach($relatedOptions as $id => $name)
                                    <option value="{{ $id }}" {{ ($filters['related'] ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Event Type -->
                        <div>
                            <label for="filter_event_type" class="block text-sm font-medium text-foreground mb-1">Type</label>
                            <select name="filters[event_type]" id="filter_event_type" class="select2 w-full" data-placeholder="Select a type" data-theme="tailwind">
                                <option value=""></option>
                                @foreach($eventTypeOptions as $id => $name)
                                    <option value="{{ $id }}" {{ ($filters['event_type'] ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Start Date Range -->
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Start Date</label>
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-muted-foreground w-10">From:</span>
                                    <input type="date" name="filters[start_at][start]" value="{{ $filters['start_at']['start'] ?? '' }}"
                                        class="flex-1 px-3 py-1.5 bg-input border border-input rounded-lg text-foreground text-sm">
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-muted-foreground w-10">To:</span>
                                    <input type="date" name="filters[start_at][end]" value="{{ $filters['start_at']['end'] ?? '' }}"
                                        class="flex-1 px-3 py-1.5 bg-input border border-input rounded-lg text-foreground text-sm">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                            Apply Filters
                        </button>
                </form>
                        <form action="{{ route('users.resetUserAttending', $user->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-muted text-muted-foreground rounded-lg hover:bg-muted/80 transition-colors">
                                Reset
                            </button>
                        </form>
                    </div>
            </div>
        </div>
    </div>

    <!-- Sort Controls -->
    <div class="card-tw mb-6">
        <div class="p-4">
            <form action="{{ url()->current() }}" method="GET" class="flex flex-wrap items-center gap-3">
                <a href="{{ url()->action('EventsController@rppResetUserAttending', ['id' => $user->id]) }}?key={{ $key ?? '' }}"
                   class="p-2 bg-muted hover:bg-muted/80 rounded-lg transition-colors" title="Reset list controls">
                    <i class="bi bi-arrow-clockwise"></i>
                </a>

                <select name="limit" class="px-3 py-2 bg-input border border-input rounded-lg text-foreground auto-submit">
                    @foreach($limitOptions as $value => $label)
                        <option value="{{ $value }}" {{ ($limit ?? 10) == $value ? 'selected' : '' }}>{{ $label }} per page</option>
                    @endforeach
                </select>

                <select name="sort" class="px-3 py-2 bg-input border border-input rounded-lg text-foreground auto-submit">
                    @foreach($sortOptions as $value => $label)
                        <option value="{{ $value }}" {{ ($sort ?? 'events.start_at') == $value ? 'selected' : '' }}>Sort: {{ $label }}</option>
                    @endforeach
                </select>

                <select name="direction" class="px-3 py-2 bg-input border border-input rounded-lg text-foreground auto-submit">
                    @foreach($directionOptions as $value => $label)
                        <option value="{{ $value }}" {{ ($direction ?? 'asc') == $value ? 'selected' : '' }}>{{ ucfirst($label) }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <!-- Events List -->
    @if (isset($events))
        @if (count($events) > 0)
            <div class="mb-4">
                {!! $events->onEachSide(2)->links('vendor.pagination.tailwind') !!}
            </div>

            @include('events.list-tw', ['events' => $events])

            <div class="mt-4">
                {!! $events->onEachSide(2)->links('vendor.pagination.tailwind') !!}
            </div>
        @else
            <div class="card-tw">
                <div class="p-8 text-center text-muted-foreground">
                    <i class="bi bi-calendar-x text-4xl mb-3"></i>
                    <p>No matching events found.</p>
                </div>
            </div>
        @endif
    @endif

    <!-- Past and Future Events (if provided separately) -->
    @if ((isset($past_events) && count($past_events) > 0) || (isset($future_events) && count($future_events) > 0))
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
            @if (isset($past_events) && count($past_events) > 0)
                <div class="card-tw">
                    <div class="border-b border-border px-6 py-4">
                        <h3 class="text-lg font-semibold text-foreground">
                            <a href="{{ url('/events/past') }}" class="hover:text-primary transition-colors">Past Events</a>
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="mb-4">
                            {!! $past_events->onEachSide(2)->links('vendor.pagination.tailwind') !!}
                        </div>
                        @include('events.list-tw', ['events' => $past_events])
                        <div class="mt-4">
                            {!! $past_events->onEachSide(2)->links('vendor.pagination.tailwind') !!}
                        </div>
                    </div>
                </div>
            @endif

            @if (isset($future_events) && count($future_events) > 0)
                <div class="card-tw">
                    <div class="border-b border-border px-6 py-4">
                        <h3 class="text-lg font-semibold text-foreground">
                            <a href="{{ url('/events/future') }}" class="hover:text-primary transition-colors">Future Events</a>
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="mb-4">
                            {!! $future_events->onEachSide(2)->links('vendor.pagination.tailwind') !!}
                        </div>
                        @include('events.list-tw', ['events' => $future_events])
                        <div class="mt-4">
                            {!! $future_events->onEachSide(2)->links('vendor.pagination.tailwind') !!}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>

@stop

@section('scripts.footer')
<script>
$(document).ready(function() {
    // Filters toggle
    const toggleBtn = document.getElementById('filters-toggle-btn');
    const filtersContent = document.getElementById('filters-content');
    const filtersIcon = document.getElementById('filters-icon');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            filtersContent.classList.toggle('hidden');
            filtersIcon.classList.toggle('rotate-180');
        });
    }

    // Auto-submit on select change
    document.querySelectorAll('.auto-submit').forEach(function(select) {
        select.addEventListener('change', function() {
            this.form.submit();
        });
    });

    // Initialize Select2
    $('#filter_venue, #filter_tag, #filter_related, #filter_event_type').select2({
        theme: 'tailwind',
        width: '100%',
        allowClear: true
    });
});
</script>
@stop
