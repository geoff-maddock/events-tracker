@extends('layouts.app-tw')

@section('title', 'Popular')

@section('content')
<div class="max-w-[2400px] mx-auto space-y-8">
    <!-- Header -->
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-foreground mb-2">Popular</h1>
        <p class="text-muted-foreground">
            Discover the most popular events, entities, and tags on {{ config('app.app_name') }}
        </p>

        <!-- Quick Links Navigation -->
        <div class="mt-6 flex flex-wrap justify-center gap-2">
            <a href="#popular-events" class="inline-flex items-center gap-2 px-4 py-2 bg-card border border-border rounded-lg text-sm hover:bg-accent transition-colors">
                <i class="bi bi-calendar-event text-primary"></i>
                <span>Popular Events</span>
            </a>
            <a href="#popular-entities" class="inline-flex items-center gap-2 px-4 py-2 bg-card border border-border rounded-lg text-sm hover:bg-accent transition-colors">
                <i class="bi bi-people text-green-500"></i>
                <span>Popular Entities</span>
            </a>
            <a href="#popular-tags" class="inline-flex items-center gap-2 px-4 py-2 bg-card border border-border rounded-lg text-sm hover:bg-accent transition-colors">
                <i class="bi bi-tags text-yellow-500"></i>
                <span>Popular Tags</span>
            </a>
        </div>
    </div>

    <!-- Popular Events -->
    <section id="popular-events" class="card-tw p-6">
        <h2 class="text-2xl font-semibold text-foreground mb-2 flex items-center gap-2">
            <i class="bi bi-calendar-event text-primary"></i>
            Popular Events
        </h2>
        <p class="text-sm text-muted-foreground mb-4">
            Upcoming events with the most interest
        </p>
        @if ($popularEvents->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach ($popularEvents as $event)
                    @include('events.card-tw', ['event' => $event])
                @endforeach
            </div>
            <div class="mt-6 text-center">
                <x-ui.button variant="secondary" href="{{ route('events.index') }}">
                    View All Events
                </x-ui.button>
            </div>
        @else
            <div class="text-center py-8">
                <i class="bi bi-calendar-x text-4xl text-muted-foreground/50 mb-3 block"></i>
                <p class="text-muted-foreground">No popular events found at this time.</p>
            </div>
        @endif
    </section>

    <!-- Popular Entities -->
    <section id="popular-entities" class="card-tw p-6">
        <h2 class="text-2xl font-semibold text-foreground mb-2 flex items-center gap-2">
            <i class="bi bi-people text-green-500"></i>
            Popular Entities
        </h2>
        <p class="text-sm text-muted-foreground mb-4">
            Artists, venues, and promoters with the most followers
        </p>
        @if ($popularEntities->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach ($popularEntities as $entity)
                    @include('entities.card-tw', ['entity' => $entity])
                @endforeach
            </div>
            <div class="mt-6 text-center">
                <x-ui.button variant="secondary" href="{{ route('entities.index') }}">
                    View All Entities
                </x-ui.button>
            </div>
        @else
            <div class="text-center py-8">
                <i class="bi bi-people text-4xl text-muted-foreground/50 mb-3 block"></i>
                <p class="text-muted-foreground">No popular entities found at this time.</p>
            </div>
        @endif
    </section>

    <!-- Popular Tags -->
    <section id="popular-tags" class="card-tw p-6">
        <h2 class="text-2xl font-semibold text-foreground mb-2 flex items-center gap-2">
            <i class="bi bi-tags text-yellow-500"></i>
            Popular Tags
        </h2>
        <p class="text-sm text-muted-foreground mb-4">
            Most frequently used tags across events and entities
        </p>
        @if ($popularTags->isNotEmpty())
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4">
                @foreach ($popularTags as $tag)
                    @php
                        $event = $tag->events()->visible($user ?? null)->latest('start_at')->first();
                        $photo = $event ? $event->getPrimaryPhoto() : null;
                    @endphp
                    <a href="/tags/{{ $tag->slug }}" class="group block">
                        <div class="relative aspect-square bg-card border border-border rounded-lg overflow-hidden hover:border-primary transition-colors">
                            @if($photo)
                                <img src="{{ Storage::disk('external')->url($photo->getStorageThumbnail()) }}"
                                     alt="{{ $tag->name }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-muted">
                                    <i class="bi bi-tag text-4xl text-muted-foreground/30"></i>
                                </div>
                            @endif
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent">
                                <div class="absolute bottom-0 left-0 right-0 p-2">
                                    <span class="text-white text-sm font-medium block truncate">{{ $tag->name }}</span>
                                    <span class="text-white/70 text-xs">{{ $tag->events_count ?? $tag->events()->count() }} events</span>
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-6 text-center">
                <x-ui.button variant="secondary" href="{{ route('tags.index') }}">
                    View All Tags
                </x-ui.button>
            </div>
        @else
            <div class="text-center py-8">
                <i class="bi bi-tags text-4xl text-muted-foreground/50 mb-3 block"></i>
                <p class="text-muted-foreground">No popular tags found at this time.</p>
            </div>
        @endif
    </section>
</div>
@stop
