@extends('layouts.app-tw')

@section('title', 'Updates Resumed')

@section('content')
<div class="max-w-2xl mx-auto">
    <h1 class="text-3xl font-bold text-foreground mb-2">Your updates are back on</h1>

    <div class="card-tw p-6 space-y-4">
        <p class="text-foreground">You'll get your email updates again, starting with the next one.</p>
        <x-ui.button :href="$preferencesUrl" variant="outline">Choose which emails you get</x-ui.button>
    </div>
</div>
@endsection
