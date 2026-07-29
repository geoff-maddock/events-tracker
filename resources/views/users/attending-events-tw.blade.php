{{-- Future events this user is attending: one non-wrapping row of small square
     thumbnails. Renders nothing unless the profile is public and the
     constrained relation was eager-loaded by the controller. --}}
@php
    $showAttending = $user->relationLoaded('attendingEvents')
        && $user->profile
        && $user->profile->setting_public_profile == 1
        && $user->attendingEvents->isNotEmpty();
@endphp
@if ($showAttending)
@php
    $attendingTotal = $user->attendingEvents->count();
    $attendingShown = $user->attendingEvents->take(4);
@endphp
<div class="flex flex-nowrap items-center justify-center gap-1.5 mb-3" data-attending-events>
    @foreach ($attendingShown as $attendingEvent)
    <a href="{{ route('events.show', ['event' => $attendingEvent->slug]) }}"
        title="{{ $attendingEvent->name }}"
        class="block w-12 h-12 shrink-0 rounded-md overflow-hidden border border-border hover:border-primary transition-colors">
        @if ($attendingPhoto = $attendingEvent->getPrimaryPhoto())
        <img src="{{ Storage::disk('external')->url($attendingPhoto->getStorageThumbnail()) }}"
            alt="{{ $attendingEvent->name }}"
            class="w-full h-full object-cover">
        @else
        <img src="/images/event-placeholder.png"
            alt="{{ $attendingEvent->name }}"
            class="w-full h-full object-cover opacity-50">
        @endif
    </a>
    @endforeach
    @if ($attendingTotal > 4)
    <span class="w-12 h-12 shrink-0 rounded-md bg-card border border-border flex items-center justify-center text-xs text-muted-foreground"
        title="{{ $attendingTotal - 4 }} more upcoming events">
        +{{ $attendingTotal - 4 }}
    </span>
    @endif
</div>
@endif
