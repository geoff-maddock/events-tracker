<!-- Sidebar Navigation -->
<aside class="sidebar hidden md:flex md:flex-col md:w-64 min-h-screen">
    <!-- Logo/Brand -->
    <div class="p-4 border-b border-dark-border">
        <a href="{{ url('/') }}" class="flex items-center gap-2">
            <span class="text-xl font-bold text-white dark:text-white light:text-gray-900">{{ config('app.app_name') }}</span>
        </a>
        <span class="text-xs text-gray-400">{{ config('app.app_tagline', 'pittsburgh events guide') }}</span>
    </div>

    <!-- Search -->
    <div class="p-4">
        <form role="search" action="/search">
            <div class="relative">
                <input type="text" 
                    class="w-full pl-10 pr-4 py-2 bg-dark-card border border-dark-border rounded-lg text-sm text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" 
                    placeholder="Search" 
                    name="keyword" 
                    title="Search" 
                    aria-label="Search" 
                    value="{{ isset($search) ? $search : '' }}">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>
        </form>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-2 space-y-1 overflow-y-auto">
        <!-- Your Radar -->
        <a href="{{ url('/events/attending') }}" class="nav-item-tw {{ Request::is('events/attending') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-broadcast text-lg"></i>
            <span>Your Radar</span>
        </a>

        <!-- Events Section -->
        <div class="pt-4">
            <a href="{{ url('/events') }}" class="nav-item-tw {{ Request::is('events') && !Request::is('events/*') ? 'nav-item-active-tw' : '' }}">
                <i class="bi bi-calendar-event text-lg"></i>
                <span>Event Listings</span>
            </a>
            <div class="ml-8 space-y-1 mt-1">
                <a href="{{ url('/events/grid') }}" class="nav-item-tw text-sm {{ Request::is('events/grid') ? 'nav-item-active-tw' : '' }}">
                    <i class="bi bi-grid text-sm"></i>
                    <span>Event Grid</span>
                </a>
            </div>
        </div>

        <!-- Calendar -->
        <a href="{{ url('/calendar') }}" class="nav-item-tw {{ Request::is('calendar') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-calendar3 text-lg"></i>
            <span>Event Calendar</span>
        </a>

        <!-- Your Calendar -->
        <a href="{{ url('/calendar/attending') }}" class="nav-item-tw {{ Request::is('calendar/attending') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-calendar-check text-lg"></i>
            <span>Your Calendar</span>
        </a>

        <!-- Entity Listings -->
        <div class="pt-2">
            <a href="{{ url('/entities') }}" class="nav-item-tw {{ Request::is('entities') && !Request::is('entities/*') ? 'nav-item-active-tw' : '' }}">
                <i class="bi bi-people text-lg"></i>
                <span>Entity Listings</span>
            </a>
            <div class="ml-8 space-y-1 mt-1">
                <a href="{{ url('/entities/following') }}" class="nav-item-tw text-sm {{ Request::is('entities/following') ? 'nav-item-active-tw' : '' }}">
                    <i class="bi bi-person-heart text-sm"></i>
                    <span>Your Entities</span>
                </a>
            </div>
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

        <!-- Search -->
        <a href="{{ url('/search') }}" class="nav-item-tw {{ Request::is('search') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-search text-lg"></i>
            <span>Search</span>
        </a>

        <!-- Divider -->
        <div class="border-t border-dark-border my-4"></div>

        <!-- Menu Items -->
        @if (isset($menus) && $menus->isNotEmpty())
        @foreach ($menus as $menu)
        <a href="{{ url('/menus/'.$menu->id.'/content') }}" class="nav-item-tw">
            <i class="bi bi-info-circle text-lg"></i>
            <span>{{ $menu->name }}</span>
        </a>
        @endforeach
        @endif

        <!-- Help Section -->
        <a href="{{ url('/about') }}" class="nav-item-tw {{ Request::is('about') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-info-circle text-lg"></i>
            <span>About</span>
        </a>

        @isset ($hasForum)
        <a href="{{ url('/threads') }}" class="nav-item-tw {{ Request::is('threads') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-chat-dots text-lg"></i>
            <span>Blogs</span>
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
    <div class="p-4 border-t border-dark-border">
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
        <div class="flex items-center gap-3 px-3 py-2 text-gray-300">
            <i class="bi bi-person-circle text-lg"></i>
            <a href="{{ url('/users/'.Auth::user()->id) }}" class="hover:text-white">{{ Auth::user()->name }}</a>
        </div>
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
    <div class="p-4 border-t border-dark-border">
        <button onclick="toggleTheme()" class="theme-toggle-tw w-full justify-center">
            <i class="bi bi-moon-fill dark:block hidden"></i>
            <i class="bi bi-sun-fill dark:hidden block"></i>
            <span class="dark:hidden">Toggle Dark Mode</span>
            <span class="hidden dark:inline">Toggle Light Mode</span>
        </button>
    </div>
</aside>

<!-- Mobile Menu Button -->
<button id="mobile-menu-toggle" class="md:hidden fixed bottom-4 left-4 z-50 p-3 bg-primary text-white rounded-full shadow-lg hover:bg-primary-hover transition-colors">
    <i class="bi bi-list text-xl"></i>
</button>

<!-- Mobile Sidebar Overlay -->
<div id="mobile-sidebar-overlay" class="hidden md:hidden fixed inset-0 bg-black/50 z-40" onclick="closeMobileSidebar()"></div>

<!-- Mobile Sidebar -->
<aside id="mobile-sidebar" class="sidebar fixed inset-y-0 left-0 z-50 w-64 transform -translate-x-full transition-transform duration-200 ease-in-out md:hidden">
    <!-- Close button -->
    <button onclick="closeMobileSidebar()" class="absolute top-4 right-4 text-gray-400 hover:text-white">
        <i class="bi bi-x-lg text-xl"></i>
    </button>
    
    <!-- Logo/Brand -->
    <div class="p-4 border-b border-dark-border">
        <a href="{{ url('/') }}" class="flex items-center gap-2">
            <span class="text-xl font-bold text-white">{{ config('app.app_name') }}</span>
        </a>
        <span class="text-xs text-gray-400">{{ config('app.app_tagline', 'pittsburgh events guide') }}</span>
    </div>

    <!-- Same navigation as desktop -->
    <nav class="flex-1 px-4 py-2 space-y-1 overflow-y-auto">
        <a href="{{ url('/events/attending') }}" class="nav-item-tw {{ Request::is('events/attending') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-broadcast text-lg"></i>
            <span>Your Radar</span>
        </a>

        <a href="{{ url('/events') }}" class="nav-item-tw {{ Request::is('events') && !Request::is('events/*') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-calendar-event text-lg"></i>
            <span>Event Listings</span>
        </a>

        <a href="{{ url('/calendar') }}" class="nav-item-tw {{ Request::is('calendar') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-calendar3 text-lg"></i>
            <span>Event Calendar</span>
        </a>

        <a href="{{ url('/entities') }}" class="nav-item-tw {{ Request::is('entities') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-people text-lg"></i>
            <span>Entity Listings</span>
        </a>

        <a href="{{ url('/series') }}" class="nav-item-tw {{ Request::is('series') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-collection text-lg"></i>
            <span>Series Listings</span>
        </a>

        <a href="{{ url('/tags') }}" class="nav-item-tw {{ Request::is('tags') ? 'nav-item-active-tw' : '' }}">
            <i class="bi bi-tags text-lg"></i>
            <span>Tags</span>
        </a>
    </nav>

    <!-- Theme Toggle -->
    <div class="p-4 border-t border-dark-border">
        <button onclick="toggleTheme()" class="theme-toggle-tw w-full justify-center">
            <i class="bi bi-moon-fill dark:block hidden"></i>
            <i class="bi bi-sun-fill dark:hidden block"></i>
            <span class="dark:hidden">Toggle Dark Mode</span>
            <span class="hidden dark:inline">Toggle Light Mode</span>
        </button>
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
    
    document.getElementById('mobile-menu-toggle')?.addEventListener('click', toggleMobileSidebar);
</script>
