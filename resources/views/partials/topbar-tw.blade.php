<!-- Top Bar - Hidden on larger screens, visible on smaller as we have sidebar -->
<header class="hidden border-b border-border bg-card">
    <div class="flex items-center justify-between px-4 py-3">
        <!-- Logo for mobile -->
        <a href="{{ url('/') }}" class="md:hidden flex items-center gap-2">
            <span class="text-lg font-bold text-foreground">{{ config('app.app_name') }}</span>
        </a>

        <!-- Desktop Search (shown when sidebar is hidden on smaller screens) -->
        <form class="hidden md:flex flex-1 max-w-md mx-4" role="search" action="/search">
            <div class="relative w-full">
                <input type="text"
                    class="w-full pl-10 pr-4 py-2 bg-transparent border border-input rounded-lg text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                    placeholder="Search events, venues, artists..."
                    name="keyword"
                    title="Search"
                    aria-label="Search"
                    value="{{ isset($search) ? $search : '' }}">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground"></i>
            </div>
        </form>

        <!-- Right side actions -->
        <div class="flex items-center gap-4">
            @if (Auth::guest())
            <a href="{{ url('/login') }}" class="text-muted-foreground hover:text-foreground transition-colors">
                <i class="bi bi-person-circle text-xl"></i>
            </a>
            @else
            <div class="relative group">
                <button class="flex items-center gap-2 text-muted-foreground hover:text-foreground transition-colors">
                    <i class="bi bi-person-circle text-xl"></i>
                    <span class="hidden md:inline">{{ Auth::user()->name }}</span>
                </button>
                <!-- Dropdown would go here -->
            </div>
            @endif

            <!-- Theme toggle -->
            <x-theme-toggle />
        </div>
    </div>
</header>
