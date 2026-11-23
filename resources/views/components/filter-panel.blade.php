{{--
    The filter section of a list page (#2410): the "Show Filters" button, the
    badges that summarise the active filters while the panel is closed, and the
    panel with its form, Apply and Reset.

    <x-filter-panel :action="route('things.filter')" :reset-url="route('things.reset')" :has-filter="$hasFilter">
        <x-slot:badges> <x-filter-badge>Name: {{ $filters['name'] }}</x-filter-badge> … </x-slot:badges>
        … the filter fields …
        <x-slot:actions> … extra buttons after Reset … </x-slot:actions>
    </x-filter-panel>

    Opening and closing is resources/assets/js/filter-panel.js, which reads the
    ids used here. One per page.
--}}
@props([
    'action',
    'method' => 'POST',
    'resetUrl' => null,
    // POST for a reset route that isn't a plain link
    'resetMethod' => 'GET',
    'hasFilter' => false,
    // the state the page starts in; the visitor's last choice then wins
    'open' => false,
])
@php
    $method = strtoupper($method);
    $resetByForm = $resetUrl && strtoupper($resetMethod) !== 'GET';
    $hasBadges = $hasFilter && isset($badges) && trim($badges) !== '';
@endphp
<div class="mb-6 flex flex-wrap items-start gap-2">
	<button type="button" id="filters-toggle-btn" data-has-filter="{{ $hasFilter ? 1 : 0 }}" aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="filter-panel"
		class="inline-flex items-center px-4 py-2 bg-accent text-foreground border border-primary rounded-lg hover:bg-accent/80 transition-colors">
		<i class="bi bi-funnel mr-2"></i>
		<span id="filters-toggle-text">{{ $open ? 'Hide' : 'Show' }} Filters</span>
		<i class="bi bi-chevron-down ml-2 transition-transform {{ $open ? 'rotate-180' : '' }}" id="filters-chevron"></i>
	</button>

	@if($hasBadges)
	<div id="active-filters-badges" class="flex flex-wrap items-center gap-2 {{ $open ? 'hidden' : '' }}">
		{{ $badges }}
	</div>
	@endif

	@if($hasFilter && $resetUrl)
		@if($resetByForm)
		<button type="submit" form="filters-reset-form" id="filters-reset-closed" class="inline-flex items-center px-3 py-1 text-sm text-muted-foreground hover:text-foreground border border-border rounded-lg {{ $open ? 'hidden' : '' }}">
			Reset <i class="bi bi-x ml-1"></i>
		</button>
		@else
		<a id="filters-reset-closed" href="{{ $resetUrl }}" class="inline-flex items-center px-3 py-1 text-sm text-muted-foreground hover:text-foreground border border-border rounded-lg {{ $open ? 'hidden' : '' }}">
			Reset <i class="bi bi-x ml-1"></i>
		</a>
		@endif
	@endif
</div>

<div id="filter-panel" class="{{ $open ? '' : 'hidden' }} bg-card border border-border rounded-lg p-4 mb-6 overflow-hidden">
	<form method="{{ $method === 'GET' ? 'GET' : 'POST' }}" action="{{ $action }}" accept-charset="UTF-8" name="filters">
		@if($method !== 'GET')
		@csrf
		@endif

		{{ $slot }}

		<div class="flex flex-wrap gap-2 mt-4">
			<button type="submit" class="px-4 py-2 bg-accent text-foreground border border-primary rounded-lg hover:bg-accent/80 transition-colors">
				Apply
			</button>
			@if($resetUrl)
				@if($resetByForm)
				<button type="submit" form="filters-reset-form" id="filters-reset-open" class="px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors {{ $hasFilter && $open ? '' : 'hidden' }}">
					Reset
				</button>
				@else
				<a id="filters-reset-open" href="{{ $resetUrl }}" class="px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors {{ $hasFilter && $open ? '' : 'hidden' }}">
					Reset
				</a>
				@endif
			@endif
			{{ $actions ?? '' }}
		</div>
	</form>
	@if($resetByForm)
	<form id="filters-reset-form" method="POST" action="{{ $resetUrl }}" class="hidden">
		@csrf
	</form>
	@endif
</div>
