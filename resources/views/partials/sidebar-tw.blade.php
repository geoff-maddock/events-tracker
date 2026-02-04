<!-- Sidebar Navigation -->
<aside class="sidebar hidden lg:flex lg:flex-col lg:w-64 min-h-screen bg-card border-r border-border">
    <!-- Logo/Brand -->
    <div class="p-4 border-b border-border text-center">
        <a href="{{ url('/') }}" class="flex items-center justify-center gap-2">
            <span class="text-xl font-bold text-foreground">{{ config('app.app_name') }}</span>
        </a>
        <span class="text-xs text-muted-foreground block">{{ config('app.app_tagline', 'pittsburgh events guide') }}</span>
    </div>

    <!-- Search -->
    <div class="p-4">
        <form role="search" action="/search">
            <div class="relative">
                <input type="text"
                    class="w-full pl-10 pr-4 py-2 bg-transparent border border-input rounded-lg text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                    placeholder="Search"
                    name="keyword"
                    title="Search"
                    aria-label="Search"
                    value="{{ isset($search) ? $search : '' }}">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground"></i>
            </div>
        </form>
    </div>

    <!-- Navigation -->
    <nav class="px-4 py-2 space-y-1">
        <!-- Your Radar (logged in users only) -->


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
                <span>Event Listings</span>
            </a>
            <div class="ml-8 space-y-1 mt-1">
                <a href="{{ url('/events/grid') }}" class="nav-item-tw text-sm {{ Request::is('events/grid') ? 'nav-item-active-tw' : '' }}">
                    <i class="bi bi-grid text-sm"></i>
                    <span>Event Grid</span>
                </a>
                 @auth
                <a href="{{ url('/events/attending') }}" class="nav-item-tw text-sm {{ Request::is('events/attending') ? 'nav-item-active-tw' : '' }}">
                    <i class="bi bi-calendar-check text-sm"></i>
                    <span>Your Events</span>
                </a>
                @endauth
            </div>

        </div>

        <!-- Calendar -->
        <div class="pt-2">
            <a href="{{ url('/calendar') }}" class="nav-item-tw {{ Request::is('calendar') && !Request::is('calendar/attending') ? 'nav-item-active-tw' : '' }}">
                <i class="bi bi-calendar3 text-lg"></i>
                <span>Event Calendar</span>
            </a>
            @auth
            <div class="ml-8 space-y-1 mt-1">
                <a href="{{ url('/calendar/attending') }}" class="nav-item-tw text-sm {{ Request::is('calendar/attending') ? 'nav-item-active-tw' : '' }}">
                    <i class="bi bi-calendar-check text-sm"></i>
                    <span>Your Calendar</span>
                </a>
            </div>
            @endauth
        </div>

        <!-- Entity Listings -->
        <div class="pt-2">
            <a href="{{ url('/entities') }}" class="nav-item-tw {{ Request::is('entities') && !Request::is('entities/*') ? 'nav-item-active-tw' : '' }}">
                <i class="bi bi-people text-lg"></i>
                <span>Entity Listings</span>
            </a>
            @auth
            <div class="ml-8 space-y-1 mt-1">
                <a href="{{ url('/entities/following') }}" class="nav-item-tw text-sm {{ Request::is('entities/following') ? 'nav-item-active-tw' : '' }}">
                    <i class="bi bi-person-heart text-sm"></i>
                    <span>Your Entities</span>
                </a>
            </div>
            @endauth
        </div>

        <!-- Series Listings -->
        <a href="{{ url('/series') }}" class="nav-item-tw {{ Request::is('series') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-collection text-lg"></i>
            <span>Series Listings</span>
        </a>

        <!-- Tags -->
        <a href="{{ url('/tags') }}" class="nav-item-tw {{ Request::is('tags') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-tags text-lg"></i>
            <span>Tags</span>
        </a>

        <!-- Users -->
        @if (!Auth::guest())
        <a href="{{ url('/users') }}" class="nav-item-tw {{ Request::is('users') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-person text-lg"></i>
            <span>Users</span>
        </a>
        @endif

        <!-- Divider -->
        <div class="border-t border-border my-4"></div>

        <!-- About -->
        <a href="{{ url('/menus/1/content') }}" class="nav-item-tw {{ Request::is('menus/1/content') ? 'nav-item-active-tw' : '' }}">
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

        <a href="{{ url('/privacy') }}" class="nav-item-tw {{ Request::is('privacy') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-shield-check text-lg"></i>
            <span>Privacy</span>
        </a>
    </nav>

    <!-- User Section -->
    <div class="p-4 border-t border-border">
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
            <i class="bi bi-person-circle text-lg"></i>
            <a href="{{ url('/users/'.Auth::user()->id) }}" class="hover:text-foreground">{{ Auth::user()->name }}</a>
        </div>
        @can('admin')
        <a href="{{ route('pages.allModules') }}" class="nav-item-tw mt-1 {{ Request::is('all-modules') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-grid-3x3-gap text-lg"></i>
            <span>All Modules</span>
        </a>
        @endcan
        <a href="{{ route('logout') }}"
            onclick="event.preventDefault(); document.getElementById('sidebar-logout-form').submit();"
            class="nav-item-tw mt-1">
            <i class="bi bi-box-arrow-right text-lg"></i>
            <span>Log out</span>
        </a>
        <form id="sidebar-logout-form" action="{{ route('logout') }}" method="POST" class="hidden">{{ csrf_field() }}</form>
        @endif
    </div>

    <!-- Theme Toggle -->
    <div class="p-4 border-t border-border">
        <x-theme-toggle />
    </div>
</aside>



<!-- Mobile Sidebar Overlay -->
<div id="mobile-sidebar-overlay" class="hidden lg:hidden fixed inset-0 bg-black/50 z-40" onclick="closeMobileSidebar()"></div>

<!-- Mobile Sidebar -->
<aside id="mobile-sidebar" class="sidebar fixed inset-y-0 left-0 z-50 w-64 transform -translate-x-full transition-transform duration-200 ease-in-out lg:hidden bg-card border-r border-border flex flex-col">
    <!-- Close button -->
    <button onclick="closeMobileSidebar()" class="absolute top-4 right-4 text-muted-foreground hover:text-foreground">
        <i class="bi bi-x-lg text-xl"></i>
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
                <span>Event Listings</span>
            </a>
            <div class="ml-8 space-y-1 mt-1">
                <a href="{{ url('/events/grid') }}" class="nav-item-tw text-sm {{ Request::is('events/grid') ? 'nav-item-active-tw' : '' }}">
                    <i class="bi bi-grid text-sm"></i>
                    <span>Event Grid</span>
                </a>
                @auth
                <a href="{{ url('/events/attending') }}" class="nav-item-tw text-sm {{ Request::is('events/attending') ? 'nav-item-active-tw' : '' }}">
                    <i class="bi bi-calendar-check text-sm"></i>
                    <span>Your Events</span>
                </a>
                @endauth
            </div>
        </div>

        <!-- Calendar -->
        <div class="pt-2">
            <a href="{{ url('/calendar') }}" class="nav-item-tw {{ Request::is('calendar') && !Request::is('calendar/attending') ? 'nav-item-active-tw' : '' }}">
                <i class="bi bi-calendar3 text-lg"></i>
                <span>Event Calendar</span>
            </a>
            @auth
            <div class="ml-8 space-y-1 mt-1">
                <a href="{{ url('/calendar/attending') }}" class="nav-item-tw text-sm {{ Request::is('calendar/attending') ? 'nav-item-active-tw' : '' }}">
                    <i class="bi bi-calendar-check text-sm"></i>
                    <span>Your Calendar</span>
                </a>
            </div>
            @endauth
        </div>

        <!-- Entity Listings -->
        <div class="pt-2">
            <a href="{{ url('/entities') }}" class="nav-item-tw {{ Request::is('entities') && !Request::is('entities/*') ? 'nav-item-active-tw' : '' }}">
                <i class="bi bi-people text-lg"></i>
                <span>Entity Listings</span>
            </a>
            @auth
            <div class="ml-8 space-y-1 mt-1">
                <a href="{{ url('/entities/following') }}" class="nav-item-tw text-sm {{ Request::is('entities/following') ? 'nav-item-active-tw' : '' }}">
                    <i class="bi bi-person-heart text-sm"></i>
                    <span>Your Entities</span>
                </a>
            </div>
            @endauth
        </div>

        <!-- Series Listings -->
        <a href="{{ url('/series') }}" class="nav-item-tw {{ Request::is('series') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-collection text-lg"></i>
            <span>Series Listings</span>
        </a>

        <!-- Tags -->
        <a href="{{ url('/tags') }}" class="nav-item-tw {{ Request::is('tags') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-tags text-lg"></i>
            <span>Tags</span>
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

        <a href="{{ url('/privacy') }}" class="nav-item-tw {{ Request::is('privacy') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-shield-check text-lg"></i>
            <span>Privacy</span>
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
        <a href="{{ route('logout') }}"
            onclick="event.preventDefault(); document.getElementById('mobile-sidebar-logout-form').submit();"
            class="nav-item-tw mt-1">
            <i class="bi bi-box-arrow-right text-lg"></i>
            <span>Log out</span>
        </a>
        <form id="mobile-sidebar-logout-form" action="{{ route('logout') }}" method="POST" class="hidden">{{ csrf_field() }}</form>
        @endif
    </div>

    <!-- Theme Toggle -->
    <div class="p-4 border-t border-border shrink-0">
        <x-theme-toggle />
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
