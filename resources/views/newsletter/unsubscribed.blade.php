@extends('layouts.app-tw')

@section('title')Unsubscribed @endsection

@section('content')
<div class="max-w-xl mx-auto card-tw p-6 md:p-8 text-center">
    <i class="bi bi-envelope-slash text-muted-foreground text-4xl" aria-hidden="true"></i>
    <h1 class="text-2xl font-bold text-foreground mt-3 mb-2">You've been unsubscribed</h1>
    <p class="text-muted-foreground mb-6">
        <strong>{{ $subscriber->email }}</strong> will no longer receive the Essential Events digest.
        Changed your mind? Just subscribe again from the homepage.
    </p>
    <x-ui.button href="{{ route('home') }}">Back to {{ config('app.app_name') }}</x-ui.button>
</div>
@stop
