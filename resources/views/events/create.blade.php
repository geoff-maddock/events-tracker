@extends('layouts.app-tw')

@section('title', 'Event Add')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
@endsection

@section('content')

<div class="max-w-7xl mx-auto">
    <h1 class="text-3xl font-bold text-foreground mb-6">Add a New Event</h1>

    @include('partials.image-analyze-panel', [
        'context' => 'event',
        'title' => 'Event Image',
        'help' => 'Upload a primary event image here and optionally use the "Analyze Image" button to automatically pre-fill the event form with details extracted from the flyer.',
        'formSelector' => '#event-create-form',
    ])

    <div class="bg-card rounded-lg border border-border shadow-sm p-6">
        <form method="POST" action="{{ route('events.store') }}" class="space-y-6" id="event-create-form">
            @csrf
            {{-- Carries the server-side temp token so the chosen image is attached
                 after save, whether or not it was run through analysis. --}}
            <input type="hidden" name="image_temp_token" id="image_temp_token" value="{{ old('image_temp_token') }}">

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

<script src="{{ asset('/js/image-analyze.js') }}?v={{ @filemtime(public_path('js/image-analyze.js')) }}"></script>

<script>
// ── Existing: collision detection ─────────────────────────────────
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