@extends('layouts.app-tw')

@section('title')Subscription Confirmed @endsection

@section('content')
<div class="max-w-xl mx-auto card-tw p-6 md:p-8 text-center">
    <i class="bi bi-check-circle text-primary text-4xl" aria-hidden="true"></i>
    <h1 class="text-2xl font-bold text-foreground mt-3 mb-2">You're subscribed!</h1>
    <p class="text-muted-foreground mb-6">
        The weekly Essential Events digest will be sent to <strong>{{ $subscriber->email }}</strong> every Monday morning.
    </p>
    <p class="text-sm text-muted-foreground mb-6">
        Want updates tailored to the artists, venues and genres you follow?
        <a href="{{ route('register', ['email' => $subscriber->email]) }}" class="text-primary hover:underline">Create a free account</a>.
    </p>
    <x-ui.button href="{{ route('events.index') }}">Browse Events</x-ui.button>
</div>
@stop
