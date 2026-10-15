<!-- Sidebar Navigation -->
{{-- Pinned to the viewport height (#2309): header and footer stay put and only
     the nav list scrolls, so the page itself never gets a second scrollbar. --}}
<aside class="sidebar sidebar-desktop-tw hidden lg:flex lg:flex-col lg:w-64 lg:h-screen shrink-0 bg-card border-r border-border">
    <!-- Logo/Brand -->
    <div class="sidebar-block-tw border-b border-border text-center shrink-0">
        <a href="{{ url('/') }}" class="flex items-center justify-center gap-2">
            <span class="text-xl font-bold text-foreground">{{ config('app.app_name') }}</span>
        </a>
        <span class="text-xs text-muted-foreground block">{{ config('app.app_tagline', 'pittsburgh events guide') }}</span>
    </div>

    <!-- Search and add -->
    <div class="sidebar-block-tw flex items-center gap-2 shrink-0">
        <div class="flex-1 min-w-0">
            @include('partials.search-autocomplete', ['variant' => 'tw', 'inputId' => 'search-sidebar'])
        </div>
        @include('partials.add-menu-tw')
    </div>

    <!-- Navigation -->
    <nav class="flex-1 min-h-0 overflow-y-auto overscroll-contain px-4 py-1 space-y-1">
        <!-- Events Section -->
        <div>
            @auth
            <a href="{{ route('radar') }}" class="nav-item-tw {{ Request::is('radar') ? 'nav-item-active-tw' : '' }}">
                <i class="bi bi-broadcast text-lg"></i>
                <span>Your Radar</span>
            </a>
            @endauth

            <a href="{{ url('/events') }}" class="nav-item-tw {{ Request::is('events') && !Request::is('events/*') ? 'nav-item-active-tw' : '' }}">
                <i class="bi bi-calendar-event text-lg"></i>
                <span>Events</span>
            </a>
            <div class="ml-8 mt-0.5 space-y-0.5">
                <a href="{{ url('/events/tonight') }}" class="nav-subitem-tw {{ Request::is('events/tonight') ? 'nav-item-active-tw' : '' }}">
                    <i class="bi bi-moon-stars"></i>
                    <span>Tonight</span>
                </a>
                <a href="{{ url('/events/this-week') }}" class="nav-subitem-tw {{ Request::is('events/this-week') ? 'nav-item-active-tw' : '' }}">
                    <i class="bi bi-calendar-range"></i>
                    <span>This Week</span>
                </a>
                <a href="{{ url('/events/this-weekend') }}" class="nav-subitem-tw {{ Request::is('events/this-weekend') ? 'nav-item-active-tw' : '' }}">
                    <i class="bi bi-calendar-week"></i>
                    <span>This Weekend</span>
                </a>
                <a href="{{ url('/events/grid') }}" class="nav-subitem-tw {{ Request::is('events/grid') ? 'nav-item-active-tw' : '' }}">
                    <i class="bi bi-grid"></i>
                    <span>Event Grid</span>
                </a>
            </div>

        </div>

        <!-- Calendar -->
        <a href="{{ url('/calendar') }}" class="nav-item-tw {{ Request::is('calendar') && !Request::is('calendar/attending') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-calendar3 text-lg"></i>
            <span>Event Calendar</span>
        </a>

        <!-- Entity Listings -->
        <a href="{{ url('/entities') }}" class="nav-item-tw {{ Request::is('entities') && !Request::is('entities/*') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-people text-lg"></i>
            <span>Entities</span>
        </a>

        <!-- Series Listings -->
        <a href="{{ url('/series') }}" class="nav-item-tw {{ Request::is('series') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-collection text-lg"></i>
            <span>Series</span>
        </a>

        <!-- Tags -->
        <a href="{{ url('/tags') }}" class="nav-item-tw {{ Request::is('tags') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-tags text-lg"></i>
            <span>Tags</span>
        </a>


        <!-- Forum -->
        <a href="{{ url('/threads/all') }}" class="nav-item-tw {{ Request::is('threads*') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-chat-dots text-lg"></i>
            <span>Forum</span>
        </a>

        <!-- Users -->
        @if (!Auth::guest())
        <a href="{{ url('/users') }}" class="nav-item-tw {{ Request::is('users') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-person text-lg"></i>
            <span>Users</span>
        </a>
        @endif

        <!-- Divider -->
        <div class="border-t border-border my-2"></div>

        <!-- About -->
        <a href="{{ url('/about') }}" class="nav-item-tw {{ Request::is('about') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-info-circle text-lg"></i>
            <span>About</span>
        </a>

        <!-- Menu Items -->
        @if (isset($menus) && $menus->isNotEmpty())
        @foreach ($menus as $menu)
        <a href="{{ url('/menus/'.$menu->id.'/content') }}" class="nav-item-tw">
            <i class="bi bi-info-circle text-lg"></i>
            <span>{{ $menu->name }}</span>
        </a>
        @endforeach
        @endif

        @isset ($hasForum)
        <a href="{{ url('/threads') }}" class="nav-item-tw {{ Request::is('threads') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-chat-dots text-lg"></i>
            <span>Forum</span>
        </a>
        @endisset

        <a href="{{ url('/help') }}" class="nav-item-tw {{ Request::is('help') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-question-circle text-lg"></i>
            <span>Help</span>
        </a>

    </nav>

    <!-- Account and theme, on one row -->
    <div class="sidebar-block-tw border-t border-border shrink-0 flex items-center gap-2">
        @if (Auth::guest())
        <div class="flex-1 min-w-0 flex items-center gap-1 text-sm">
            <a href="{{ url('/login') }}" class="nav-item-tw px-2">
                <i class="bi bi-person-circle text-lg"></i>
                <span>Log in</span>
            </a>
            <a href="{{ url('/register') }}" class="nav-item-tw px-2">
                <span>Register</span>
            </a>
        </div>
        @else
        <div class="relative flex-1 min-w-0" x-data="{ open: false }" @keydown.escape.window="open = false">
            <button @click="open = !open" type="button" class="nav-item-tw w-full text-left {{ Request::is('users/'.Auth::id()) || Request::is('all-modules') ? 'nav-item-active-tw' : '' }}"
                aria-haspopup="true" :aria-expanded="open">
                <i class="bi bi-person-circle text-lg shrink-0" aria-hidden="true"></i>
                <span class="truncate flex-1">{{ Auth::user()->name }}</span>
                <i class="bi bi-chevron-up text-xs shrink-0" aria-hidden="true"></i>
            </button>
            <div x-show="open" @click.away="open = false" style="display: none"
                class="absolute left-0 bottom-full mb-1 w-full bg-card border border-border rounded-lg shadow-lg z-50">
                <div class="py-1">
                    <a href="{{ url('/users/'.Auth::user()->id) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-foreground hover:bg-accent transition-colors">
                        <i class="bi bi-person"></i>
                        Your Profile
                    </a>
                    @can('admin')
                    <a href="{{ route('pages.allModules') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-foreground hover:bg-accent transition-colors">
                        <i class="bi bi-grid-3x3-gap"></i>
                        All Modules
                    </a>
                    @endcan
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-foreground hover:bg-accent transition-colors text-left">
                            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                            Log out
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif
        <x-theme-toggle />
    </div>
</aside>



<!-- Mobile Sidebar Overlay -->
<div id="mobile-sidebar-overlay" class="hidden lg:hidden fixed inset-0 bg-black/50 z-40" onclick="closeMobileSidebar()"></div>

<!-- Mobile Sidebar -->
<aside id="mobile-sidebar" class="sidebar fixed inset-y-0 left-0 z-50 w-64 transform -translate-x-full transition-transform duration-200 ease-in-out lg:hidden bg-card border-r border-border flex flex-col">
    <!-- Close button -->
    <button onclick="closeMobileSidebar()" class="absolute top-4 right-4 p-2 rounded text-muted-foreground hover:text-foreground" aria-label="Close navigation">
        <i class="bi bi-x-lg text-xl" aria-hidden="true"></i>
    </button>

    <!-- Logo/Brand -->
    <div class="p-4 border-b border-border shrink-0">
        <a href="{{ url('/') }}" class="flex items-center gap-2">
            <span class="text-xl font-bold text-foreground">{{ config('app.app_name') }}</span>
        </a>
        <span class="text-xs text-muted-foreground">{{ config('app.app_tagline', 'pittsburgh events guide') }}</span>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-2 space-y-1 overflow-y-auto">
        <!-- Events Section -->
        <div>
            @auth
            <a href="{{ route('radar') }}" class="nav-item-tw {{ Request::is('radar') ? 'nav-item-active-tw' : '' }}">
                <i class="bi bi-broadcast text-lg"></i>
                <span>Your Radar</span>
            </a>
            @endauth
            <a href="{{ url('/events') }}" class="nav-item-tw {{ Request::is('events') && !Request::is('events/*') ? 'nav-item-active-tw' : '' }}">
                <i class="bi bi-calendar-event text-lg"></i>
                <span>Events</span>
            </a>
            <div class="ml-8 space-y-1 mt-1">
                <a href="{{ url('/events/tonight') }}" class="nav-item-tw text-sm {{ Request::is('events/tonight') ? 'nav-item-active-tw' : '' }}">
                    <i class="bi bi-moon-stars text-sm"></i>
                    <span>Tonight</span>
                </a>
                <a href="{{ url('/events/this-week') }}" class="nav-item-tw text-sm {{ Request::is('events/this-week') ? 'nav-item-active-tw' : '' }}">
                    <i class="bi bi-calendar-range text-sm"></i>
                    <span>This Week</span>
                </a>
                <a href="{{ url('/events/this-weekend') }}" class="nav-item-tw text-sm {{ Request::is('events/this-weekend') ? 'nav-item-active-tw' : '' }}">
                    <i class="bi bi-calendar-week text-sm"></i>
                    <span>This Weekend</span>
                </a>
                <a href="{{ url('/events/grid') }}" class="nav-item-tw text-sm {{ Request::is('events/grid') ? 'nav-item-active-tw' : '' }}">
                    <i class="bi bi-grid text-sm"></i>
                    <span>Event Grid</span>
                </a>
            </div>
        </div>

        <!-- Calendar (hidden on the smallest viewports) -->
        <div class="pt-2 hidden sm:block">
            <a href="{{ url('/calendar') }}" class="nav-item-tw {{ Request::is('calendar') && !Request::is('calendar/attending') ? 'nav-item-active-tw' : '' }}">
                <i class="bi bi-calendar3 text-lg"></i>
                <span>Event Calendar</span>
            </a>
        </div>

        <!-- Entity Listings -->
        <div class="pt-2">
            <a href="{{ url('/entities') }}" class="nav-item-tw {{ Request::is('entities') && !Request::is('entities/*') ? 'nav-item-active-tw' : '' }}">
                <i class="bi bi-people text-lg"></i>
                <span>Entities</span>
            </a>
        </div>

        <!-- Series Listings -->
        <a href="{{ url('/series') }}" class="nav-item-tw {{ Request::is('series') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-collection text-lg"></i>
            <span>Series</span>
        </a>

        <!-- Tags -->
        <a href="{{ url('/tags') }}" class="nav-item-tw {{ Request::is('tags') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-tags text-lg"></i>
            <span>Tags</span>
        </a>

        <!-- Forum -->
        <a href="{{ url('/threads/all') }}" class="nav-item-tw {{ Request::is('threads/*') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-chat-dots text-lg"></i>
            <span>Forum</span>
        </a>


        <!-- Users -->
        @auth
        <a href="{{ url('/users') }}" class="nav-item-tw {{ Request::is('users') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-person text-lg"></i>
            <span>Users</span>
        </a>
        @endauth

        <!-- Divider -->
        <div class="border-t border-border my-4"></div>

        <!-- About -->
        <a href="{{ url('/about') }}" class="nav-item-tw {{ Request::is('about') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-info-circle text-lg"></i>
            <span>About</span>
        </a>

        <!-- Menu Items -->
        @if (isset($menus) && $menus->isNotEmpty())
        @foreach ($menus as $menu)
        <a href="{{ url('/menus/'.$menu->id.'/content') }}" class="nav-item-tw">
            <i class="bi bi-info-circle text-lg"></i>
            <span>{{ $menu->name }}</span>
        </a>
        @endforeach
        @endif


        @isset ($hasForum)
        <a href="{{ url('/threads') }}" class="nav-item-tw {{ Request::is('threads/*') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-chat-dots text-lg"></i>
            <span>Forum</span>
        </a>
        @endisset

        <a href="{{ url('/help') }}" class="nav-item-tw {{ Request::is('help') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-question-circle text-lg"></i>
            <span>Help</span>
        </a>

    </nav>

    <!-- User Section -->
    <div class="p-4 border-t border-border shrink-0">
        @if (Auth::guest())
        <a href="{{ url('/login') }}" class="nav-item-tw">
            <i class="bi bi-person-circle text-lg"></i>
            <span>My Account</span>
        </a>
        <a href="{{ url('/register') }}" class="nav-item-tw mt-1">
            <i class="bi bi-box-arrow-in-right text-lg"></i>
            <span>Register</span>
        </a>
        @else
        <div class="flex items-center gap-3 px-3 py-2 text-muted-foreground">
            <i class="bi bi-person-circle text-lg shrink-0"></i>
            <a href="{{ url('/users/'.Auth::user()->id) }}" class="hover:text-foreground truncate">{{ Auth::user()->name }}</a>
        </div>
        @can('admin')
        <a href="{{ route('pages.allModules') }}" class="nav-item-tw mt-1 {{ Request::is('all-modules') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-grid-3x3-gap text-lg"></i>
            <span>All Modules</span>
        </a>
        @endcan
        @endif
    </div>

    <!-- Theme Toggle & Logout -->
    <div class="p-4 border-t border-border shrink-0">
        <div class="flex items-center justify-between gap-4">
            <x-theme-toggle />
            @auth
            <a href="{{ route('logout') }}"
                onclick="event.preventDefault(); document.getElementById('mobile-sidebar-logout-form').submit();"
                class="nav-item-tw">
                <i class="bi bi-box-arrow-right text-lg"></i>
                <span>Log out</span>
            </a>
            <form id="mobile-sidebar-logout-form" action="{{ route('logout') }}" method="POST" class="hidden">{{ csrf_field() }}</form>
            @endauth
        </div>
    </div>
</aside>

<script>
    function toggleMobileSidebar() {
        const sidebar = document.getElementById('mobile-sidebar');
        const overlay = document.getElementById('mobile-sidebar-overlay');
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }
    
    function closeMobileSidebar() {
        const sidebar = document.getElementById('mobile-sidebar');
        const overlay = document.getElementById('mobile-sidebar-overlay');
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    }
    
    // Support both menu toggle buttons
    document.getElementById('mobile-menu-toggle')?.addEventListener('click', toggleMobileSidebar);
    document.getElementById('mobile-menu-toggle-top')?.addEventListener('click', toggleMobileSidebar);
</script>
