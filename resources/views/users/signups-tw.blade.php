@extends('layouts.app-tw')

@section('title', 'Signup Review')

@section('content')
{{-- Admin review of recent signups (#2312). Risky signups stay Pending after
     they verify; approve the real ones, ban the spam. --}}
<div class="mb-6">
    <h1 class="text-3xl font-bold text-primary mb-2">Signup Review</h1>
    <p class="text-muted-foreground">
        Accounts from the last {{ $days }} days. A risk score of {{ $threshold }} or more keeps an account Pending after it verifies.
        Until an account is approved or established, only it and admins see its bio and social links.
    </p>
</div>

<div class="card-tw p-4 mb-6">
    <form method="GET" action="{{ route('users.signups') }}" class="flex flex-wrap items-end gap-4">
        <div>
            <label for="show" class="block text-sm font-medium text-foreground mb-1">Show</label>
            <select name="show" id="show" class="form-select-tw">
                <option value="all" @selected($show === 'all')>All signups</option>
                <option value="flagged" @selected($show === 'flagged')>Flagged</option>
                <option value="untrusted" @selected($show === 'untrusted')>Not yet trusted</option>
            </select>
        </div>
        <div>
            <label for="days" class="block text-sm font-medium text-foreground mb-1">Days</label>
            <input type="number" name="days" id="days" min="1" max="3650" value="{{ $days }}" class="form-input-tw w-28">
        </div>
        <x-ui.button type="submit" variant="outline">Apply</x-ui.button>
    </form>
</div>

<div class="card-tw p-4">
    @if ($signups->isEmpty())
        <p class="text-muted-foreground py-8 text-center">No signups match.</p>
    @else
        <form method="POST" action="{{ route('users.signups.update') }}" x-data>
            @csrf
            <div class="flex flex-wrap gap-2 mb-4">
                <x-ui.button type="submit" name="action" value="approve" variant="default" size="sm">
                    <i class="bi bi-check-circle mr-1"></i>Approve selected
                </x-ui.button>
                <x-ui.button type="submit" name="action" value="ban" variant="destructive" size="sm"
                    data-confirm="The selected accounts will be banned and their bio and social links cleared." data-confirm-button="Ban as spam">
                    <i class="bi bi-slash-circle mr-1"></i>Ban selected as spam
                </x-ui.button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-muted-foreground border-b border-border">
                            <th class="py-2 pr-3">
                                <input type="checkbox" aria-label="Select all"
                                    @change="$root.querySelectorAll('input[name=\'ids[]\']').forEach(c => c.checked = $event.target.checked)">
                            </th>
                            <th class="py-2 pr-4">User</th>
                            <th class="py-2 pr-4">Signed up</th>
                            <th class="py-2 pr-4">Status</th>
                            <th class="py-2 pr-4">Risk</th>
                            <th class="py-2 pr-4">Activity</th>
                            <th class="py-2 pr-4">Bio</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($signups as $signup)
                            @php
                                $risky = $signup->risk_score !== null && $signup->risk_score >= $threshold;
                                $verifiedAt = $signup->email_verified_at ? \Illuminate\Support\Carbon::parse($signup->email_verified_at) : null;
                            @endphp
                            <tr class="border-b border-border/50 align-top {{ $risky ? 'bg-destructive/5' : '' }}">
                                <td class="py-2 pr-3">
                                    <input type="checkbox" name="ids[]" value="{{ $signup->id }}" aria-label="Select {{ $signup->name }}">
                                </td>
                                <td class="py-2 pr-4">
                                    <a href="{{ route('users.show', $signup->id) }}" class="text-primary hover:underline font-medium">{{ $signup->name }}</a>
                                    <div class="text-xs text-muted-foreground break-all">{{ $signup->email }}</div>
                                </td>
                                <td class="py-2 pr-4 whitespace-nowrap">
                                    {{ $signup->created_at?->format('M j, Y g:ia') }}
                                    <div class="text-xs text-muted-foreground">
                                        @if ($verifiedAt)
                                            verified after {{ $signup->created_at ? $signup->created_at->diffForHumans($verifiedAt, true) : '—' }}
                                        @else
                                            not verified
                                        @endif
                                    </div>
                                </td>
                                <td class="py-2 pr-4 whitespace-nowrap">
                                    {{ $signup->status->name ?? '—' }}
                                    @if ($signup->trusted_at)
                                        <div><x-ui.badge variant="secondary">Approved</x-ui.badge></div>
                                    @endif
                                </td>
                                <td class="py-2 pr-4">
                                    @if ($signup->risk_score === null)
                                        <span class="text-muted-foreground">—</span>
                                    @else
                                        <x-ui.badge :variant="$risky ? 'destructive' : 'outline'">{{ $signup->risk_score }}</x-ui.badge>
                                        <ul class="mt-1 text-xs text-muted-foreground">
                                            @foreach ($signup->risk_reasons ?? [] as $reason)
                                                <li>{{ $labels[$reason] ?? $reason }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </td>
                                <td class="py-2 pr-4 text-xs text-muted-foreground whitespace-nowrap">
                                    {{ $signup->events_count }} events, {{ $signup->entities_count }} entities<br>
                                    {{ $signup->comments_count }} comments, {{ $signup->responses_count }} responses<br>
                                    {{ $signup->follows_count }} follows
                                </td>
                                <td class="py-2 pr-4 text-xs text-muted-foreground max-w-xs">
                                    {{ \Illuminate\Support\Str::limit((string) $signup->profile?->bio, 160) ?: '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </form>

        <div class="mt-4">
            {!! $signups->onEachSide(2)->links('vendor.pagination.tailwind') !!}
        </div>
    @endif
</div>
@endsection
