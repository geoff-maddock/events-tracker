{{--
    Search autocomplete (Alpine.js).

    Variants:
      - $variant = 'tw'        (Tailwind/sidebar) -- default
      - $variant = 'tw-mobile' (Tailwind/topbar)  -- gets full-screen overlay below sm:
      - $variant = 'bs'        (legacy Bootstrap nav)

    Optional:
      - $inputId  -- DOM id for the input
      - $value    -- prefilled keyword

    The overlay variant is teleported to <body> via x-teleport so it escapes
    every potential ancestor stacking context (header, sidebar, layout flex
    containers, etc.) and reliably renders above all other site content.
--}}
@php
    $variant = $variant ?? 'tw';
    $inputId = $inputId ?? 'search-autocomplete-' . uniqid();
    $value   = $value ?? (isset($search) ? $search : '');

    $inputClass = match ($variant) {
        'tw-mobile' => 'w-full pl-8 pr-3 py-1.5 bg-transparent border border-input rounded-lg text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring',
        'bs'        => 'form-control form-background',
        default     => 'w-full pl-10 pr-4 py-2 bg-transparent border border-input rounded-lg text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent',
    };
@endphp

<div class="search-autocomplete-root relative"
     x-data="searchAutocomplete({ endpoint: '{{ url('/api/search') }}', target: '/search', variant: '{{ $variant }}' })"
     x-on:keydown.escape.window="overlay ? closeOverlay() : close()"
     x-on:click.outside="overlay ? null : close()">

    {{-- Inline trigger input (always rendered, sits in nav/sidebar/topbar) --}}
    <form role="search" action="/search"
          x-on:submit="onSubmit($event)"
          class="{{ $variant === 'bs' ? 'navbar-form navbar-left' : 'flex flex-1 min-w-0' }}">
        @if($variant === 'bs')
            <div class="form-group">
                <input type="text"
                    id="{{ $inputId }}"
                    x-ref="input"
                    class="{{ $inputClass }}"
                    placeholder="Search"
                    name="keyword"
                    autocomplete="off"
                    title="Search"
                    aria-label="Search"
                    aria-autocomplete="list"
                    role="combobox"
                    x-bind:aria-expanded="open"
                    x-model="query"
                    x-on:input.debounce.400ms="fetch()"
                    x-on:focus="onFocus()"
                    x-on:keydown.arrow-down.prevent="move(1)"
                    x-on:keydown.arrow-up.prevent="move(-1)"
                    x-on:keydown.enter="onEnter($event)"
                    value="{{ $value }}">
            </div>
        @else
            <div class="relative w-full">
                <input type="text"
                    id="{{ $inputId }}"
                    x-ref="input"
                    class="{{ $inputClass }}"
                    placeholder="{{ $variant === 'tw-mobile' ? 'Search...' : 'Search' }}"
                    name="keyword"
                    autocomplete="off"
                    title="Search"
                    aria-label="Search"
                    aria-autocomplete="list"
                    role="combobox"
                    x-bind:aria-expanded="open || overlay"
                    x-model="query"
                    x-on:input.debounce.400ms="fetch()"
                    x-on:focus="onFocus()"
                    x-on:keydown.arrow-down.prevent="move(1)"
                    x-on:keydown.arrow-up.prevent="move(-1)"
                    x-on:keydown.enter="onEnter($event)"
                    value="{{ $value }}">
                <i class="bi bi-search absolute {{ $variant === 'tw-mobile' ? 'left-2.5 text-sm' : 'left-3' }} top-1/2 -translate-y-1/2 text-muted-foreground"></i>
            </div>
        @endif
    </form>

    {{-- Inline dropdown (non-overlay desktop/tablet behavior) --}}
    <div x-show="open && !overlay"
         x-cloak
         x-transition.opacity
         role="listbox"
         class="absolute z-50 mt-1 left-0 right-0 min-w-[18rem] max-w-md bg-card border border-border rounded-lg shadow-lg overflow-hidden text-sm">
        @include('partials.search-autocomplete-results')
    </div>

    {{-- Teleported full-screen overlay: rendered as a direct child of <body>
         to escape any ancestor stacking context. --}}
    <template x-teleport="body">
        <div x-show="overlay"
             x-cloak
             x-transition.opacity
             role="dialog"
             aria-modal="true"
             aria-label="Search"
             class="fixed inset-0 z-[2000] bg-background flex flex-col">
            <div class="flex items-center gap-2 px-3 py-2 border-b border-border bg-card">
                <form role="search" action="/search" x-on:submit="onSubmit($event)" class="flex-1">
                    <div class="relative w-full">
                        <input type="text"
                            x-ref="overlayInput"
                            class="w-full pl-10 pr-3 py-2 bg-transparent border border-input rounded-lg text-base text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                            placeholder="Search..."
                            name="keyword"
                            autocomplete="off"
                            aria-label="Search"
                            aria-autocomplete="list"
                            role="combobox"
                            x-bind:aria-expanded="overlay"
                            x-model="query"
                            x-on:input.debounce.400ms="fetch()"
                            x-on:keydown.arrow-down.prevent="move(1)"
                            x-on:keydown.arrow-up.prevent="move(-1)"
                            x-on:keydown.enter="onEnter($event)">
                        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground"></i>
                    </div>
                </form>
                <button type="button"
                        x-on:click="closeOverlay()"
                        class="flex-shrink-0 p-2 rounded text-muted-foreground hover:text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                        aria-label="Close search">
                    <i class="bi bi-x-lg text-lg" aria-hidden="true"></i>
                </button>
            </div>
            <div role="listbox" class="flex-1 overflow-y-auto bg-card text-sm">
                @include('partials.search-autocomplete-results', ['inOverlay' => true])
            </div>
        </div>
    </template>
</div>

@once
<script>
    window.searchAutocomplete = function (config) {
        return {
            endpoint: config.endpoint,
            target: config.target,
            variant: config.variant || 'tw',
            query: '',
            open: false,
            overlay: false,
            loading: false,
            controller: null,
            results: { events: [], entities: [], series: [], tags: [] },
            activeKey: null,
            activeIdx: -1,
            _savedBodyOverflow: '',

            init() {
                // Prefill from the input value (Blade-rendered).
                this.query = this.$root.querySelector('input[name="keyword"]')?.value || '';
            },

            get groups() {
                return [
                    { key: 'events',   label: 'Events',   items: this.results.events   || [] },
                    { key: 'entities', label: 'Entities', items: this.results.entities || [] },
                    { key: 'series',   label: 'Series',   items: this.results.series   || [] },
                    { key: 'tags',     label: 'Tags',     items: this.results.tags     || [] },
                ];
            },

            get totalCount() {
                return this.groups.reduce((sum, g) => sum + g.items.length, 0);
            },

            get hasResults() {
                return this.totalCount > 0;
            },

            // Mobile-only: tapping into the input opens the full-screen overlay
            // for the smallest viewports. Desktop/tablet keeps the normal dropdown.
            onFocus() {
                if (this.variant === 'tw-mobile' && window.matchMedia('(max-width: 639px)').matches) {
                    this.openOverlay();
                } else {
                    this.open = this.hasResults;
                }
            },

            openOverlay() {
                if (this.overlay) return;
                this.overlay = true;
                this._savedBodyOverflow = document.body.style.overflow;
                document.body.style.overflow = 'hidden';
                this.$refs.input?.blur();
                // Defer focus until after the overlay DOM swap.
                this.$nextTick(() => this.$refs.overlayInput?.focus());
            },

            closeOverlay() {
                if (!this.overlay) return;
                this.overlay = false;
                document.body.style.overflow = this._savedBodyOverflow || '';
                this.open = false;
                this.$refs.overlayInput?.blur();
            },

            async fetch() {
                const q = this.query.trim();
                if (q.length < 3) {
                    this.results = { events: [], entities: [], series: [], tags: [] };
                    if (!this.overlay) this.open = false;
                    return;
                }
                if (this.controller) this.controller.abort();
                this.controller = new AbortController();
                this.loading = true;
                if (!this.overlay) this.open = true;
                try {
                    const res = await fetch(this.endpoint + '?q=' + encodeURIComponent(q) + '&limit=5', {
                        headers: { 'Accept': 'application/json' },
                        signal: this.controller.signal,
                        credentials: 'same-origin',
                    });
                    if (!res.ok) throw new Error('search request failed');
                    const data = await res.json();
                    this.results = Object.assign(
                        { events: [], entities: [], series: [], tags: [] },
                        data.results || {}
                    );
                    this.activeKey = null;
                    this.activeIdx = -1;
                } catch (err) {
                    if (err.name !== 'AbortError') console.warn('autocomplete error', err);
                } finally {
                    this.loading = false;
                }
            },

            close() { this.open = false; },

            isActive(key, idx) { return this.activeKey === key && this.activeIdx === idx; },
            setActive(key, idx) { this.activeKey = key; this.activeIdx = idx; },

            move(direction) {
                const flat = [];
                for (const g of this.groups) {
                    for (let i = 0; i < g.items.length; i++) flat.push([g.key, i]);
                }
                if (flat.length === 0) return;
                let current = flat.findIndex(([k, i]) => k === this.activeKey && i === this.activeIdx);
                current = current === -1 ? (direction > 0 ? -1 : flat.length) : current;
                let next = current + direction;
                if (next < 0) next = flat.length - 1;
                if (next >= flat.length) next = 0;
                [this.activeKey, this.activeIdx] = flat[next];
                this.open = true;
            },

            onEnter(event) {
                if (this.activeKey !== null && this.activeIdx >= 0) {
                    const item = (this.results[this.activeKey] || [])[this.activeIdx];
                    if (item && item.url) {
                        event.preventDefault();
                        window.location.href = item.url;
                    }
                }
                // Otherwise let the form submit normally to /search.
            },

            onSubmit(event) {
                if (!this.query.trim()) event.preventDefault();
            },

            highlight(text) {
                const q = this.query.trim();
                if (!q) return this.escape(text);
                const escaped = this.escape(text);
                const pattern = q.split(/\s+/).filter(Boolean).map(this.escapeRegex).join('|');
                if (!pattern) return escaped;
                return escaped.replace(new RegExp('(' + pattern + ')', 'gi'), '<mark class="bg-yellow-200 text-inherit">$1</mark>');
            },

            escape(s) {
                return String(s ?? '').replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
            },

            escapeRegex(s) { return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); },
        };
    };
</script>
@endonce
