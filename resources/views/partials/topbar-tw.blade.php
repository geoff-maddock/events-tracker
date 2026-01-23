<!-- Mobile Top Bar -->
<header class="md:hidden border-b border-border bg-card">
    <div class="flex items-center justify-between px-4 py-3 gap-3">
        <!-- Hamburger Menu -->
        <button id="mobile-menu-toggle-top" class="text-foreground hover:text-muted-foreground transition-colors flex-shrink-0">
            <i class="bi bi-list text-2xl"></i>
        </button>

        <!-- Logo for mobile -->
        <a href="{{ url('/') }}" class="flex flex-col leading-tight flex-shrink-0">
            <span class="text-base font-bold text-foreground whitespace-nowrap">{{ config('app.app_name') }}</span>
            <span class="text-sm text-muted-foreground">{{ config('app.app_tagline', 'pittsburgh events guide') }}</span>
        </a>

        <!-- Mobile Search -->
        <form class="flex flex-1 min-w-0" role="search" action="/search">
            <div class="relative w-full">
                <input type="text"
                    class="w-full pl-8 pr-3 py-1.5 bg-transparent border border-input rounded-lg text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                    placeholder="Search..."
                    name="keyword"
                    title="Search"
                    aria-label="Search"
                    value="{{ isset($search) ? $search : '' }}">
                <i class="bi bi-search absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground text-sm"></i>
            </div>
        </form>

        <!-- Right side actions -->
        <div class="flex items-center gap-2 flex-shrink-0">
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
