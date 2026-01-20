<!-- Mobile Top Bar -->
<header class="md:hidden border-b border-border bg-card">
    <div class="flex items-center justify-between px-4 py-3">
        <!-- Hamburger Menu -->
        <button id="mobile-menu-toggle-top" class="text-foreground hover:text-muted-foreground transition-colors">
            <i class="bi bi-list text-2xl"></i>
        </button>

        <!-- Logo for mobile -->
        <a href="{{ url('/') }}" class="flex items-center gap-2">
            <span class="text-lg font-bold text-foreground">{{ config('app.app_name') }}</span>
            <span class="text-xs text-muted-foreground hidden sm:block">{{ config('app.app_tagline', 'pittsburgh events guide') }}</span>
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
            @auth
            <a href="{{ url('/users/'.Auth::user()->id) }}" class="text-muted-foreground hover:text-foreground transition-colors">
                @if ($photo = Auth::user()->getPrimaryPhoto())
                <img src="{{ Storage::disk('external')->url($photo->getStorageThumbnail()) }}" 
                     alt="{{ Auth::user()->name }}" 
                     class="w-8 h-8 rounded-full object-cover">
                @else
                <i class="bi bi-person-circle text-2xl"></i>
                @endif
            </a>
            @endauth

            <!-- Theme toggle -->
            <x-theme-toggle />
        </div>
    </div>
</header>

<script>
    // Ensure hamburger menu button works
    (function() {
        function setupMobileMenu() {
            const button = document.getElementById('mobile-menu-toggle-top');
            if (button && !button.hasAttribute('data-listener-added')) {
                button.setAttribute('data-listener-added', 'true');
                button.addEventListener('click', function() {
                    const sidebar = document.getElementById('mobile-sidebar');
                    const overlay = document.getElementById('mobile-sidebar-overlay');
                    if (sidebar && overlay) {
                        sidebar.classList.toggle('-translate-x-full');
                        overlay.classList.toggle('hidden');
                    }
                });
            }
        }
        
        // Run immediately
        setupMobileMenu();
        
        // Also run on DOMContentLoaded in case script runs before DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupMobileMenu);
        }
    })();
</script>
