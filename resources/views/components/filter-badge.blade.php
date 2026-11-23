{{-- One active filter, shown beside the "Show Filters" button while the panel is closed. --}}
<span {{ $attributes->merge(['class' => 'px-3 py-1 text-sm bg-muted text-muted-foreground rounded-lg border border-border']) }}>{{ $slot }}</span>
