<div x-data="{
    isDark: document.documentElement.classList.contains('dark'),
    toggle() {
        const nextIsDark = !document.documentElement.classList.contains('dark');
        if (document.documentElement.dataset.artistTheme) {
            window.dispatchEvent(new Event('artist-theme-reset'));
        }
        this.isDark = nextIsDark;

        // Update both html and body elements
        document.documentElement.classList.remove('dark', 'light');
        document.documentElement.classList.add(this.isDark ? 'dark' : 'light');
        document.body.classList.remove('dark', 'light');
        document.body.classList.add(this.isDark ? 'dark' : 'light');

        localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
        fetch('/theme/' + (this.isDark ? 'dark' : 'light'));
    }
}" @artist-theme-change.window="isDark = document.documentElement.classList.contains('dark')">
    <button
        @click="toggle()"
        class="inline-flex items-center justify-center rounded-md p-2 text-muted-foreground hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
        :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'">
        <i class="bi text-lg" :class="isDark ? 'bi-sun-fill' : 'bi-moon-fill'"></i>
    </button>
</div>
