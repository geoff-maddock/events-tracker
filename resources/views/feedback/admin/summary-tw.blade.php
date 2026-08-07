@extends('layouts.app-tw')

@section('title', 'Feedback Summary')

@section('content')
{{-- Per-campaign (optionally per-event) rollup of collected feedback (#1998). --}}
<div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-3xl font-bold text-primary mb-2">Feedback Summary</h1>
        <p class="text-muted-foreground">
            @if($event)
                {{ $campaign?->name }} for <span class="text-foreground">{{ $event->name }}</span>
            @else
                Aggregate results across all responses to this campaign.
            @endif
        </p>
    </div>
    <a href="{{ route('feedback.admin.index') }}" class="px-3 py-2 text-sm rounded-md border border-border text-foreground hover:bg-accent transition-colors">
        <i class="bi bi-list-ul"></i> All responses
    </a>
</div>

<div class="card-tw p-4 mb-6">
    <form method="GET" action="{{ route('feedback.admin.summary') }}" class="flex flex-wrap items-end gap-4">
        <div>
            <label for="campaign" class="block text-sm text-muted-foreground mb-1">Campaign</label>
            <select id="campaign" name="campaign" class="form-select-tw">
                @foreach($campaigns as $option)
                    <option value="{{ $option->slug }}" {{ $campaign && $campaign->slug === $option->slug ? 'selected' : '' }}>{{ $option->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="event" class="block text-sm text-muted-foreground mb-1">Event ID (optional)</label>
            <input type="text" id="event" name="event" value="{{ $event?->id }}" class="form-input-tw" placeholder="Leave blank for all">
        </div>
        <x-ui.button type="submit">Apply</x-ui.button>
    </form>
</div>

@if(!$campaign)
    <div class="card-tw p-4">
        <p class="text-muted-foreground">That campaign could not be found.</p>
    </div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="card-tw p-4">
            <p class="text-sm text-muted-foreground">Invitations</p>
            <p class="text-2xl font-bold text-foreground">{{ $counts['invitations'] }}</p>
        </div>
        <div class="card-tw p-4">
            <p class="text-sm text-muted-foreground">Responses</p>
            <p class="text-2xl font-bold text-foreground">{{ $counts['responses'] }}</p>
        </div>
        <div class="card-tw p-4">
            <p class="text-sm text-muted-foreground">Completion rate</p>
            <p class="text-2xl font-bold text-foreground">{{ $counts['completion'] }}%</p>
        </div>
    </div>

    <div class="card-tw p-4 mb-6">
        <h2 class="text-lg font-semibold text-foreground mb-4">By question</h2>

        @if(empty($stats))
            <p class="text-muted-foreground">No quantitative questions in this campaign.</p>
        @else
            <div class="space-y-6">
                @foreach($stats as $stat)
                    <div>
                        <div class="flex items-baseline justify-between gap-4 mb-2">
                            <h3 class="text-sm font-semibold text-foreground">{{ $stat['question']->prompt }}</h3>
                            <span class="text-xs text-muted-foreground whitespace-nowrap">{{ $stat['count'] }} answer(s)</span>
                        </div>

                        @if($stat['kind'] === 'rating')
                            <p class="text-2xl font-bold text-foreground">
                                {{ $stat['average'] }}
                                <span class="text-sm font-normal text-muted-foreground">
                                    / {{ $stat['question']->max_value ?? 5 }} average
                                </span>
                            </p>
                        @elseif(empty($stat['histogram']))
                            <p class="text-sm text-muted-foreground">No answers yet.</p>
                        @else
                            @php $max = max(array_column($stat['histogram'], 'total')); @endphp
                            <div class="space-y-1">
                                @foreach($stat['histogram'] as $bar)
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm text-muted-foreground w-56 shrink-0 truncate">{{ $bar['label'] }}</span>
                                        <div class="flex-1 bg-background rounded h-3 overflow-hidden">
                                            <div class="bg-primary h-full" style="width: {{ $max > 0 ? round(($bar['total'] / $max) * 100) : 0 }}%"></div>
                                        </div>
                                        <span class="text-sm text-foreground tabular-nums w-10 text-right">{{ $bar['total'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="card-tw p-4">
        <h2 class="text-lg font-semibold text-foreground mb-4">Recent comments</h2>

        @if($comments->isEmpty())
            <p class="text-muted-foreground">No free-text comments yet.</p>
        @else
            <div class="space-y-4">
                @foreach($comments as $comment)
                    <div class="pb-4 border-b border-border/50 last:border-0 last:pb-0">
                        <p class="text-sm text-foreground">{{ $comment->value_text }}</p>
                        <p class="text-xs text-muted-foreground mt-1">
                            {{ $comment->response?->user?->name ?? 'Unknown' }}
                            @if($comment->response?->subject)
                                &middot; {{ $comment->response->subject->name }}
                            @endif
                            @if($comment->response)
                                &middot; <a href="{{ route('feedback.admin.show', $comment->response->id) }}" class="text-primary hover:underline">View response</a>
                            @endif
                        </p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @if($event)
        {{-- Feedback is solicited and private; Event Reviews are unsolicited and
             public. Different features on purpose — cross-link rather than merge. --}}
        <div class="card-tw p-4 mt-6">
            <h2 class="text-lg font-semibold text-foreground mb-2">Public reviews</h2>
            <p class="text-sm text-muted-foreground">
                This page covers solicited, private-by-default feedback. Unsolicited public reviews for this event live separately —
                <a href="{{ route('reviews.index') }}" class="text-primary hover:underline">browse event reviews</a>.
            </p>
        </div>
    @endif
@endif
@endsection
