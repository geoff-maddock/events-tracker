{{--
    The main navigation links, shared by the desktop sidebar and the mobile drawer
    (partials/sidebar-tw). Add, remove or reorder a link here and both follow.

    $mobile  bool  true in the mobile drawer: roomier sub-links for touch, and the
                   calendar link is left out on the smallest screens.
--}}
@php
    $mobile = $mobile ?? false;
    $subItemClass = $mobile ? 'nav-item-tw text-sm' : 'nav-subitem-tw';

    $navItems = [
        ['label' => 'Entities', 'url' => url('/entities'), 'icon' => 'bi-people', 'active' => Request::is('entities')],
        ['label' => 'Series', 'url' => url('/series'), 'icon' => 'bi-collection', 'active' => Request::is('series')],
        ['label' => 'Tags', 'url' => url('/tags'), 'icon' => 'bi-tags', 'active' => Request::is('tags')],
        ['label' => 'Forum', 'url' => url('/threads/all'), 'icon' => 'bi-chat-dots', 'active' => Request::is('threads*')],
    ];
    if (Auth::check()) {
        $navItems[] = ['label' => 'Users', 'url' => url('/users'), 'icon' => 'bi-person', 'active' => Request::is('users')];
    }

    $eventSubItems = [
        ['label' => 'Tonight', 'url' => url('/events/tonight'), 'icon' => 'bi-moon-stars', 'active' => Request::is('events/tonight')],
        ['label' => 'This Week', 'url' => url('/events/this-week'), 'icon' => 'bi-calendar-range', 'active' => Request::is('events/this-week')],
        ['label' => 'This Weekend', 'url' => url('/events/this-weekend'), 'icon' => 'bi-calendar-week', 'active' => Request::is('events/this-weekend')],
        ['label' => 'Event Grid', 'url' => url('/events/grid'), 'icon' => 'bi-grid', 'active' => Request::is('events/grid')],
    ];
@endphp

<!-- Events Section -->
<div>
    @auth
    <a href="{{ route('radar') }}" @class(['nav-item-tw', 'nav-item-active-tw' => Request::is('radar')]) @if (Request::is('radar')) aria-current="page" @endif>
        <i class="bi bi-broadcast text-lg" aria-hidden="true"></i>
        <span>Your Radar</span>
    </a>
    @endauth

    <a href="{{ url('/events') }}" @class(['nav-item-tw', 'nav-item-active-tw' => Request::is('events')]) @if (Request::is('events')) aria-current="page" @endif>
        <i class="bi bi-calendar-event text-lg" aria-hidden="true"></i>
        <span>Events</span>
    </a>
    <div class="ml-8 mt-0.5 space-y-0.5">
        @foreach ($eventSubItems as $item)
        <a href="{{ $item['url'] }}" @class([$subItemClass, 'nav-item-active-tw' => $item['active']]) @if ($item['active']) aria-current="page" @endif>
            <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
            <span>{{ $item['label'] }}</span>
        </a>
        @endforeach
    </div>
</div>

<!-- Calendar (left out of the mobile drawer on the smallest screens) -->
<a href="{{ url('/calendar') }}" @class(['nav-item-tw', 'nav-item-active-tw' => Request::is('calendar'), 'hidden sm:flex' => $mobile]) @if (Request::is('calendar')) aria-current="page" @endif>
    <i class="bi bi-calendar3 text-lg" aria-hidden="true"></i>
    <span>Event Calendar</span>
</a>

<!-- Audio player (#2366) -->
@include('player.launch-tw', ['query' => ['source' => 'upcoming'], 'label' => 'Listen', 'variant' => 'nav'])

@foreach ($navItems as $item)
<a href="{{ $item['url'] }}" @class(['nav-item-tw', 'nav-item-active-tw' => $item['active']]) @if ($item['active']) aria-current="page" @endif>
    <i class="bi {{ $item['icon'] }} text-lg" aria-hidden="true"></i>
    <span>{{ $item['label'] }}</span>
</a>
@endforeach

<!-- Divider -->
<div class="border-t border-border my-2"></div>

<a href="{{ url('/about') }}" @class(['nav-item-tw', 'nav-item-active-tw' => Request::is('about')]) @if (Request::is('about')) aria-current="page" @endif>
    <i class="bi bi-info-circle text-lg" aria-hidden="true"></i>
    <span>About</span>
</a>

<!-- Menu Items -->
@if (isset($menus) && $menus->isNotEmpty())
@foreach ($menus as $menu)
<a href="{{ url('/menus/'.$menu->id.'/content') }}" class="nav-item-tw">
    <i class="bi bi-info-circle text-lg" aria-hidden="true"></i>
    <span>{{ $menu->name }}</span>
</a>
@endforeach
@endif

<a href="{{ url('/help') }}" @class(['nav-item-tw', 'nav-item-active-tw' => Request::is('help')]) @if (Request::is('help')) aria-current="page" @endif>
    <i class="bi bi-question-circle text-lg" aria-hidden="true"></i>
    <span>Help</span>
</a>
