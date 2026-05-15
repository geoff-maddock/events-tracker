{{--
    Search autocomplete (Alpine.js).

    Variants:
      - $variant = 'tw'        (Tailwind/sidebar) -- default
      - $variant = 'tw-mobile' (Tailwind/topbar)
      - $variant = 'bs'        (legacy Bootstrap nav)

    Optional:
      - $inputId  -- DOM id for the input
      - $value    -- prefilled keyword
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

<div class="relative search-autocomplete-root"
     x-data="searchAutocomplete({ endpoint: '{{ url('/api/search') }}', target: '/search' })"
     x-on:keydown.escape.window="close()"
     x-on:click.outside="close()">
    <form role="search" action="/search" x-on:submit="onSubmit($event)" class="{{ $variant === 'bs' ? 'navbar-form navbar-left' : 'flex flex-1 min-w-0' }}">
        @if($variant === 'bs')
            <div class="form-group">
                <input type="text"
                    id="{{ $inputId }}"
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
                    x-on:input.debounce.200ms="fetch()"
                    x-on:focus="open = hasResults"
                    x-on:keydown.arrow-down.prevent="move(1)"
                    x-on:keydown.arrow-up.prevent="move(-1)"
                    x-on:keydown.enter="onEnter($event)"
                    value="{{ $value }}">
            </div>
        @else
            <div class="relative w-full">
                <input type="text"
                    id="{{ $inputId }}"
                    class="{{ $inputClass }}"
                    placeholder="{{ $variant === 'tw-mobile' ? 'Search...' : 'Search' }}"
                    name="keyword"
                    autocomplete="off"
                    title="Search"
                    aria-label="Search"
                    aria-autocomplete="list"
                    role="combobox"
                    x-bind:aria-expanded="open"
                    x-model="query"
                    x-on:input.debounce.200ms="fetch()"
                    x-on:focus="open = hasResults"
                    x-on:keydown.arrow-down.prevent="move(1)"
                    x-on:keydown.arrow-up.prevent="move(-1)"
                    x-on:keydown.enter="onEnter($event)"
                    value="{{ $value }}">
                <i class="bi bi-search absolute {{ $variant === 'tw-mobile' ? 'left-2.5 text-sm' : 'left-3' }} top-1/2 -translate-y-1/2 text-muted-foreground"></i>
            </div>
        @endif
    </form>

    <div x-show="open"
         x-cloak
         x-transition.opacity
         class="absolute z-50 mt-1 left-0 right-0 min-w-[18rem] max-w-md bg-card border border-border rounded-lg shadow-lg overflow-hidden text-sm"
         role="listbox">
        <template x-if="loading">
            <div class="px-3 py-2 text-muted-foreground">Searching&hellip;</div>
        </template>
        <template x-if="!loading && totalCount === 0 && query.length > 0">
            <div class="px-3 py-2 text-muted-foreground">No matches for &ldquo;<span x-text="query"></span>&rdquo;.</div>
        </template>

        <template x-for="group in groups" :key="group.key">
            <div x-show="group.items.length > 0">
                <div class="px-3 py-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground bg-muted/50" x-text="group.label"></div>
                <ul>
                    <template x-for="(item, idx) in group.items" :key="group.key + '-' + item.id">
                        <li>
                            <a :href="item.url"
                               class="block px-3 py-2 hover:bg-muted focus:bg-muted"
                               :class="{ 'bg-muted': isActive(group.key, idx) }"
                               x-on:mouseenter="setActive(group.key, idx)"
                               role="option"
                               :aria-selected="isActive(group.key, idx)">
                                <div class="font-medium text-foreground" x-html="highlight(item.name)"></div>
                                <template x-if="item.subtitle">
                                    <div class="text-xs text-muted-foreground truncate" x-text="item.subtitle"></div>
                                </template>
                            </a>
                        </li>
                    </template>
                </ul>
            </div>
        </template>

        <template x-if="query.length > 0 && totalCount > 0">
            <a x-bind:href="'/search?keyword=' + encodeURIComponent(query)"
               class="block px-3 py-2 border-t border-border text-center text-xs font-medium text-foreground hover:bg-muted">
                View all results for &ldquo;<span x-text="query"></span>&rdquo;
            </a>
        </template>
    </div>
</div>

@once
<script>
    window.searchAutocomplete = function (config) {
        return {
            endpoint: config.endpoint,
            target: config.target,
            query: '',
            open: false,
            loading: false,
            controller: null,
            results: { events: [], entities: [], series: [], tags: [] },
            activeKey: null,
            activeIdx: -1,

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

            async fetch() {
                const q = this.query.trim();
                if (q.length < 2) {
                    this.results = { events: [], entities: [], series: [], tags: [] };
                    this.open = false;
                    return;
                }
                if (this.controller) this.controller.abort();
                this.controller = new AbortController();
                this.loading = true;
                this.open = true;
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
