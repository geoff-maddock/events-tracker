@extends('layouts.app-tw')

@section('title', 'Deleted Events')

@section('content')
{{-- Admin-only (can:admin): soft-deleted events, kept indefinitely, with restore (#2192). --}}
<div class="mb-6">
    <h1 class="text-3xl font-bold text-primary mb-2">Deleted Events</h1>
    <p class="text-muted-foreground">Deleted events are hidden everywhere but kept, with their RSVPs, reviews and links. Restoring one brings all of it back.</p>
</div>

@if ($events->isEmpty())
    <div class="card-tw p-6 text-muted-foreground">No deleted events.</div>
@else
    <div class="card-tw overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-muted-foreground border-b border-border">
                    <th class="p-3">Event</th>
                    <th class="p-3">Starts</th>
                    <th class="p-3">Venue</th>
                    <th class="p-3">Owner</th>
                    <th class="p-3">RSVPs</th>
                    <th class="p-3">Deleted</th>
                    <th class="p-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($events as $event)
                    @php
                        $deletion = $deletedBy->get($event->id);
                    @endphp
                    <tr class="border-b border-border">
                        <td class="p-3 text-foreground">{{ $event->name }}</td>
                        <td class="p-3">{{ $event->start_at?->format('M j, Y g:ia') }}</td>
                        <td class="p-3">{{ $event->venue?->name }}</td>
                        <td class="p-3">{{ $event->user?->name }}</td>
                        <td class="p-3">{{ $event->event_responses_count }}</td>
                        <td class="p-3">
                            {{ $event->deleted_at->diffForHumans() }}
                            @if ($deletion?->user)
                                <span class="text-muted-foreground">by {{ $deletion->user->name }}</span>
                            @endif
                        </td>
                        <td class="p-3 text-right">
                            <form method="POST" action="{{ route('events.restore', ['id' => $event->id]) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1 text-sm rounded-md border border-border text-foreground hover:bg-accent transition-colors">
                                    <i class="bi bi-arrow-counterclockwise"></i> Restore
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $events->links() }}</div>
@endif
@endsection
