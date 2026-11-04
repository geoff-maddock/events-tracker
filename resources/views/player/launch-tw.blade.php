{{--
    A link that opens the pop-out audio player (#2366); public/js/player-launcher.js
    turns the click into a reused popup window, or an "add to queue" message when the
    player is already open. Without JS the named target still opens one player tab.

    $query   array  queue parameters, e.g. ['source' => 'event', 'id' => 12]
    $mode    string 'load' (replace the queue) or 'enqueue' (add to an open player)
    $label   string visible text / accessible name
    $variant string 'nav' | 'button' | 'icon'
--}}
@php
    $query = http_build_query($query ?? []);
    $mode = $mode ?? 'load';
    $variant = $variant ?? 'button';
    $label = $label ?? 'Listen';
@endphp
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
    ])>
    <i @class(['bi bi-headphones', 'text-lg' => $variant === 'nav', 'text-xl text-muted-foreground' => $variant === 'icon']) aria-hidden="true"></i>
    <span data-player-label @class(['sr-only' => $variant === 'icon'])>{{ $label }}</span>
</a>
