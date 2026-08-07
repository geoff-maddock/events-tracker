@extends('layouts.app-tw')

@section('title', 'Share your feedback')

@section('content')
    {{--
        Standalone feedback page (issue #1998). Renders the same Alpine
        component as the in-app prompt, but inline and with the payload already
        supplied so there is no round-trip to feedback.prompt.

        This is also the target a future emailed feedback link will point at.
    --}}
    <div class="max-w-2xl mx-auto">
        @include('partials.feedback-modal', ['standalone' => true, 'payload' => $payload])
    </div>
@endsection
