{{--
    A link that opens the pop-out audio player (#2366); public/js/player-launcher.js
    turns the click into a reused popup window, or an "add to queue" message when the
    player is already open. Without JS the named target still opens one player tab.

    $query   array  queue parameters, e.g. ['source' => 'event', 'id' => 12]
    $mode    string 'load' (replace the queue) or 'enqueue' (add to an open player)
    $label   string visible text / accessible name
    $variant string 'nav' | 'button' | 'icon' | 'chip' (matches a page's section buttons)
--}}
@php
    $query = http_build_query($query ?? []);
    $mode = $mode ?? 'load';
    $variant = $variant ?? 'button';
    $label = $label ?? 'Listen';
@endphp
{{-- This partial sits in the global sidebar (partials/sidebar-tw), so it renders on
     every page. If the player route is ever unavailable (e.g. a stale route cache
     during a deploy, EVENTREPO-Z6), a bare route('player.show') would 500 every page.
     Guard it so a missing route degrades to simply hiding the link instead. --}}
@if (Route::has('player.show'))
<a href="{{ route('player.show') }}{{ $query !== '' ? '?'.$query : '' }}"
    target="arcane-player"
    data-player-launch
    data-player-mode="{{ $mode }}"
    data-player-query="{{ $query }}"
    @if ($variant === 'icon') aria-label="{{ $label }}" title="{{ $label }}" @endif
    @class([
        'nav-item-tw' => $variant === 'nav',
        'inline-flex items-center gap-2 px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors' => $variant === 'button',
        'p-2 rounded-md hover:bg-accent' => $variant === 'icon',
        'inline-flex items-center gap-2 px-4 py-2 bg-card border-2 border-primary text-foreground rounded-lg hover:bg-accent transition-colors text-sm font-medium' => $variant === 'chip',
    ])>
    <i @class(['bi bi-headphones', 'text-lg' => $variant === 'nav', 'text-xl text-muted-foreground' => $variant === 'icon']) aria-hidden="true"></i>
    <span data-player-label @class(['sr-only' => $variant === 'icon'])>{{ $label }}</span>
</a>
@endif
