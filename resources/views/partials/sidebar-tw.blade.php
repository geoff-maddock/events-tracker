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
    <nav aria-label="Main" class="flex-1 min-h-0 overflow-y-auto overscroll-contain px-4 py-1 space-y-1">
        @include('partials.nav-items-tw', ['mobile' => false])
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
<div id="mobile-sidebar-overlay" class="hidden lg:hidden fixed inset-0 bg-black/50 z-40"></div>

<!-- Mobile Sidebar -->
<div id="mobile-sidebar" inert role="dialog" aria-modal="true" aria-label="Navigation menu" class="sidebar fixed inset-y-0 left-0 z-50 w-64 transform -translate-x-full transition-transform duration-200 ease-in-out lg:hidden bg-card border-r border-border flex flex-col">
    <!-- Close button -->
    <button type="button" id="mobile-sidebar-close" class="absolute top-4 right-4 p-2 rounded text-muted-foreground hover:text-foreground" aria-label="Close navigation menu">
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
    <nav aria-label="Main" class="flex-1 px-4 py-2 space-y-1 overflow-y-auto">
        @include('partials.nav-items-tw', ['mobile' => true])
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
</div>

{{-- The drawer is off-screen by transform only, so while closed it is `inert`:
     its links are out of the tab order and hidden from screen readers (#2411).
     Focus moving in and back out is resources/assets/js/dialog-focus.js. --}}
<script>
    (function () {
        const drawer = document.getElementById('mobile-sidebar');
        const overlay = document.getElementById('mobile-sidebar-overlay');
        const isOpen = () => !drawer.inert;

        function setOpen(open) {
            drawer.classList.toggle('-translate-x-full', !open);
            overlay.classList.toggle('hidden', !open);
            drawer.inert = !open;
            // the menu button is in the top bar, which is rendered after this script
            document.getElementById('mobile-menu-toggle-top')?.setAttribute('aria-expanded', String(open));
        }

        document.addEventListener('click', function (event) {
            if (event.target.closest('#mobile-menu-toggle-top')) {
                setOpen(!isOpen());
            } else if (event.target.closest('#mobile-sidebar-close') || event.target === overlay) {
                setOpen(false);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && isOpen()) {
                setOpen(false);
            }
        });
    })();
</script>
