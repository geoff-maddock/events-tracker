@extends('layouts.app-tw')

@section('title', 'Unsubscribed')

@section('content')
<div class="max-w-2xl mx-auto">
    <h1 class="text-3xl font-bold text-foreground mb-2">You're unsubscribed</h1>

    <div class="card-tw p-6 space-y-4">
        <p class="text-foreground">We won't send any more reminders or summaries about your listing on {{ config('app.app_name') }} to this address.</p>
        <p class="text-muted-foreground">If this was a mistake, reply to any of our emails and we'll put it back.</p>
    </div>
</div>
@endsection
