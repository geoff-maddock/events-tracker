@extends('layouts.app-tw')

@section('title', 'Event Add')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
@endsection

@section('content')

<div class="max-w-7xl mx-auto">
    <h1 class="text-3xl font-bold text-foreground mb-6">Add a New Event</h1>

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
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all flatpickr date/time pickers
    const dateTimePickers = document.querySelectorAll('[data-flatpickr]');

    dateTimePickers.forEach(function(picker) {
        const enableTime = picker.getAttribute('data-enable-time') === 'true';
        const dateFormat = picker.getAttribute('data-date-format') || 'Y-m-d H:i';
        const altFormat = picker.getAttribute('data-alt-format') || 'F j, Y at h:i K';
        const minDate = picker.getAttribute('data-min-date');
        const maxDate = picker.getAttribute('data-max-date');

        flatpickr(picker, {
            enableTime: enableTime,
            dateFormat: dateFormat,
            altInput: true,
            altFormat: altFormat,
            time_24hr: false,
            minDate: minDate,
            maxDate: maxDate,
        });
    });
});
</script>
@stop