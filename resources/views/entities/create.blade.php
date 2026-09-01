@extends('layouts.app-tw')

@section('title', 'Entity Add')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
@endsection

@section('content')

<div class="max-w-7xl mx-auto">
    <h1 class="text-3xl font-bold text-foreground mb-6">Add a New Entity</h1>

    @include('partials.image-analyze-panel', [
        'context' => 'entity',
        'title' => 'Entity Image',
        'help' => 'Upload a primary image here — a logo, band photo or venue shot. Optionally use the "Analyze Image" button to identify the subject and pre-fill the form. The image is attached when you save either way.',
        'formSelector' => '#entity-create-form',
    ])

    <div class="bg-card rounded-lg border border-border shadow-sm p-6">
        <form method="POST" action="{{ route('entities.store') }}" class="space-y-6" id="entity-create-form">
            @csrf
            {{-- Carries the server-side temp token so the chosen image is attached
                 after save, whether or not it was run through analysis. --}}
            <input type="hidden" name="image_temp_token" id="image_temp_token" value="{{ old('image_temp_token') }}">

            @include('entities.form')
        </form>
    </div>

    <div class="mt-6">
        <x-ui.button variant="ghost" href="{{ route('entities.index') }}">
            <i class="bi bi-arrow-left mr-2"></i>
            Return to list
        </x-ui.button>
    </div>
</div>

@stop
@section('scripts.footer')
<script src="{{ asset('/js/image-analyze.js') }}?v={{ @filemtime(public_path('js/image-analyze.js')) }}"></script>
@stop
