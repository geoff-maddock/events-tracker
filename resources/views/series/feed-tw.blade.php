@extends('layouts.app-tw')

@section('title', 'Series Feed')

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold text-foreground mb-6">Series Feed</h1>

    @if (count($series) > 0)
        <div class="space-y-6">
            @php $month = ''; @endphp
            @foreach ($series as $s)
                @if ($s->nextOccurrenceDate() && $month != $s->nextOccurrenceDate()->format('F Y'))
                    @php $month = $s->nextOccurrenceDate()->format('F Y'); @endphp
                    <h2 class="text-xl font-semibold text-primary border-b border-border pb-2 mt-8 first:mt-0">
                        {{ $month }}
                    </h2>
                @endif

                <div class="card-tw p-4">
                    <div class="text-sm text-muted-foreground mb-1">
                        @if ($s->nextOccurrenceDate())
                            {{ $s->nextOccurrenceDate()->format('l, F jS Y') }}
                            at {{ $s->nextOccurrenceDate()->format('g:i A') }}
                        @endif
                    </div>

                    <h3 class="text-lg font-semibold text-foreground">
                        <a href="{{ route('series.show', $s->slug) }}" class="hover:text-primary transition-colors">
                            {{ $s->name }}
                        </a>
                    </h3>

                    <div class="flex flex-wrap gap-2 mt-2 text-sm">
                        @if ($s->eventType)
                            <span class="badge-tw badge-secondary-tw">{{ $s->eventType->name }}</span>
                        @endif

                        @if ($s->door_price)
                            <span class="badge-tw badge-primary-tw">${{ number_format($s->door_price, 0) }}</span>
                        @endif

                        @if ($s->occurrenceType)
                            <span class="badge-tw badge-secondary-tw">{{ $s->occurrenceType->name }}</span>
                        @endif
                    </div>

                    @if ($s->venue)
                        <div class="text-sm text-muted-foreground mt-2">
                            <i class="bi bi-geo-alt mr-1"></i>
                            {{ $s->venue->name ?? 'No venue specified' }}
                            @if ($s->venue->getPrimaryLocationAddress())
                                - {{ $s->venue->getPrimaryLocationAddress() }}
                            @endif
                        </div>
                    @endif

                    @unless ($s->entities->isEmpty())
                        <div class="mt-2">
                            <span class="text-sm text-muted-foreground">Related:</span>
                            @foreach ($s->entities as $entity)
                                <a href="{{ route('entities.show', $entity->slug) }}" class="badge-tw badge-secondary-tw text-xs">
                                    {{ $entity->name }}
                                </a>
                            @endforeach
                        </div>
                    @endunless

                    @unless ($s->tags->isEmpty())
                        <div class="mt-2">
                            <span class="text-sm text-muted-foreground">Tags:</span>
                            @foreach ($s->tags as $tag)
                                <a href="{{ route('tags.show', $tag->slug) }}" class="badge-tw badge-primary-tw text-xs">
                                    {{ $tag->name }}
                                </a>
                            @endforeach
                        </div>
                    @endunless

                    @if ($s->primary_link)
                        <div class="mt-2 text-sm">
                            <a href="{{ $s->primary_link }}" target="_blank" class="text-primary hover:underline">
                                <i class="bi bi-link-45deg mr-1"></i>{{ $s->primary_link }}
                            </a>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="card-tw p-8 text-center text-muted-foreground">
            <i class="bi bi-calendar-x text-4xl mb-3"></i>
            <p>No future series listed</p>
        </div>
    @endif
</div>
@stop
