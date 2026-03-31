@extends('layouts.app-tw')

@section('title', 'Event Add')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
@endsection

@section('content')

<div class="max-w-7xl mx-auto">
    <h1 class="text-3xl font-bold text-foreground mb-6">Add a New Event</h1>

    {{-- Flyer Import Section --}}
    <div class="bg-card rounded-lg border border-border shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-foreground flex items-center gap-2">
                <i class="bi bi-image-fill text-primary"></i>
                Import from Flyer
            </h2>
            <button type="button" id="flyer-toggle-btn"
                class="text-sm text-muted-foreground hover:text-foreground inline-flex items-center gap-1">
                <i class="bi bi-chevron-down" id="flyer-toggle-icon"></i>
                <span id="flyer-toggle-label">Show</span>
            </button>
        </div>

        <div id="flyer-import-panel" class="hidden">
            <p class="text-sm text-muted-foreground mb-4">
                Upload an event flyer and our AI assistant will extract the event details and pre-fill the form below.
                You can review and edit the information before saving.
            </p>

            <div id="flyer-drop-zone"
                class="border-2 border-dashed border-border rounded-lg p-8 text-center cursor-pointer hover:border-primary/50 transition-colors mb-4"
                ondragover="event.preventDefault();"
                ondrop="handleFlyerDrop(event)">
                <input type="file" id="flyer-file-input" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden">
                <div id="flyer-drop-content">
                    <i class="bi bi-cloud-upload text-4xl text-muted-foreground mb-2 block"></i>
                    <p class="text-muted-foreground text-sm">Drag & drop a flyer image here, or</p>
                    <button type="button" onclick="document.getElementById('flyer-file-input').click()"
                        class="mt-2 inline-flex items-center px-3 py-1.5 bg-primary text-primary-foreground text-sm rounded-md hover:bg-primary/90 transition-colors">
                        <i class="bi bi-folder2-open mr-1.5"></i>
                        Browse File
                    </button>
                    <p class="text-xs text-muted-foreground mt-2">Supports JPEG, PNG, GIF, WebP — max 10 MB</p>
                </div>
                <div id="flyer-preview-content" class="hidden">
                    <img id="flyer-preview-img" src="" alt="Flyer preview" class="max-h-48 mx-auto rounded mb-2">
                    <p id="flyer-preview-name" class="text-sm text-muted-foreground"></p>
                    <button type="button" onclick="clearFlyerSelection()"
                        class="mt-2 text-sm text-muted-foreground hover:text-foreground underline">
                        Choose a different image
                    </button>
                </div>
            </div>

            <div id="flyer-status" class="hidden text-sm mb-4 p-3 rounded-md"></div>

            <div class="flex items-center gap-3">
                <button type="button" id="analyze-flyer-btn" onclick="analyzeFlyer()"
                    class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    disabled>
                    <i class="bi bi-stars mr-2"></i>
                    <span id="analyze-btn-text">Analyze with AI</span>
                </button>
                <span id="analyze-spinner" class="hidden text-sm text-muted-foreground">
                    <i class="bi bi-arrow-clockwise animate-spin mr-1"></i>
                    Analyzing flyer&hellip;
                </span>
            </div>
        </div>
    </div>

    <div class="bg-card rounded-lg border border-border shadow-sm p-6">
        <form method="POST" action="{{ route('events.store') }}" class="space-y-6">
            @csrf

            @include('events.form')
        </form>
    </div>

    <div class="mt-6">
        <x-ui.button variant="ghost" href="{{ route('events.index') }}">
            <i class="bi bi-arrow-left mr-2"></i>
            Return to list
        </x-ui.button>
    </div>
</div>

@stop
@section('scripts.footer')

<script>
// ── Flyer import panel toggle ──────────────────────────────────────────────
(function () {
    const btn   = document.getElementById('flyer-toggle-btn');
    const panel = document.getElementById('flyer-import-panel');
    const icon  = document.getElementById('flyer-toggle-icon');
    const label = document.getElementById('flyer-toggle-label');
    if (btn) {
        btn.addEventListener('click', function () {
            const open = !panel.classList.contains('hidden');
            panel.classList.toggle('hidden', open);
            icon.className  = open ? 'bi bi-chevron-down' : 'bi bi-chevron-up';
            label.textContent = open ? 'Show' : 'Hide';
        });
    }
})();

// ── File selection ─────────────────────────────────────────────────────────
let selectedFlyerFile = null;

document.getElementById('flyer-file-input')?.addEventListener('change', function () {
    if (this.files && this.files[0]) {
        setFlyerFile(this.files[0]);
    }
});

function handleFlyerDrop(e) {
    e.preventDefault();
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        setFlyerFile(file);
    }
}

function setFlyerFile(file) {
    selectedFlyerFile = file;
    const reader = new FileReader();
    reader.onload = function (e) {
        const img = document.getElementById('flyer-preview-img');
        if (img) img.src = e.target.result;
    };
    reader.readAsDataURL(file);
    document.getElementById('flyer-preview-name').textContent = file.name;
    document.getElementById('flyer-drop-content').classList.add('hidden');
    document.getElementById('flyer-preview-content').classList.remove('hidden');
    document.getElementById('analyze-flyer-btn').disabled = false;
    setFlyerStatus('', '');
}

function clearFlyerSelection() {
    selectedFlyerFile = null;
    document.getElementById('flyer-file-input').value = '';
    document.getElementById('flyer-drop-content').classList.remove('hidden');
    document.getElementById('flyer-preview-content').classList.add('hidden');
    document.getElementById('analyze-flyer-btn').disabled = true;
    setFlyerStatus('', '');
}

function setFlyerStatus(message, type) {
    const el = document.getElementById('flyer-status');
    if (!message) { el.classList.add('hidden'); return; }
    el.textContent = message;
    el.className = 'text-sm mb-4 p-3 rounded-md '
        + (type === 'error'
            ? 'bg-red-50 text-red-700 border border-red-200 dark:bg-red-950/30 dark:text-red-400 dark:border-red-800'
            : 'bg-green-50 text-green-700 border border-green-200 dark:bg-green-950/30 dark:text-green-400 dark:border-green-800');
    el.classList.remove('hidden');
}

// ── Analyse flyer ──────────────────────────────────────────────────────────
async function analyzeFlyer() {
    if (!selectedFlyerFile) return;

    const btn     = document.getElementById('analyze-flyer-btn');
    const spinner = document.getElementById('analyze-spinner');
    const btnText = document.getElementById('analyze-btn-text');

    btn.disabled    = true;
    btnText.textContent = 'Analyzing…';
    spinner.classList.remove('hidden');
    setFlyerStatus('', '');

    try {
        const formData = new FormData();
        formData.append('image', selectedFlyerFile);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content
            || document.querySelector('input[name="_token"]')?.value || '');

        const response = await fetch('{{ route("events.analyzeFlyer") }}', {
            method: 'POST',
            body: formData,
        });

        const json = await response.json();

        if (!response.ok || !json.success) {
            setFlyerStatus(json.message || 'Failed to analyse flyer. Please try again.', 'error');
            return;
        }

        populateFormFromFlyer(json.data);
        setFlyerStatus('✓ Form pre-filled from flyer. Please review and adjust before saving.', 'success');

    } catch (err) {
        console.error('Flyer analysis error:', err);
        setFlyerStatus('An unexpected error occurred. Please try again.', 'error');
    } finally {
        btn.disabled    = false;
        btnText.textContent = 'Analyze with AI';
        spinner.classList.add('hidden');
    }
}

// ── Populate form from extracted data ─────────────────────────────────────
function populateFormFromFlyer(data) {
    setField('name', data.name);
    setField('slug', data.slug);
    setField('short', data.short);
    setField('description', data.description);
    setField('start_at', data.start_at);
    setField('end_at', data.end_at);
    setField('door_at', data.door_at);
    setField('presale_price', data.presale_price);
    setField('door_price', data.door_price);
    setField('primary_link', data.primary_link);
    setField('ticket_link', data.ticket_link);

    // min_age
    if (data.min_age !== undefined && data.min_age !== null) {
        const minAgeEl = document.getElementById('min_age');
        if (minAgeEl) {
            const age = parseInt(data.min_age, 10);
            const val = (age === 18 || age === 21) ? String(age) : '0';
            minAgeEl.value = val;
        }
    }

    // venue – try to match by name in the select options
    matchSelectByName('venue_id', data.venue_name);

    // promoter – try to match by name in the select options
    matchSelectByName('promoter_id', data.promoter_name);

    // Tags – add extracted tags as new options in the Select2 multi-select
    if (Array.isArray(data.tags) && data.tags.length) {
        addSelect2Options('tag_list', data.tags);
    }

    // Performers – add to entity list as new options
    if (Array.isArray(data.performers) && data.performers.length) {
        addSelect2Options('entity_list', data.performers);
    }

    // Scroll to the form
    document.querySelector('.bg-card form')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function setField(id, value) {
    if (value === null || value === undefined || value === '') return;
    const el = document.getElementById(id);
    if (el) el.value = value;
}

function matchSelectByName(selectId, name) {
    if (!name) return;
    const el = document.getElementById(selectId);
    if (!el) return;
    const nameLower = name.toLowerCase();
    // Try exact match first, then starts-with match
    for (const option of el.options) {
        if (option.text.toLowerCase() === nameLower) {
            el.value = option.value;
            if (window.jQuery && jQuery('#' + selectId).data('select2')) {
                jQuery('#' + selectId).trigger('change');
            }
            return;
        }
    }
    for (const option of el.options) {
        if (option.text.toLowerCase().startsWith(nameLower)) {
            el.value = option.value;
            if (window.jQuery && jQuery('#' + selectId).data('select2')) {
                jQuery('#' + selectId).trigger('change');
            }
            return;
        }
    }
}

function addSelect2Options(selectId, names) {
    if (!window.jQuery) return;
    const $el = jQuery('#' + selectId);
    if (!$el.length) return;

    names.forEach(function (name) {
        if (!name) return;
        // Check if an option with this text already exists (case-insensitive)
        const lower = name.toLowerCase();
        let found = false;
        $el.find('option').each(function () {
            if (jQuery(this).text().toLowerCase() === lower) {
                jQuery(this).prop('selected', true);
                found = true;
            }
        });
        if (!found) {
            // Add new option and select it (Select2 allows tag-like addition for display)
            const newOption = new Option(name, name, true, true);
            $el.append(newOption);
        }
    });
    $el.trigger('change');
}

// ── Existing: collision detection ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    const startAt = document.getElementById('start_at');
    if (startAt) {
        startAt.addEventListener('change', function() {
            if (!this.value) return;
            const d = new Date(this.value);
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            checkEventsOnDate(year, month, day, null);
        });
    }
});

function checkEventsOnDate(year, month, day, excludeSlug) {
    const warning = document.getElementById('events-on-date-warning');
    const list = document.getElementById('events-on-date-list');
    if (!warning || !list) return;

    fetch(`/api/events/by-date/${year}/${month}/${day}`)
        .then(r => r.json())
        .then(data => {
            const events = (data.data || []).filter(e => e.slug !== excludeSlug);
            if (events.length === 0) {
                warning.classList.add('hidden');
                return;
            }
            list.innerHTML = events.map(e => {
                const venue = e.venue ? ` @ ${e.venue.name}` : '';
                const time = e.start_at
                    ? new Date(e.start_at).toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'})
                    : '';
                return `<li><a href="/events/${e.slug}" target="_blank" class="underline hover:no-underline">${e.name}</a>${venue}${time ? ' &mdash; ' + time : ''}</li>`;
            }).join('');
            warning.classList.remove('hidden');
        })
        .catch(() => warning.classList.add('hidden'));
}
</script>
@stop