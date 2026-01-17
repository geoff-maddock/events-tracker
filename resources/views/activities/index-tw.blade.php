@extends('layouts.app-tw')

@section('title', 'Activity')

@section('content')

<!-- Page Header -->
<div class="mb-6">
    <h1 class="text-3xl font-bold text-primary mb-2">Activity</h1>
    <p class="text-muted-foreground">Recent activity and changes across the site.</p>
</div>

<!-- Filters Section -->
<div class="mb-6">
    <button id="filters-toggle-btn" class="inline-flex items-center px-4 py-2 bg-accent text-foreground border border-primary rounded-lg hover:bg-accent/80 transition-colors">
        <i class="bi bi-funnel mr-2"></i>
        <span id="filters-toggle-text">@if ($hasFilter) Hide @else Show @endif Filters</span>
        <i class="bi bi-chevron-down ml-2 transition-transform @if($hasFilter) rotate-180 @endif" id="filters-chevron"></i>
    </button>

    @if($hasFilter)
    <div class="inline-flex items-center gap-2 ml-4">
        <a href="{{ route('activities.reset') }}" class="inline-flex items-center px-3 py-1 text-sm text-muted-foreground hover:text-foreground border border-border rounded-lg">
            Clear All <i class="bi bi-x ml-1"></i>
        </a>
    </div>
    @endif
</div>

<!-- Filter Panel -->
<div id="filter-panel" class="@if (!$hasFilter) hidden @endif bg-card border border-border rounded-lg p-4 mb-6 overflow-hidden">
    {!! Form::open(['route' => [$filterRoute ?? 'activities.filter'], 'name' => 'filters', 'method' => 'POST']) !!}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="min-w-0">
            <label for="filter_message" class="block text-sm font-medium text-muted-foreground mb-1">Message</label>
            {!! Form::text('filter_message', ($filters['message'] ?? null), [
                'class' => 'form-input-tw',
                'name' => 'filters[message]',
                'id' => 'filter_message',
                'placeholder' => 'Filter by message'
            ]) !!}
        </div>

        <div class="min-w-0">
            <label for="filter_object_table" class="block text-sm font-medium text-muted-foreground mb-1">Table</label>
            {!! Form::text('filter_object_table', ($filters['object_table'] ?? null), [
                'class' => 'form-input-tw',
                'name' => 'filters[object_table]',
                'id' => 'filter_object_table',
                'placeholder' => 'Filter by table'
            ]) !!}
        </div>

        <div class="min-w-0">
            <label for="filter_action" class="block text-sm font-medium text-muted-foreground mb-1">Action</label>
            {!! Form::select('filter_action', $actionOptions, ($filters['action'] ?? null), [
                'class' => 'form-select-tw',
                'data-placeholder' => 'Select an action',
                'name' => 'filters[action]',
                'id' => 'filter_action'
            ]) !!}
        </div>

        <div class="min-w-0">
            <label for="filter_user" class="block text-sm font-medium text-muted-foreground mb-1">User</label>
            {!! Form::select('filter_user', $userOptions, ($filters['user'] ?? null), [
                'class' => 'form-select-tw select2',
                'data-placeholder' => 'Select a user',
                'data-theme' => 'tailwind',
                'data-allow-clear' => 'true',
                'name' => 'filters[user]',
                'id' => 'filter_user'
            ]) !!}
        </div>
    </div>

    <!-- Filter Actions -->
    <div class="flex gap-2 mt-4">
        <button type="submit" class="px-4 py-2 bg-accent text-foreground border border-primary rounded-lg hover:bg-accent/80 transition-colors">
            Apply
        </button>
        {!! Form::close() !!}
        {!! Form::open(['route' => ['activities.reset'], 'method' => 'GET']) !!}
        <button type="submit" class="px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors">
            Reset
        </button>
        {!! Form::close() !!}
    </div>
</div>

<!-- Results Bar -->
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <!-- Results Count -->
    <div class="text-sm text-muted-foreground">
        @if(isset($activities) && $activities->total() > 0)
        Showing {{ $activities->firstItem() ?? 0 }} to {{ $activities->lastItem() ?? 0 }} of {{ $activities->total() }} results
        @endif
    </div>

    <!-- Sort Controls -->
    <div class="flex items-center gap-4">
        <form action="{{ url()->current() }}" method="GET" class="flex items-center gap-2">
            <select name="limit" class="form-select-tw text-sm py-1 auto-submit">
                @foreach($limitOptions as $value => $label)
                <option value="{{ $value }}" {{ ($limit ?? 10) == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <span class="text-muted-foreground text-sm">Sort by:</span>
            <select name="sort" class="form-select-tw text-sm py-1 auto-submit">
                @foreach($sortOptions as $value => $label)
                <option value="{{ $value }}" {{ ($sort ?? 'activities.created_at') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="direction" class="form-select-tw text-sm py-1 auto-submit">
                @foreach($directionOptions as $value => $label)
                <option value="{{ $value }}" {{ ($direction ?? 'desc') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- Pagination (top) -->
    @if(isset($activities) && $activities->hasPages())
    <div class="flex items-center gap-1">
        @if($activities->onFirstPage())
        <span class="px-3 py-1 text-muted-foreground/50 cursor-not-allowed">&lt; Previous</span>
        @else
        <a href="{{ $activities->appends(['sort' => $sort, 'direction' => $direction, 'limit' => $limit])->previousPageUrl() }}" class="px-3 py-1 text-muted-foreground hover:text-foreground">&lt; Previous</a>
        @endif

        @foreach($activities->appends(['sort' => $sort, 'direction' => $direction, 'limit' => $limit])->getUrlRange(max(1, $activities->currentPage() - 2), min($activities->lastPage(), $activities->currentPage() + 2)) as $page => $url)
        <a href="{{ $url }}" class="px-3 py-1 rounded {{ $page == $activities->currentPage() ? 'bg-accent text-foreground border border-primary' : 'text-muted-foreground hover:bg-card' }}">{{ $page }}</a>
        @endforeach

        @if($activities->hasMorePages())
        <a href="{{ $activities->appends(['sort' => $sort, 'direction' => $direction, 'limit' => $limit])->nextPageUrl() }}" class="px-3 py-1 text-muted-foreground hover:text-foreground">Next &gt;</a>
        @else
        <span class="px-3 py-1 text-muted-foreground/50 cursor-not-allowed">Next &gt;</span>
        @endif
    </div>
    @endif
</div>

<!-- Activity List -->
<div class="card-tw divide-y divide-border">
    @forelse ($activities as $activity)
    <div class="p-4 hover:bg-accent/50 transition-colors">
        <div class="flex items-start gap-3">
            <span class="text-xs font-mono text-muted-foreground bg-secondary px-2 py-1 rounded">
                #{{ $activity->id }}
            </span>
            <div class="flex-1 min-w-0">
                <a href="{{ strtolower($activity->getShowLink()) }}" class="text-foreground hover:text-primary font-medium">
                    {{ $activity->message }}
                </a>
                @if($activity->object_name)
                    <span class="text-sm text-muted-foreground ml-2">{{ $activity->object_name }}</span>
                @endif

                <div class="mt-1 text-sm text-muted-foreground">
                    by
                    <a href="{{ route('activities.filter', ['filters[user]' => $activity->userName]) }}" class="hover:text-foreground">
                        {{ $activity->userName }}
                    </a>
                    <a href="{{ url('users/'.$activity->user_id) }}" class="text-primary hover:underline ml-1" title="Show user profile">
                        <i class="bi bi-person-circle"></i>
                    </a>
                    @if(isset($activity->created_at))
                        <span class="ml-2">on {{ $activity->created_at->format('m/d/Y H:i') }}</span>
                    @endif
                    @if(isset($activity->ip_address))
                        <span class="ml-2 font-mono text-xs">[{{ $activity->ip_address }}]</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="p-8 text-center text-muted-foreground">
        <i class="bi bi-inbox text-4xl mb-2 block"></i>
        <p>No activity found.</p>
    </div>
    @endforelse
</div>

<!-- Bottom Pagination -->
@if(isset($activities) && $activities->hasPages())
<div class="mt-4">
    {{ $activities->appends(['sort' => $sort, 'direction' => $direction, 'limit' => $limit])->links('vendor.pagination.tailwind') }}
</div>
@endif

@stop

@section('footer')
<script>
    // Filter toggle functionality
    document.getElementById('filters-toggle-btn')?.addEventListener('click', function() {
        const panel = document.getElementById('filter-panel');
        const text = document.getElementById('filters-toggle-text');
        const chevron = document.getElementById('filters-chevron');

        panel.classList.toggle('hidden');

        if (panel.classList.contains('hidden')) {
            text.textContent = 'Show Filters';
            chevron.classList.remove('rotate-180');
        } else {
            text.textContent = 'Hide Filters';
            chevron.classList.add('rotate-180');
        }
    });
</script>
@endsection
