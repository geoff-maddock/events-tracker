<!-- Results Bar -->
<div class="flex flex-wrap items-center gap-4 mb-6">
	<!-- Results Count -->
	<div class="text-sm text-muted-foreground w-full sm:w-auto">
		@if(isset($series))
		Showing {{ $series->firstItem() ?? 0 }} to {{ $series->lastItem() ?? 0 }} of {{ $series->total() }} results
		@endif
	</div>

	<!-- Sort Controls -->
	<div class="flex items-center justify-center gap-4 w-full sm:flex-1">
		<form action="{{ url()->current() }}" method="GET" class="flex flex-wrap sm:flex-nowrap items-center gap-2 w-full sm:w-auto">
			<select name="limit" class="form-select-tw text-sm py-1.5 px-3 auto-submit flex-1 sm:flex-initial sm:max-w-[120px] min-w-0">
				@foreach($limitOptions as $value => $label)
				<option value="{{ $value }}" {{ ($limit ?? 10) == $value ? 'selected' : '' }}>{{ $label }}</option>
				@endforeach
			</select>
			<span class="text-muted-foreground text-sm hidden sm:inline">Sort by:</span>
			<select name="sort" class="form-select-tw text-sm py-1.5 px-3 auto-submit flex-1 sm:flex-initial sm:max-w-[160px] min-w-0">
				@foreach($sortOptions as $value => $label)
				<option value="{{ $value }}" {{ ($sort ?? 'series.name') == $value ? 'selected' : '' }}>{{ $label }}</option>
				@endforeach
			</select>
			<select name="direction" class="form-select-tw text-sm py-1.5 px-3 auto-submit flex-1 sm:flex-initial sm:max-w-[140px] min-w-0">
				@foreach($directionOptions as $value => $label)
				<option value="{{ $value }}" {{ ($direction ?? 'asc') == $value ? 'selected' : '' }}>{{ $label }}</option>
				@endforeach
			</select>
		</form>
	</div>

	<!-- Pagination -->
	@if(isset($series) && $series->hasPages())
	<div class="flex items-center justify-center sm:justify-end gap-1 w-full sm:w-auto">
		@foreach($series->getUrlRange(max(1, $series->currentPage() - 2), min($series->lastPage(), $series->currentPage() + 2)) as $page => $url)
		<a href="{{ $url }}" class="px-2 sm:px-3 py-1 rounded {{ $page == $series->currentPage() ? 'bg-accent text-foreground border border-primary' : 'text-muted-foreground hover:bg-card' }}">{{ $page }}</a>
		@endforeach
	</div>
	@endif
</div>