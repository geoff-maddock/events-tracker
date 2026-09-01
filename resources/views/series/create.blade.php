@extends('layouts.app-tw')

@section('title', 'Series Add')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
@endsection

@section('content')

<div class="max-w-7xl mx-auto">
    <h1 class="text-3xl font-bold text-foreground mb-6">Add a New Event Series</h1>

    @include('partials.image-analyze-panel', [
        'context' => 'series',
        'title' => 'Series Image',
        'help' => 'Upload a primary image here and optionally use the "Analyze Image" button to pre-fill the form, including the recurring schedule. The image is attached when you save either way.',
        'formSelector' => '#series-create-form',
    ])

    <div class="bg-card rounded-lg border border-border shadow-sm p-6">
        <form method="POST" action="{{ route('series.store') }}" class="space-y-6" id="series-create-form">
            @csrf
            {{-- Carries the server-side temp token so the chosen image is attached
                 after save, whether or not it was run through analysis. --}}
            <input type="hidden" name="image_temp_token" id="image_temp_token" value="{{ old('image_temp_token') }}">

            @include('series.form')
        </form>
    </div>

    <div class="mt-6">
        <x-ui.button variant="ghost" href="{{ route('series.index') }}">
            <i class="bi bi-arrow-left mr-2"></i>
            Return to list
        </x-ui.button>
    </div>
</div>

@stop
@section('scripts.footer')
<script src="{{ asset('/js/image-analyze.js') }}?v={{ @filemtime(public_path('js/image-analyze.js')) }}"></script>
@stop
