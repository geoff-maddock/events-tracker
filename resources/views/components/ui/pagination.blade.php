@props([
    'paginator',
    'showLinks' => true,
    'showText' => true,
])

@if ($paginator->hasPages())
<nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between">
    <!-- Mobile View -->
    <div class="flex justify-between flex-1 sm:hidden">
        @if ($paginator->onFirstPage())
            <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-muted-foreground bg-muted border border-border rounded-md cursor-not-allowed">
                Previous
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-foreground bg-card border border-border rounded-md hover:bg-accent transition-colors">
                Previous
            </a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-foreground bg-card border border-border rounded-md hover:bg-accent transition-colors">
                Next
            </a>
        @else
            <span class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-muted-foreground bg-muted border border-border rounded-md cursor-not-allowed">
                Next
            </span>
        @endif
    </div>

    <!-- Desktop View -->
    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        @if ($showText)
        <div>
            <p class="text-sm text-muted-foreground">
                Showing
                <span class="font-medium">{{ $paginator->firstItem() ?? 0 }}</span>
                to
                <span class="font-medium">{{ $paginator->lastItem() ?? 0 }}</span>
                of
                <span class="font-medium">{{ $paginator->total() }}</span>
                results
            </p>
        </div>
        @endif

        @if ($showLinks)
        <div>
            <span class="relative z-0 inline-flex shadow-sm rounded-md gap-1">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="Previous">
                        <span class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-muted-foreground bg-muted border border-border rounded-l-md cursor-not-allowed" aria-hidden="true">
                            <i class="bi bi-chevron-left"></i>
                        </span>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-foreground bg-card border border-border rounded-l-md hover:bg-accent transition-colors" aria-label="Previous">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($paginator->elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <span aria-disabled="true">
                            <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-muted-foreground bg-muted border border-border cursor-default">{{ $element }}</span>
                        </span>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page">
                                    <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-primary-foreground bg-primary border border-primary cursor-default">{{ $page }}</span>
                                </span>
                            @else
                                <a href="{{ $url }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-foreground bg-card border border-border hover:bg-accent transition-colors" aria-label="Go to page {{ $page }}">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-foreground bg-card border border-border rounded-r-md hover:bg-accent transition-colors" aria-label="Next">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                @else
                    <span aria-disabled="true" aria-label="Next">
                        <span class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-muted-foreground bg-muted border border-border rounded-r-md cursor-not-allowed" aria-hidden="true">
                            <i class="bi bi-chevron-right"></i>
                        </span>
                    </span>
                @endif
            </span>
        </div>
        @endif
    </div>
</nav>
@endif
