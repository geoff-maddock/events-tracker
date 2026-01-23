@extends('layouts.app-tw')

@section('title', 'Your Radar')

@section('content')
<div class="max-w-[2400px] mx-auto space-y-8">
    <!-- Header -->
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-foreground mb-2">Your Radar</h1>
        <p class="text-muted-foreground">
            Stay up to date with events and activities that matter to you
        </p>

        <!-- Quick Links Navigation -->
        <div class="mt-6 flex flex-wrap justify-center gap-2">
            @if ($attendingEvents->isNotEmpty())
                <a href="#attending-events" class="inline-flex items-center gap-2 px-4 py-2 bg-card border border-border rounded-lg text-sm hover:bg-accent transition-colors">
                    <i class="bi bi-calendar-check text-primary"></i>
                    <span>Your Events</span>
                </a>
            @endif

            @if ($hasFollowedContent)
                <a href="#recommended-events" class="inline-flex items-center gap-2 px-4 py-2 bg-card border border-border rounded-lg text-sm hover:bg-accent transition-colors">
                    <i class="bi bi-tags text-green-500"></i>
                    <span>Recommended</span>
                </a>
            @endif

            <a href="#recent-events" class="inline-flex items-center gap-2 px-4 py-2 bg-card border border-border rounded-lg text-sm hover:bg-accent transition-colors">
                <i class="bi bi-clock-history text-muted-foreground"></i>
                <span>Recent Events</span>
            </a>

            <a href="{{ route('events.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-card border border-border rounded-lg text-sm hover:bg-accent transition-colors">
                <i class="bi bi-calendar-event text-yellow-500"></i>
                <span>All Events</span>
            </a>
        </div>
    </div>

    <!-- Events You're Attending -->
    @if ($attendingEvents->isNotEmpty())
        <section id="attending-events" class="card-tw p-6">
            <h2 class="text-2xl font-semibold text-foreground mb-4 flex items-center gap-2">
                <i class="bi bi-calendar-check text-primary"></i>
                Events You're Attending
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach ($attendingEvents as $event)
                    @include('events.card-tw', ['event' => $event])
                @endforeach
            </div>
        </section>
    @endif

    <!-- Recommended Events -->
    @if ($hasFollowedContent)
        <section id="recommended-events" class="card-tw p-6">
            <h2 class="text-2xl font-semibold text-foreground mb-2 flex items-center gap-2">
                <i class="bi bi-tags text-green-500"></i>
                Recommended for You
            </h2>
            <p class="text-sm text-muted-foreground mb-4">
                Based on entities, tags, and series you follow
            </p>
            @if ($recommendedEvents->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach ($recommendedEvents as $event)
                        @include('events.card-tw', ['event' => $event])
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <p class="text-muted-foreground mb-2">No recommendations found at this time.</p>
                    <p class="text-sm text-muted-foreground">
                        Check back later as new events are added!
                    </p>
                </div>
            @endif
        </section>
    @endif

    <!-- Get Started Section (when no activity) -->
    @if ($attendingEvents->isEmpty() && !$hasFollowedContent)
        <section id="get-started" class="card-tw p-8 bg-gradient-to-br from-primary/5 to-accent/10 text-center">
            <h2 class="text-2xl font-semibold text-foreground mb-4">Get Started with Your Radar</h2>
            <p class="text-muted-foreground mb-6">
                Personalize your experience by attending events and following your favorite entities and tags
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <x-ui.button variant="default" href="{{ route('events.index') }}">
                    <i class="bi bi-calendar-event mr-2"></i>
                    Browse Events
                </x-ui.button>
                <x-ui.button variant="secondary" href="{{ route('entities.index') }}">
                    <i class="bi bi-people mr-2"></i>
                    Follow Entities
                </x-ui.button>
                <x-ui.button variant="secondary" href="{{ route('tags.index') }}">
                    <i class="bi bi-tags mr-2"></i>
                    Follow Tags
                </x-ui.button>
            </div>
        </section>
    @endif

    <!-- Recently Added Events -->
    <section id="recent-events" class="card-tw p-6">
        <h2 class="text-2xl font-semibold text-foreground mb-2 flex items-center gap-2">
            <i class="bi bi-clock-history text-muted-foreground"></i>
            Recently Added Events
        </h2>
        <p class="text-sm text-muted-foreground mb-4">
            Discover the latest events added to {{ config('app.app_name') }}
        </p>
        @if ($recentEvents->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach ($recentEvents as $event)
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
                <p class="text-muted-foreground">No recent events found.</p>
            </div>
        @endif
    </section>
</div>
@stop
