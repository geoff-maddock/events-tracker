@extends('layouts.app-tw')

@section('title')
@if (isset($pageTitle))
{{ $pageTitle }}
@elseif (isset($tag) || isset($related) || isset($type) || isset($slug) || isset($cdate))
Events @include('events.title-crumbs')
@else
Pittsburgh Events Calendar — Concerts, Shows & More
@endif
@endsection

{{-- listing variants that share this view but are distinct pages set their own description --}}
@if (isset($pageDescription))
@section('description')
{{ $pageDescription }}
@endsection
@section('og-description')
{{ $pageDescription }}
@endsection
@endif

@if (isset($events) && count($events) > 0)
@php
	$first = $events[0];
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
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
@endsection

@if (isset($events) && count($events) > 0)
@section('page.json')
@include('events.index-json-ld')
@endsection
@endif

@section('content')

<!-- Page Header -->
<div class="mb-6">
	<h1 class="text-3xl font-bold text-primary mb-2">Event Listings @include('events.crumbs-tw')</h1>
	@isset($tag)
	<p class="text-muted-foreground">Discover and explore all events tagged with <strong>{{ ucfirst($tag->name) }}</strong>.</p>
	@else
	<p class="text-muted-foreground">Discover and explore upcoming events.</p>
	@endisset
</div>

<!-- Action Buttons -->
<div class="mb-6 flex flex-wrap gap-2 items-center">
	<a href="{!! URL::route('events.create') !!}" class="inline-flex items-center px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors">
		<i class="bi bi-plus-lg mr-2"></i>
		Add Event
	</a>
	@if (!empty($playerQuery))
	@include('player.launch-tw', ['query' => $playerQuery, 'label' => 'Play this list'])
	@endif
	<div class="ml-auto relative" id="actions-menu-container">
		<button id="actions-menu-btn" class="inline-flex items-center px-3 py-2 bg-card border border-border text-muted-foreground rounded-lg hover:bg-accent transition-colors text-sm" aria-haspopup="true" aria-expanded="false" aria-controls="actions-menu-dropdown" aria-label="More actions" title="More actions">
			<i class="bi bi-three-dots" aria-hidden="true"></i>
		</button>
		<div id="actions-menu-dropdown" class="hidden absolute right-0 mt-1 w-44 bg-card border border-border rounded-lg shadow-lg z-10">
			<a href="{!! URL::route('events.export') !!}" class="flex items-center px-4 py-2 text-sm text-muted-foreground hover:bg-accent rounded-t-lg transition-colors" target="_blank">
				<i class="bi bi-download mr-2"></i>
				Export TXT
			</a>
			<a href="{!! URL::route('events.indexIcal') !!}" class="flex items-center px-4 py-2 text-sm text-muted-foreground hover:bg-accent rounded-b-lg transition-colors" target="_blank">
				<i class="bi bi-calendar-event mr-2"></i>
				Export iCal
			</a>
			@if ($signedIn && $user && $user->hasGroup('super_admin'))
			<div class="border-t border-border my-1"></div>
			<form action="{!! URL::route('events.instagramWeekendPreview') !!}" method="POST" class="block" data-ajax-action>
				@csrf
				<button type="submit" class="flex items-center px-4 py-2 text-sm text-muted-foreground hover:bg-accent transition-colors w-full text-left">
					<i class="bi bi-instagram mr-2"></i>
					Weekend Preview
				</button>
			</form>
			<form action="{!! URL::route('events.instagramTodaysPreview') !!}" method="POST" class="block" data-ajax-action>
				@csrf
				<button type="submit" class="flex items-center px-4 py-2 text-sm text-muted-foreground hover:bg-accent rounded-b-lg transition-colors w-full text-left">
					<i class="bi bi-instagram mr-2"></i>
					Today's Preview
				</button>
			</form>
			@endif
		</div>
	</div>
</div>

<!-- Filters -->
<x-filter-panel :action="route($filterRoute ?? 'events.filter')" :reset-url="route('events.reset', ['redirect' => $redirect ?? 'events.index', 'key' => $key ?? 'internal_event_index'])" :has-filter="$hasFilter">
	<x-slot:badges>
		@if(!empty($filters['name']))
		<x-filter-badge>Name: {{ $filters['name'] }}</x-filter-badge>
		@endif
		@if(!empty($filters['venue']))
		<x-filter-badge>Venue: {{ $venueOptions[$filters['venue']] ?? 'Unknown' }}</x-filter-badge>
		@endif
		@if(!empty($filters['tag']))
		<x-filter-badge>Tag: {{ collect((array) $filters['tag'])->filter()->map(fn ($tag) => $tagOptions[$tag] ?? $tag)->implode(', ') }}</x-filter-badge>
		@endif
		@if(!empty($filters['related']))
		<x-filter-badge>Entity: {{ $relatedOptions[$filters['related']] ?? 'Unknown' }}</x-filter-badge>
		@endif
		@if(!empty($filters['event_type']))
		<x-filter-badge>Type: {{ $eventTypeOptions[$filters['event_type']] ?? 'Unknown' }}</x-filter-badge>
		@endif
		@if(isset($filters['start_at']['start']))
		<x-filter-badge>Date from: {{ $filters['start_at']['start'] }}</x-filter-badge>
		@endif
		@if(isset($filters['start_at']['end']))
		<x-filter-badge>Date to: {{ $filters['start_at']['end'] }}</x-filter-badge>
		@endif
		@if(!empty($filters['my_events']))
		<x-filter-badge>My Events</x-filter-badge>
		@endif
		@include('events.filters.audience-badges')
		@if(!empty($filters['display_type']) && $filters['display_type'] !== 'all')
		<x-filter-badge>{{ \App\Filters\EventFilters::DISPLAY_TYPES[$filters['display_type']] ?? $filters['display_type'] }}</x-filter-badge>
		@endif
	</x-slot:badges>

	<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
		<!-- Name Filter -->
		<div class="min-w-0">
			<label for="filter_name" class="block text-sm font-medium text-muted-foreground mb-1">Name</label>
			<input type="text"
				name="filters[name]"
				id="filter_name"
				value="{{ $filters['name'] ?? '' }}"
				class="form-input-tw"
				placeholder="Event name...">
		</div>

		<!-- Venue Filter -->
		<div class="min-w-0">
			<label for="filter_venue" class="block text-sm font-medium text-muted-foreground mb-1">Venue</label>
			<x-ui.select-field name="filters[venue]" :options="$venueOptions" :selected="$filters['venue'] ?? null"
				data-theme="tailwind" class="form-select-tw select2" data-placeholder="Select a venue" id="filter_venue" />
		</div>

		<!-- Tag Filter -->
		<div class="min-w-0">
			<label for="filter_tag" class="block text-sm font-medium text-muted-foreground mb-1">Tags</label>
			<x-ui.select-field name="filters[tag][]" :options="array_filter($tagOptions, fn($key) => $key !== '', ARRAY_FILTER_USE_KEY)" :selected="$filters['tag'] ?? null"
				data-theme="tailwind" class="form-select-tw select2" data-placeholder="Select tags" id="filter_tag" multiple />
		</div>

		<!-- Related Entity Filter -->
		<div class="min-w-0">
			<label for="filter_related" class="block text-sm font-medium text-muted-foreground mb-1">Related Entity</label>
			<x-ui.select-field name="filters[related]" :options="$relatedOptions" :selected="$filters['related'] ?? null"
				data-theme="tailwind" class="form-select-tw select2" data-placeholder="Select an entity" id="filter_related" />
		</div>

		<!-- Event Type Filter -->
		<div class="min-w-0">
			<label for="filter_event_type" class="block text-sm font-medium text-muted-foreground mb-1">Event Type</label>
			<x-ui.select-field name="filters[event_type]" :options="$eventTypeOptions" :selected="$filters['event_type'] ?? null"
				data-theme="tailwind" class="form-select-tw select2" data-placeholder="Select a type" id="filter_event_type" />
		</div>

		<!-- Date Range Filter -->
		<div class="min-w-0">
			<label class="block text-sm font-medium text-muted-foreground mb-1">Start Date</label>
			<div class="space-y-2">
				<div class="flex items-center gap-2">
					<span class="text-sm text-muted-foreground w-12 shrink-0">From:</span>
					<input type="date"
						name="filters[start_at][start]"
						value="{{ $filters['start_at']['start'] ?? '' }}"
						class="form-input-tw flex-1 min-w-0">
				</div>
				<div class="flex items-center gap-2">
					<span class="text-sm text-muted-foreground w-12 shrink-0">To:</span>
					<input type="date"
						name="filters[start_at][end]"
						value="{{ $filters['start_at']['end'] ?? '' }}"
						class="form-input-tw flex-1 min-w-0">
				</div>
			</div>
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
		@if($hasFilter)
		<button type="button" id="copy-filter-url-btn" class="px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors flex items-center gap-2">
			<i class="bi bi-link-45deg"></i>
			<span>Copy Filter URL</span>
		</button>
		@endif
	</x-slot:actions>
</x-filter-panel>

<x-list-toolbar :paginator="$events ?? null" :limit="$limit ?? 10" :sort="$sort ?? 'events.start_at'" :direction="$direction ?? 'desc'"
	:limit-options="$limitOptions" :sort-options="$sortOptions" :direction-options="$directionOptions" />

<!-- Events Grid -->
@if (isset($events) && count($events) > 0)
<div class="event-grid-tw">
	@foreach ($events as $event)
	@include('events.card-tw', ['event' => $event])
	@endforeach
</div>
@else
<x-empty-state icon="bi-calendar-x">
	No matching events found.
	<x-slot:actions>
		<a href="{{ url('/events') }}" class="inline-flex items-center text-primary hover:text-primary/90">
			<i class="bi bi-arrow-left mr-2" aria-hidden="true"></i>
			View all events
		</a>
	</x-slot:actions>
</x-empty-state>
@endif

<br>
<x-list-toolbar :paginator="$events ?? null" :limit="$limit ?? 10" :sort="$sort ?? 'events.start_at'" :direction="$direction ?? 'desc'"
	:limit-options="$limitOptions" :sort-options="$sortOptions" :direction-options="$directionOptions" />

@stop

@section('footer')
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

	// Queue-only admin actions (the Instagram previews) post via fetch and show the
	// JSON reply in a popup, so the page doesn't reload. A redirect-and-flash
	// round trip can lose the message to the embed requests sharing the session.
	(function() {
		const fireAlert = function(options) {
			if (window.Swal && typeof window.Swal.fire === 'function') {
				return window.Swal.fire(options);
			}
			window.alert(options.text || options.title || 'Notification');
		};

		document.querySelectorAll('form[data-ajax-action]').forEach(function(form) {
			form.addEventListener('submit', function(e) {
				e.preventDefault();

				const button = form.querySelector('button[type="submit"]');
				if (button) button.disabled = true;

				fetch(form.action, {
					method: 'POST',
					body: new FormData(form),
					headers: {
						'X-Requested-With': 'XMLHttpRequest',
						'Accept': 'application/json'
					},
					credentials: 'same-origin'
				}).then(function(response) {
					return response.json().then(function(data) {
						return { ok: response.ok, data: data };
					});
				}).then(function(result) {
					fireAlert({
						title: result.data.title || (result.ok ? 'Queued' : 'Error'),
						text: result.data.message || 'Could not start the Instagram post. Please try again.',
						icon: result.ok ? 'success' : 'error',
						timer: result.ok ? 3000 : undefined,
						showConfirmButton: !result.ok
					});
				}).catch(function() {
					fireAlert({
						title: 'Error',
						text: 'Could not start the Instagram post. Please try again.',
						icon: 'error'
					});
				}).finally(function() {
					if (button) button.disabled = false;
				});
			});
		});
	})();

	// Copy filter URL functionality
	document.getElementById('copy-filter-url-btn')?.addEventListener('click', function() {
		// Build the filter URL with current filter values
		const filters = @json($filters ?? []);
		const sort = @json($sort ?? '');
		const direction = @json($direction ?? '');
		const limit = @json($limit ?? null);
		
		// Build query parameters
		const params = new URLSearchParams();
		
		// Add filters
		if (filters && Object.keys(filters).length > 0) {
			for (const [key, value] of Object.entries(filters)) {
				if (value !== null && value !== '' && value !== undefined) {
					if (Array.isArray(value)) {
						// Handle array values (like tags)
						value.forEach(v => {
							if (v !== null && v !== '') {
								params.append(`filters[${key}][]`, v);
							}
						});
					} else if (typeof value === 'object' && value !== null) {
						// Handle nested objects (like date ranges)
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
		
		// Add sorting parameters
		if (sort) {
			params.append('sort', sort);
		}
		if (direction) {
			params.append('direction', direction);
		}
		if (limit) {
			params.append('limit', limit);
		}
		
		// Build the full URL
		const baseUrl = '{{ Route::has('events.applyFilterFromUrl') ? route('events.applyFilterFromUrl') : route('events.filter') }}';
		const fullUrl = `${baseUrl}?${params.toString()}`;

		const btn = document.getElementById('copy-filter-url-btn');

		function showCopiedFeedback() {
			const originalContent = btn.innerHTML;
			btn.innerHTML = '<i class="bi bi-check2"></i><span>Copied!</span>';
			btn.classList.add('bg-green-100', 'border-green-500', 'text-green-700');
			setTimeout(function() {
				btn.innerHTML = originalContent;
				btn.classList.remove('bg-green-100', 'border-green-500', 'text-green-700');
			}, 2000);
		}

		function copyToClipboard(url) {
			navigator.clipboard.writeText(url).then(showCopiedFeedback).catch(function() {
				alert('Failed to copy URL. Please copy manually: ' + url);
			});
		}

		// Shorten the URL before copying
		const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
		fetch('{{ route('short-url.shorten') }}', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-CSRF-TOKEN': csrfToken,
				'Accept': 'application/json',
			},
			body: JSON.stringify({ url: fullUrl }),
		})
		.then(function(response) { return response.json(); })
		.then(function(data) { copyToClipboard(data.short_url || fullUrl); })
		.catch(function() { copyToClipboard(fullUrl); });
	});
</script>
@endsection
