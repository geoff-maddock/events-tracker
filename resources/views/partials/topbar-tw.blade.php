<!-- Mobile Top Bar -->
<header class="lg:hidden border-b border-border bg-card">
    <div class="flex items-center justify-between px-4 py-3 gap-3">
        <!-- Hamburger Menu -->
        <button id="mobile-menu-toggle-top" class="p-2 rounded text-foreground hover:text-muted-foreground transition-colors flex-shrink-0" aria-label="Open navigation menu" aria-expanded="false" aria-controls="mobile-sidebar">
            <i class="bi bi-list text-2xl" aria-hidden="true"></i>
        </button>

        <!-- Logo for mobile -->
        <a href="{{ url('/') }}" class="flex flex-col leading-tight flex-shrink-0">
            <span class="text-base font-bold text-foreground whitespace-nowrap">{{ config('app.app_name') }}</span>
            <span class="text-sm text-muted-foreground">{{ config('app.app_tagline', 'pittsburgh events guide') }}</span>
        </a>

        <!-- Mobile Search -->
        <div class="flex-1 min-w-0">
            @include('partials.search-autocomplete', ['variant' => 'tw-mobile', 'inputId' => 'search-topbar'])
        </div>

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
                        const isOpen = !sidebar.classList.contains('-translate-x-full');
                        sidebar.classList.toggle('-translate-x-full');
                        overlay.classList.toggle('hidden');
                        button.setAttribute('aria-expanded', String(isOpen ? 'false' : 'true'));
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
