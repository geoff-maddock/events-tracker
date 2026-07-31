{{--
    Post-signup "Getting To Know You" onboarding prompt (issue #901).

    Shown to verified users who follow nothing yet. Lets them pick popular
    entities, tags, and events to follow. Each section pages client-side
    (up to 10 pages of options from the data endpoint) and selections are
    kept across pages. Selecting "Follow selected" creates the follows and
    marks onboarding complete; "Skip for now" permanently dismisses it.
    Either way it never auto-shows again.
--}}
<style>[x-cloak]{display:none!important}</style>
<div x-data="onboarding()" x-init="init()" x-show="open" x-cloak
     class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
     role="dialog" aria-modal="true" aria-labelledby="onboarding-title">
    <div class="bg-secondary rounded-lg shadow-2xl max-w-2xl w-full max-h-[90vh] flex flex-col border border-border ring-1 ring-black/5"
         @click.stop>
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-border">
            <h2 id="onboarding-title" class="text-lg font-semibold text-foreground">Get to know your scene</h2>
            <button type="button" @click="dismiss()" title="Skip for now"
                    class="p-1 rounded-md hover:bg-background text-muted-foreground hover:text-foreground transition-colors">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto px-6 pb-6 pt-3 space-y-6">
            <p class="text-sm text-muted-foreground">
                Follow a few artists, venues, genres, or events below and we'll tailor your radar and recommendations to what you care about. Or skip for now and you can always browse the site to discover and follow more later.
            </p>

            <template x-if="loading">
                <div class="text-center text-muted-foreground py-8">
                    <i class="bi bi-arrow-repeat animate-spin text-2xl"></i>
                </div>
            </template>

            <template x-if="!loading">
                <div class="space-y-6">
                    <!-- Entities -->
                    <section x-show="entities.length">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-semibold text-foreground">Popular artists &amp; venues</h3>
                            <div class="flex items-center gap-1" x-show="pageCount('entities') > 1">
                                <button type="button" @click="prevPage('entities')" :disabled="page.entities === 0"
                                        aria-label="Previous artists and venues"
                                        class="p-1 rounded-md border border-border text-muted-foreground hover:text-foreground hover:bg-background disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                                    <i class="bi bi-chevron-left text-xs"></i>
                                </button>
                                <span class="text-xs text-muted-foreground tabular-nums"
                                      x-text="(page.entities + 1) + ' / ' + pageCount('entities')"></span>
                                <button type="button" @click="nextPage('entities')" :disabled="page.entities >= pageCount('entities') - 1"
                                        aria-label="More artists and venues"
                                        class="p-1 rounded-md border border-border text-muted-foreground hover:text-foreground hover:bg-background disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                                    <i class="bi bi-chevron-right text-xs"></i>
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <template x-for="item in paged('entities')" :key="'entity-' + item.id">
                                <label class="flex items-center gap-3 p-2 rounded-md border border-border hover:bg-background cursor-pointer">
                                    <input type="checkbox" :value="item.id" x-model.number="selected.entities" class="rounded border-border">
                                    <img x-show="item.image" :src="item.image" alt="" class="w-8 h-8 rounded object-cover">
                                    <span class="min-w-0">
                                        <span class="block text-sm text-foreground truncate" x-text="item.name"></span>
                                        <span x-show="item.subtitle" class="block text-xs text-muted-foreground truncate" x-text="item.subtitle"></span>
                                    </span>
                                </label>
                            </template>
                        </div>
                    </section>

                    <!-- Tags -->
                    <section x-show="tags.length">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-semibold text-foreground">Popular genres &amp; tags</h3>
                            <div class="flex items-center gap-1" x-show="pageCount('tags') > 1">
                                <button type="button" @click="prevPage('tags')" :disabled="page.tags === 0"
                                        aria-label="Previous genres and tags"
                                        class="p-1 rounded-md border border-border text-muted-foreground hover:text-foreground hover:bg-background disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                                    <i class="bi bi-chevron-left text-xs"></i>
                                </button>
                                <span class="text-xs text-muted-foreground tabular-nums"
                                      x-text="(page.tags + 1) + ' / ' + pageCount('tags')"></span>
                                <button type="button" @click="nextPage('tags')" :disabled="page.tags >= pageCount('tags') - 1"
                                        aria-label="More genres and tags"
                                        class="p-1 rounded-md border border-border text-muted-foreground hover:text-foreground hover:bg-background disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                                    <i class="bi bi-chevron-right text-xs"></i>
                                </button>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="item in paged('tags')" :key="'tag-' + item.id">
                                <label class="cursor-pointer">
                                    <input type="checkbox" :value="item.id" x-model.number="selected.tags" class="sr-only peer">
                                    <span class="inline-block px-3 py-1 rounded-full text-sm border border-border text-muted-foreground peer-checked:bg-primary peer-checked:text-primary-foreground peer-checked:border-primary transition-colors"
                                          x-text="item.name"></span>
                                </label>
                            </template>
                        </div>
                    </section>

                    <!-- Events -->
                    <section x-show="events.length">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-semibold text-foreground">Upcoming events</h3>
                            <div class="flex items-center gap-1" x-show="pageCount('events') > 1">
                                <button type="button" @click="prevPage('events')" :disabled="page.events === 0"
                                        aria-label="Previous events"
                                        class="p-1 rounded-md border border-border text-muted-foreground hover:text-foreground hover:bg-background disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                                    <i class="bi bi-chevron-left text-xs"></i>
                                </button>
                                <span class="text-xs text-muted-foreground tabular-nums"
                                      x-text="(page.events + 1) + ' / ' + pageCount('events')"></span>
                                <button type="button" @click="nextPage('events')" :disabled="page.events >= pageCount('events') - 1"
                                        aria-label="More events"
                                        class="p-1 rounded-md border border-border text-muted-foreground hover:text-foreground hover:bg-background disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                                    <i class="bi bi-chevron-right text-xs"></i>
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <template x-for="item in paged('events')" :key="'event-' + item.id">
                                <label class="flex items-center gap-3 p-2 rounded-md border border-border hover:bg-background cursor-pointer">
                                    <input type="checkbox" :value="item.id" x-model.number="selected.events" class="rounded border-border">
                                    <span class="min-w-0">
                                        <span class="block text-sm text-foreground truncate" x-text="item.name"></span>
                                        <span x-show="item.subtitle" class="block text-xs text-muted-foreground truncate" x-text="item.subtitle"></span>
                                    </span>
                                </label>
                            </template>
                        </div>
                    </section>

                    <p x-show="!entities.length && !tags.length && !events.length" class="text-sm text-muted-foreground">
                        Nothing to suggest right now — you can explore and follow things any time.
                    </p>
                </div>
            </template>
        </div>

        <!-- Footer -->
        <div class="p-4 border-t border-border flex justify-between items-center gap-2">
            <button type="button" @click="dismiss()" :disabled="saving"
                    class="px-4 py-2 text-sm rounded-md text-muted-foreground hover:text-foreground hover:bg-background transition-colors">
                Skip for now
            </button>
            <button type="button" @click="save()" :disabled="saving || totalSelected === 0"
                    class="px-4 py-2 text-sm rounded-md bg-primary text-primary-foreground hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!saving" x-text="totalSelected ? 'Follow ' + totalSelected + ' selected' : 'Follow selected'"></span>
                <span x-show="saving">Saving…</span>
            </button>
        </div>
    </div>
</div>

<script>
    function onboarding() {
        return {
            open: false,
            loading: true,
            saving: false,
            entities: [],
            tags: [],
            events: [],
            selected: { entities: [], tags: [], events: [] },
            // Client-side paging per section; selections persist across pages
            // because checkbox state lives in `selected`, not in the DOM.
            page: { entities: 0, tags: 0, events: 0 },
            perPage: { entities: 8, tags: 12, events: 6 },
            paged(kind) {
                const start = this.page[kind] * this.perPage[kind];
                return this[kind].slice(start, start + this.perPage[kind]);
            },
            pageCount(kind) {
                return Math.max(1, Math.ceil(this[kind].length / this.perPage[kind]));
            },
            prevPage(kind) {
                if (this.page[kind] > 0) this.page[kind]--;
            },
            nextPage(kind) {
                if (this.page[kind] < this.pageCount(kind) - 1) this.page[kind]++;
            },
            get totalSelected() {
                return this.selected.entities.length + this.selected.tags.length + this.selected.events.length;
            },
            csrf() {
                return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            },
            init() {
                this.open = true;
                fetch('{{ route('onboarding.data') }}', { headers: { 'Accept': 'application/json' } })
                    .then(r => r.ok ? r.json() : { entities: [], tags: [], events: [] })
                    .then(data => {
                        this.entities = data.entities || [];
                        this.tags = data.tags || [];
                        this.events = data.events || [];
                    })
                    .catch(() => {})
                    .finally(() => { this.loading = false; });
            },
            save() {
                if (this.saving) return;
                this.saving = true;
                fetch('{{ route('onboarding.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                    },
                    body: JSON.stringify(this.selected),
                })
                    .then(() => { this.open = false; })
                    .catch(() => { this.saving = false; });
            },
            dismiss() {
                if (this.saving) return;
                this.saving = true;
                fetch('{{ route('onboarding.dismiss') }}', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                })
                    .finally(() => { this.open = false; this.saving = false; });
            },
        };
    }
</script>
