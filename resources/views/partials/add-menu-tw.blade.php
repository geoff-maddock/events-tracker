{{-- "+" add menu (#2108): the site's front door for contributing. Guests see it
     too; the create pages send them to sign in and back (see Authenticate). --}}
<div class="relative flex-shrink-0" x-data="{ open: false }" @keydown.escape.window="open = false">
    <button @click="open = !open" type="button"
        class="{{ $buttonClass ?? 'p-2' }} rounded-md text-foreground hover:bg-accent transition-colors"
        aria-haspopup="true" :aria-expanded="open" aria-label="Add something" title="Add an event, series or entity">
        <i class="bi bi-plus-lg text-xl" aria-hidden="true"></i>
    </button>
    <div x-show="open" @click.away="open = false" style="display: none"
        class="absolute {{ ($align ?? 'right') === 'left' ? 'left-0' : 'right-0' }} mt-1 w-44 bg-card border border-border rounded-lg shadow-lg z-50">
        <div class="py-1">
            <a href="{{ route('events.create') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-foreground hover:bg-accent transition-colors">
                <i class="bi bi-calendar-plus"></i>
                Add Event
            </a>
            <a href="{{ route('series.create') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-foreground hover:bg-accent transition-colors">
                <i class="bi bi-collection"></i>
                Add Series
            </a>
            <a href="{{ route('entities.create') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-foreground hover:bg-accent transition-colors">
                <i class="bi bi-people"></i>
                Add Entity
            </a>
        </div>
    </div>
</div>
