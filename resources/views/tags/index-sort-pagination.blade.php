<!-- Results Bar -->
<div class="flex flex-wrap items-center gap-4 mb-6">
	<!-- Results Count -->
	<div class="text-sm text-muted-foreground w-full sm:w-auto">
		@if(isset($tags))
		Showing {{ $tags->firstItem() ?? 0 }} to {{ $tags->lastItem() ?? 0 }} of {{ $tags->total() }} results
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
				<option value="{{ $value }}" {{ ($sort ?? 'tags.start_at') == $value ? 'selected' : '' }}>{{ $label }}</option>
				@endforeach
			</select>
			<select name="direction" class="form-select-tw text-sm py-1.5 px-3 auto-submit flex-1 sm:flex-initial sm:max-w-[140px] min-w-0">
				@foreach($directionOptions as $value => $label)
				<option value="{{ $value }}" {{ ($direction ?? 'desc') == $value ? 'selected' : '' }}>{{ $label }}</option>
				@endforeach
			</select>
		</form>
	</div>

	<!-- Pagination (top) -->
	@if(isset($tags) && $tags->hasPages())
	<div class="flex items-center justify-center sm:justify-end gap-1 w-full sm:w-auto">
		@foreach($tags->getUrlRange(max(1, $tags->currentPage() - 2), min($tags->lastPage(), $tags->currentPage() + 2)) as $page => $url)
		<a href="{{ $url }}" class="px-2 sm:px-3 py-1 rounded {{ $page == $tags->currentPage() ? 'bg-accent text-foreground border border-primary' : 'text-muted-foreground hover:bg-card' }}">{{ $page }}</a>
		@endforeach
	</div>
	@endif
</div>