@extends('layouts.app-tw')

@section('title', 'Feedback Responses')

@section('content')
{{-- Admin listing of collected survey feedback (issue #1998). Responses are
     private by default, so this whole page is behind can:admin. --}}
<div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-3xl font-bold text-primary mb-2">Feedback Responses</h1>
        <p class="text-muted-foreground">Solicited, private-by-default feedback collected through survey campaigns.</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('feedback.admin.summary') }}" class="px-3 py-2 text-sm rounded-md border border-border text-foreground hover:bg-accent transition-colors">
            <i class="bi bi-bar-chart-line"></i> Summary
        </a>
        <a href="{{ route('feedback.admin.export', request()->query()) }}" class="px-3 py-2 text-sm rounded-md border border-border text-foreground hover:bg-accent transition-colors">
            <i class="bi bi-download"></i> Export CSV
        </a>
    </div>
</div>

<div class="card-tw p-4 mb-6">
    <form method="GET" action="{{ route('feedback.admin.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
            <label for="campaign" class="block text-sm text-muted-foreground mb-1">Campaign</label>
            <select id="campaign" name="campaign" class="form-select-tw">
                <option value="">All campaigns</option>
                @foreach($campaigns as $campaign)
                    <option value="{{ $campaign->slug }}" {{ ($filters['campaign'] ?? '') === $campaign->slug ? 'selected' : '' }}>{{ $campaign->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="user" class="block text-sm text-muted-foreground mb-1">User</label>
            <input type="text" id="user" name="user" value="{{ $filters['user'] ?? '' }}" class="form-input-tw" placeholder="Name contains…">
        </div>
        <div>
            <label for="event" class="block text-sm text-muted-foreground mb-1">Event</label>
            <input type="text" id="event" name="event" value="{{ $filters['event'] ?? '' }}" class="form-input-tw" placeholder="Name or ID">
        </div>
        <div>
            <label for="rating" class="block text-sm text-muted-foreground mb-1">Minimum rating</label>
            <select id="rating" name="rating" class="form-select-tw">
                <option value="">Any</option>
                @foreach(range(1, 5) as $value)
                    <option value="{{ $value }}" {{ (string) ($filters['rating'] ?? '') === (string) $value ? 'selected' : '' }}>{{ $value }}+</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="visibility" class="block text-sm text-muted-foreground mb-1">Visibility</label>
            <select id="visibility" name="visibility" class="form-select-tw">
                <option value="">Any</option>
                @foreach($visibilities as $visibility)
                    <option value="{{ $visibility->id }}" {{ (string) ($filters['visibility'] ?? '') === (string) $visibility->id ? 'selected' : '' }}>{{ $visibility->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="display_status" class="block text-sm text-muted-foreground mb-1">Display status</label>
            <select id="display_status" name="display_status" class="form-select-tw">
                <option value="">Any</option>
                @foreach(\App\Models\SurveyResponse::DISPLAY_STATUSES as $status)
                    <option value="{{ $status }}" {{ ($filters['display_status'] ?? '') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="submitted_after" class="block text-sm text-muted-foreground mb-1">Submitted after</label>
            <input type="date" id="submitted_after" name="submitted_after" value="{{ $filters['submitted_after'] ?? '' }}" class="form-input-tw">
        </div>
        <div>
            <label for="submitted_before" class="block text-sm text-muted-foreground mb-1">Submitted before</label>
            <input type="date" id="submitted_before" name="submitted_before" value="{{ $filters['submitted_before'] ?? '' }}" class="form-input-tw">
        </div>
        <div class="flex items-end gap-2">
            <x-ui.button type="submit">Filter</x-ui.button>
            <a href="{{ route('feedback.admin.index') }}" class="px-4 py-2 text-sm text-muted-foreground hover:text-foreground transition-colors">Reset</a>
        </div>
    </form>
</div>

<div class="card-tw p-4">
    @if($responses->isEmpty())
        <p class="text-muted-foreground py-8 text-center">No feedback responses match these filters yet.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-muted-foreground border-b border-border">
                        <th class="py-2 pr-4">Submitted</th>
                        <th class="py-2 pr-4">Campaign</th>
                        <th class="py-2 pr-4">User</th>
                        <th class="py-2 pr-4">Subject</th>
                        <th class="py-2 pr-4">Rating</th>
                        <th class="py-2 pr-4">Visibility</th>
                        <th class="py-2 pr-4">Display</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($responses as $response)
                        @php
                            $ratingAnswer = $response->answers->firstWhere('value_int', '!=', null);
                        @endphp
                        <tr class="border-b border-border/50">
                            <td class="py-2 pr-4 whitespace-nowrap">{{ $response->submitted_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="py-2 pr-4">{{ $response->campaign?->name ?? '—' }}</td>
                            <td class="py-2 pr-4">
                                @if($response->user)
                                    <a href="{{ route('users.show', $response->user->id) }}" class="text-primary hover:underline">{{ $response->user->name }}</a>
                                @else
                                    <span class="text-muted-foreground">—</span>
                                @endif
                            </td>
                            <td class="py-2 pr-4">{{ $response->subject->name ?? '—' }}</td>
                            <td class="py-2 pr-4">
                                @if($ratingAnswer)
                                    <span class="text-yellow-400">{{ str_repeat('★', (int) $ratingAnswer->value_int) }}</span>
                                @else
                                    <span class="text-muted-foreground">—</span>
                                @endif
                            </td>
                            <td class="py-2 pr-4">
                                <x-ui.badge :variant="$response->isPublic() ? 'default' : 'secondary'">
                                    {{ $response->isPublic() ? 'Public' : 'Private' }}
                                </x-ui.badge>
                            </td>
                            <td class="py-2 pr-4">
                                <x-ui.badge :variant="$response->isApproved() ? 'default' : ($response->isRejected() ? 'destructive' : 'secondary')">
                                    {{ ucfirst($response->display_status) }}
                                </x-ui.badge>
                            </td>
                            <td class="py-2 text-right">
                                <a href="{{ route('feedback.admin.show', $response->id) }}" class="text-primary hover:underline">Moderate</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            <x-ui.pagination :paginator="$responses" />
        </div>
    @endif
</div>
@endsection
