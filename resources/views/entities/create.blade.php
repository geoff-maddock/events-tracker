@extends('layouts.app-tw')

@section('title', 'Entity Add')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
@endsection

@section('content')

<div class="max-w-7xl mx-auto">
    <h1 class="text-3xl font-bold text-foreground mb-6">Add a New Entity</h1>

    <div class="bg-card rounded-lg border border-border shadow-sm p-6">
        <form method="POST" action="{{ route('entities.store') }}" class="space-y-6">
            @csrf

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
