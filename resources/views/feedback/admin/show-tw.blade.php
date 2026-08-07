@extends('layouts.app-tw')

@section('title', 'Feedback Response')

@section('content')
{{-- A single collected feedback response with every answer (issue #1998). --}}
<div class="mb-6">
    <a href="{{ route('feedback.admin.index') }}" class="text-sm text-muted-foreground hover:text-foreground transition-colors">
        <i class="bi bi-arrow-left"></i> Back to responses
    </a>
    <h1 class="text-3xl font-bold text-primary mt-2 mb-2">{{ $response->campaign?->name ?? 'Feedback' }}</h1>
    <p class="text-muted-foreground">
        Submitted {{ $response->submitted_at?->format('M j, Y g:ia') ?? 'at an unknown time' }}
        @if($response->user)
            by <a href="{{ route('users.show', $response->user->id) }}" class="text-primary hover:underline">{{ $response->user->name }}</a>
        @endif
    </p>
</div>

<div class="card-tw p-4 mb-6">
    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
        <div>
            <dt class="text-muted-foreground">Subject</dt>
            <dd class="text-foreground">
                @if($response->subject && $response->subject_type === 'event')
                    <a href="{{ route('events.show', $response->subject->slug ?: $response->subject->id) }}" class="text-primary hover:underline">
                        {{ $response->subject->name }}
                    </a>
                @elseif($response->subject)
                    {{ $response->subject->name ?? '—' }}
                @else
                    <span class="text-muted-foreground">None</span>
                @endif
            </dd>
        </div>
        <div>
            <dt class="text-muted-foreground">Visibility</dt>
            <dd>
                <x-ui.badge :variant="$response->isPublic() ? 'default' : 'secondary'">
                    {{ $response->isPublic() ? 'Public — shared by the user' : 'Private — admin only' }}
                </x-ui.badge>
            </dd>
        </div>
        <div>
            <dt class="text-muted-foreground">Invitation</dt>
            <dd class="text-foreground">
                @if($response->invitation)
                    {{ ucfirst($response->invitation->status) }}
                    <span class="text-muted-foreground">({{ $response->invitation->source }})</span>
                @else
                    <span class="text-muted-foreground">Unsolicited</span>
                @endif
            </dd>
        </div>
    </dl>
</div>

<div class="card-tw p-4">
    <h2 class="text-lg font-semibold text-foreground mb-4">Answers</h2>

    @if($response->answers->isEmpty())
        <p class="text-muted-foreground">No answers were recorded.</p>
    @else
        <div class="space-y-4">
            {{-- Multi-choice stores one row per selected option, so group by
                 question to render them as a single answer. --}}
            @foreach($response->answers->groupBy('survey_question_id') as $answers)
                @php $question = $answers->first()->question; @endphp
                <div class="pb-4 border-b border-border/50 last:border-0 last:pb-0">
                    <p class="text-sm font-semibold text-foreground">{{ $question?->prompt ?? 'Unknown question' }}</p>
                    <p class="text-sm text-muted-foreground mt-1">
                        {{ $answers->map(fn($answer) => $answer->displayValue())->filter()->implode(', ') ?: '—' }}
                    </p>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
