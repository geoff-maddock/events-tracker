@extends('layouts.app-tw')

@section('title', 'Unsubscribed')

@section('content')
<div class="max-w-2xl mx-auto">
    <h1 class="text-3xl font-bold text-foreground mb-2">You're unsubscribed</h1>

    <div class="card-tw p-6 space-y-4">
        <p class="text-foreground">You won't get the <strong>{{ $listLabel }}</strong> email any more.</p>
        <p class="text-muted-foreground">Unsubscribed by mistake, or want to stop other emails too?</p>
        <x-ui.button :href="$preferencesUrl" variant="outline">Manage email preferences</x-ui.button>
    </div>
</div>
@endsection
