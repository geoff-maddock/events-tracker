@extends('layouts.app-tw')

@section('title', 'Event Feed')

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold text-foreground mb-6">Event Feed</h1>

    @if (count($events) > 0)
        <div class="space-y-6">
            @php $month = ''; @endphp
            @foreach ($events as $event)
                @if ($month != $event->start_at->format('F Y'))
                    @php $month = $event->start_at->format('F Y'); @endphp
                    <h2 class="text-xl font-semibold text-primary border-b border-border pb-2 mt-8 first:mt-0">
                        {{ $month }}
                    </h2>
                @endif

                <div class="card-tw p-4">
                    <div class="text-sm text-muted-foreground mb-1">
                        {{ $event->start_at->format('l, F jS Y') }}
                        @if ($event->start_at)
                            at {{ $event->start_at->format('g:i A') }}
                        @endif
                    </div>

                    <h3 class="text-lg font-semibold text-foreground">
                        <a href="{{ url('/events/' . $event->id) }}" class="hover:text-primary transition-colors">
                            {{ $event->name }}
                        </a>
                    </h3>

                    @if (!empty($event->series_id) && !empty($event->series))
                        <div class="text-sm text-muted-foreground">
                            <a href="/series/{{ $event->series_id }}" class="text-primary hover:underline">
                                {{ $event->series->name }}
                            </a> series
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-2 mt-2 text-sm">
                        @if ($event->eventType)
                            <span class="badge-tw badge-secondary-tw">{{ $event->eventType->name }}</span>
                        @endif

                        @if ($event->door_price)
                            <span class="badge-tw badge-primary-tw">${{ number_format($event->door_price, 0) }}</span>
                        @endif
                    </div>

                    @if ($event->venue)
                        <div class="text-sm text-muted-foreground mt-2">
                            <i class="bi bi-geo-alt mr-1"></i>
                            {{ $event->venue->name ?? 'No venue specified' }}
                            @if ($event->venue->getPrimaryLocationAddress())
                                - {{ $event->venue->getPrimaryLocationAddress() }}
                            @endif
                        </div>
                    @endif

                    @unless ($event->entities->isEmpty())
                        <div class="mt-2">
                            <span class="text-sm text-muted-foreground">Related:</span>
                            @foreach ($event->entities as $entity)
                                <a href="{{ route('entities.show', $entity->slug) }}" class="badge-tw badge-secondary-tw text-xs">
                                    {{ $entity->name }}
                                </a>
                            @endforeach
                        </div>
                    @endunless

                    @unless ($event->tags->isEmpty())
                        <div class="mt-2">
                            <span class="text-sm text-muted-foreground">Tags:</span>
                            @foreach ($event->tags as $tag)
                                <a href="{{ route('tags.show', $tag->slug) }}" class="badge-tw badge-primary-tw text-xs">
                                    {{ $tag->name }}
                                </a>
                            @endforeach
                        </div>
                    @endunless

                    @if ($event->primary_link)
                        <div class="mt-2 text-sm">
                            <a href="{{ $event->primary_link }}" target="_blank" class="text-primary hover:underline">
                                <i class="bi bi-link-45deg mr-1"></i>{{ $event->primary_link }}
                            </a>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="card-tw p-8 text-center text-muted-foreground">
            <i class="bi bi-calendar-x text-4xl mb-3"></i>
            <p>No events listed</p>
        </div>
    @endif
</div>
@stop
