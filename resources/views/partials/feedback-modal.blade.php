{{--
    Proactive feedback interview prompt (issue #1998).

    Shown to a signed-in user who has an actionable survey invitation — today
    that means they marked "Attending" on an event that has since finished.

    Deliberately self-contained Alpine rather than <x-ui.modal>: that component
    emits an inline script with a hardcoded element id and stacks a fresh
    document-level keydown listener on every render.

    Two modes:
      - default: fixed overlay, fetches its payload from feedback.prompt
      - $standalone: renders inline on the standalone page with $payload
        already supplied, so there's no round-trip

    Optional variables:
      $standalone (bool)  render inline instead of as an overlay
      $payload    (array) pre-seeded payload; skips the fetch
--}}
@php
    $isStandalone = $standalone ?? false;
    $seededPayload = $payload ?? null;
@endphp
<style>[x-cloak]{display:none!important}</style>
<div x-data="feedbackPrompt({{ \Illuminate\Support\Js::from($seededPayload) }}, {{ $isStandalone ? 'true' : 'false' }})"
     x-init="init()" x-show="open" x-cloak
     class="{{ $isStandalone ? '' : 'fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4' }}"
     role="dialog" aria-modal="{{ $isStandalone ? 'false' : 'true' }}" aria-labelledby="feedback-title">
    <div class="bg-secondary rounded-lg shadow-2xl {{ $isStandalone ? 'w-full' : 'max-w-2xl w-full max-h-[90vh]' }} flex flex-col border border-border ring-1 ring-black/5"
         @click.stop>
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-border">
            <div class="min-w-0">
                <h2 id="feedback-title" class="text-lg font-semibold text-foreground" x-text="campaign.name || 'How was it?'"></h2>
                <template x-if="subject && subject.name">
                    <p class="text-sm text-muted-foreground truncate">
                        <span x-text="subject.name"></span>
                        <template x-if="subject.date">
                            <span> &middot; <span x-text="subject.date"></span></span>
                        </template>
                    </p>
                </template>
            </div>
            @unless($isStandalone)
                <button type="button" @click="dismiss()" title="Not now"
                        class="p-1 rounded-md hover:bg-background text-muted-foreground hover:text-foreground transition-colors">
                    <i class="bi bi-x-lg"></i>
                </button>
            @endunless
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto px-6 pb-6 pt-3 space-y-6">
            <p class="text-sm text-muted-foreground" x-show="campaign.intro" x-text="campaign.intro"></p>

            <template x-if="loading">
                <div class="text-center text-muted-foreground py-8">
                    <i class="bi bi-arrow-repeat animate-spin text-2xl"></i>
                </div>
            </template>

            <template x-if="!loading">
                <div class="space-y-6">
                    <template x-for="question in visibleQuestions" :key="question.key">
                        <section>
                            <h3 class="text-sm font-semibold text-foreground mb-2">
                                <span x-text="question.prompt"></span>
                                <span x-show="question.required" class="text-destructive" aria-hidden="true">*</span>
                            </h3>
                            <p x-show="question.help" class="text-xs text-muted-foreground mb-2" x-text="question.help"></p>

                            <!-- Rating -->
                            <template x-if="question.type === 'rating'">
                                <div class="flex items-center gap-1">
                                    <template x-for="n in ratingRange(question)" :key="'r-' + question.key + '-' + n">
                                        <button type="button" @click="answers[question.key] = n"
                                                :aria-label="n + ' out of ' + question.max"
                                                :aria-pressed="answers[question.key] === n"
                                                class="p-1 text-2xl leading-none transition-colors"
                                                :class="answers[question.key] >= n ? 'text-yellow-400' : 'text-muted-foreground hover:text-foreground'">
                                            <i class="bi" :class="answers[question.key] >= n ? 'bi-star-fill' : 'bi-star'"></i>
                                        </button>
                                    </template>
                                </div>
                            </template>

                            <!-- Single choice -->
                            <template x-if="question.type === 'single_choice'">
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="option in question.options" :key="question.key + '-' + option.value">
                                        <label class="cursor-pointer">
                                            <input type="radio" :name="question.key" :value="option.value"
                                                   x-model="answers[question.key]" class="sr-only peer">
                                            <span class="inline-block px-3 py-1 rounded-full text-sm border border-border text-muted-foreground peer-checked:bg-primary peer-checked:text-primary-foreground peer-checked:border-primary transition-colors"
                                                  x-text="option.label"></span>
                                        </label>
                                    </template>
                                </div>
                            </template>

                            <!-- Multi choice -->
                            <template x-if="question.type === 'multi_choice'">
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="option in question.options" :key="question.key + '-' + option.value">
                                        <label class="cursor-pointer">
                                            <input type="checkbox" :value="option.value"
                                                   x-model="answers[question.key]" class="sr-only peer">
                                            <span class="inline-block px-3 py-1 rounded-full text-sm border border-border text-muted-foreground peer-checked:bg-primary peer-checked:text-primary-foreground peer-checked:border-primary transition-colors"
                                                  x-text="option.label"></span>
                                        </label>
                                    </template>
                                </div>
                            </template>

                            <!-- Free text -->
                            <template x-if="question.type === 'text'">
                                <textarea x-model="answers[question.key]" rows="3"
                                          :maxlength="question.maxlength"
                                          class="form-input-tw w-full"
                                          placeholder="Optional"></textarea>
                            </template>

                            <p x-show="errors[question.key]" class="text-xs text-destructive mt-1" x-text="errors[question.key]"></p>
                        </section>
                    </template>

                    <label class="flex items-start gap-3 pt-2 border-t border-border cursor-pointer">
                        <input type="checkbox" x-model="isPublic" class="mt-1 rounded border-border">
                        <span class="text-sm text-muted-foreground" x-text="visibilityPrompt"></span>
                    </label>

                    <p x-show="generalError" class="text-sm text-destructive" x-text="generalError"></p>
                </div>
            </template>
        </div>

        <!-- Footer -->
        <div class="p-4 border-t border-border flex flex-wrap justify-between items-center gap-2">
            <div class="flex items-center gap-2">
                @unless($isStandalone)
                    <button type="button" @click="snooze()" :disabled="saving"
                            class="px-3 py-2 text-sm rounded-md text-muted-foreground hover:text-foreground hover:bg-background transition-colors">
                        Later
                    </button>
                @endunless
                <button type="button" @click="optOut()" :disabled="saving"
                        class="px-3 py-2 text-xs rounded-md text-muted-foreground hover:text-foreground hover:bg-background transition-colors">
                    Don't ask me again
                </button>
            </div>
            <button type="button" @click="submit()" :disabled="saving || !canSubmit"
                    class="px-4 py-2 text-sm rounded-md bg-primary text-primary-foreground hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!saving">Send feedback</span>
                <span x-show="saving">Sending&hellip;</span>
            </button>
        </div>
    </div>
</div>

<script>
    function feedbackPrompt(seeded, standalone) {
        return {
            open: false,
            loading: true,
            saving: false,
            standalone: standalone,
            token: null,
            campaign: {},
            subject: null,
            questions: [],
            answers: {},
            errors: {},
            generalError: '',
            isPublic: false,
            visibilityPrompt: 'Share my feedback publicly',

            csrf() {
                return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            },

            ratingRange(question) {
                const min = question.min || 1;
                const max = question.max || 5;
                return Array.from({ length: max - min + 1 }, (_, i) => min + i);
            },

            // Whether a question's branch is currently taken. Mirrors
            // SurveyQuestion::appliesGiven() on the server.
            applies(question) {
                if (!question.depends_on) return true;
                const actual = this.answers[question.depends_on.key];
                if (Array.isArray(actual)) return actual.includes(question.depends_on.value);
                return actual !== undefined && actual !== null && String(actual) === String(question.depends_on.value);
            },

            get visibleQuestions() {
                return this.questions.filter(q => this.applies(q));
            },

            // Only the required questions that are currently shown gate the
            // submit button; everything else is optional by design so the
            // survey stays quick to finish.
            get canSubmit() {
                return this.visibleQuestions
                    .filter(q => q.required)
                    .every(q => {
                        const value = this.answers[q.key];
                        return Array.isArray(value) ? value.length > 0 : (value !== undefined && value !== '');
                    });
            },

            apply(data) {
                if (!data || !data.invitation) {
                    this.open = false;
                    return;
                }
                this.token = data.invitation.token;
                this.campaign = data.campaign || {};
                this.subject = data.subject || null;
                this.questions = data.questions || [];
                this.visibilityPrompt = (data.visibility && data.visibility.prompt) || this.visibilityPrompt;
                this.isPublic = !!(data.visibility && data.visibility.default);

                // Multi-choice needs an array to x-model into; everything else
                // stays undefined until the user touches it.
                this.questions.forEach(q => {
                    if (q.type === 'multi_choice') this.answers[q.key] = [];
                });

                this.open = true;
            },

            init() {
                if (seeded) {
                    this.apply(seeded);
                    this.loading = false;
                    // The payload came from the layout composer, so the
                    // prompt endpoint never ran — record the view here.
                    if (this.token) {
                        this.post('/feedback/' + this.token + '/shown').catch(() => {});
                    }
                    return;
                }

                this.open = true;
                fetch('{{ route('feedback.prompt') }}', { headers: { 'Accept': 'application/json' } })
                    .then(r => r.ok ? r.json() : { invitation: null })
                    .then(data => this.apply(data))
                    .catch(() => { this.open = false; })
                    .finally(() => { this.loading = false; });
            },

            post(url, body) {
                return fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                    },
                    body: body ? JSON.stringify(body) : null,
                });
            },

            submit() {
                if (this.saving || !this.token) return;
                this.saving = true;
                this.errors = {};
                this.generalError = '';

                this.post('/feedback/' + this.token, { answers: this.answers, public: this.isPublic })
                    .then(r => {
                        if (r.ok) {
                            this.open = false;
                            if (this.standalone) window.location = '/';
                            return;
                        }
                        return r.json().then(body => {
                            // Laravel returns answers.<key> keys; strip the prefix
                            // so each message lands next to its question.
                            const errors = (body && body.errors) || {};
                            Object.keys(errors).forEach(field => {
                                this.errors[field.replace(/^answers\./, '')] = errors[field][0];
                            });
                            if (!Object.keys(errors).length) {
                                this.generalError = 'Something went wrong. Please try again.';
                            }
                        });
                    })
                    .catch(() => { this.generalError = 'Something went wrong. Please try again.'; })
                    .finally(() => { this.saving = false; });
            },

            snooze() {
                if (this.saving || !this.token) return;
                this.saving = true;
                this.post('/feedback/' + this.token + '/snooze')
                    .finally(() => { this.open = false; this.saving = false; });
            },

            dismiss() {
                if (this.saving || !this.token) { this.open = false; return; }
                this.saving = true;
                this.post('/feedback/' + this.token + '/dismiss')
                    .finally(() => { this.open = false; this.saving = false; });
            },

            optOut() {
                if (this.saving) return;
                this.saving = true;
                this.post('{{ route('feedback.optOut') }}')
                    .finally(() => { this.open = false; this.saving = false; });
            },
        };
    }
</script>
