@extends('layouts.app-tw')

@section('title', 'Event Add')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
@endsection

@section('content')

<div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold text-foreground mb-6">Add a New Event</h1>

    <form method="POST" action="{{ route('events.store') }}" class="space-y-6">
        @csrf

        @include('events.form')
    </form>

    <div class="mt-6">
        <x-ui.button variant="ghost" href="{{ route('events.index') }}">
            <i class="bi bi-arrow-left mr-2"></i>
            Return to list
        </x-ui.button>
    </div>
</div>

@stop

@section('scripts.footer')
<script src="{{ asset('/js/facebook-event.js') }}"></script>
@stop
