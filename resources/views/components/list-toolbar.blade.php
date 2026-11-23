{{--
    The bar above (and below) a list: the result count, the per-page, sort and
    direction selects, and the nearby page links (#2410).

    <x-list-toolbar :paginator="$things ?? null" :limit="$limit ?? 10" :sort="$sort ?? 'things.name'" :direction="$direction ?? 'asc'"
        :limit-options="$limitOptions" :sort-options="$sortOptions" :direction-options="$directionOptions" />

    The selects submit on change (the .auto-submit handler in custom.js). Hidden
    inputs that must travel with them go in the slot.
--}}
@props([
    'paginator' => null,
    'limit',
    'sort',
    'direction',
    'limitOptions',
    'sortOptions',
    'directionOptions',
])
<div class="flex flex-wrap items-center gap-4 mb-6">
	<div class="text-sm text-muted-foreground w-full sm:w-auto">
		@if($paginator)
		Showing {{ $paginator->firstItem() ?? 0 }} to {{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }} results
		@endif
	</div>

	<div class="flex items-center justify-center gap-4 w-full sm:flex-1">
		<form action="{{ url()->current() }}" method="GET" class="flex flex-wrap sm:flex-nowrap items-center gap-2 w-full sm:w-auto">
			{{ $slot }}
			<select name="limit" aria-label="Results per page" class="form-select-tw text-sm py-1.5 px-3 auto-submit flex-1 sm:flex-initial sm:max-w-[120px] min-w-0">
				@foreach($limitOptions as $value => $label)
				<option value="{{ $value }}" {{ $limit == $value ? 'selected' : '' }}>{{ $label }}</option>
				@endforeach
			</select>
			<span class="text-muted-foreground text-sm hidden sm:inline">Sort by:</span>
			<select name="sort" aria-label="Sort by" class="form-select-tw text-sm py-1.5 px-3 auto-submit flex-1 sm:flex-initial sm:max-w-[160px] min-w-0">
				@foreach($sortOptions as $value => $label)
				<option value="{{ $value }}" {{ $sort == $value ? 'selected' : '' }}>{{ $label }}</option>
				@endforeach
			</select>
			<select name="direction" aria-label="Sort direction" class="form-select-tw text-sm py-1.5 px-3 auto-submit flex-1 sm:flex-initial sm:max-w-[140px] min-w-0">
				@foreach($directionOptions as $value => $label)
				<option value="{{ $value }}" {{ $direction == $value ? 'selected' : '' }}>{{ $label }}</option>
				@endforeach
			</select>
		</form>
	</div>

	@if($paginator && $paginator->hasPages())
	<nav aria-label="Pages" class="flex items-center justify-center sm:justify-end gap-1 w-full sm:w-auto">
		@foreach($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
		<a href="{{ $url }}" @if($page == $paginator->currentPage()) aria-current="page" @endif class="px-2 sm:px-3 py-1 rounded {{ $page == $paginator->currentPage() ? 'bg-accent text-foreground border border-primary' : 'text-muted-foreground hover:bg-card' }}">{{ $page }}</a>
		@endforeach
	</nav>
	@endif
</div>
