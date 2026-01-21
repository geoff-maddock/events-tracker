@extends('layouts.app-tw')

@section('title', 'Upcoming Events')

@section('content')

    <!-- Hero Section -->
    <div class="card-tw p-6 mb-8 relative overflow-hidden">
        <!-- Background Pattern/Gradient -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>

        <div class="relative z-10">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-2xl font-bold text-foreground">
                    {{ config('app.tagline')}}
                </h3>
                <button id="event-close-box"
                    class="text-muted-foreground hover:text-foreground transition-colors"
                    onclick="document.getElementById('jumbo-container').classList.toggle('hidden')">
                    <i class="bi bi-three-dots"></i>
                </button>
            </div>

            <div id="jumbo-container" class="transition-all duration-300">
                <p class="text-muted-foreground mb-6 max-w-3xl text-lg">
                    {{ config('app.app_name') }} is a calendar and guide to events, weekly and monthly series, promoters, artists, producers, djs, venues and other entities.
                </p>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('events.index') }}" class="px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors">
                        <i class="bi bi-calendar3 mr-2"></i>Show all events
                    </a>
                    <a href="{{ route('series.index') }}" class="px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors">
                        <i class="bi bi-collection mr-2"></i>Show event series
                    </a>

                    <div class="w-full sm:w-auto border-l border-border pl-3 ml-1 flex flex-wrap gap-3">
                        <a href="{{ route('events.create') }}" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                            <i class="bi bi-plus-lg mr-2"></i>Add Event
                        </a>
                        <a href="{{ route('series.create') }}" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                            <i class="bi bi-plus-lg mr-2"></i>Add Series
                        </a>
                        <a href="{{ route('entities.create') }}" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                            <i class="bi bi-plus-lg mr-2"></i>Add Entity
                        </a>
                    </div>

                    @if (Auth::guest())
                        <a href="{{ route('register') }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-500 transition-colors ml-auto">
                            <i class="bi bi-person-plus mr-2"></i>Register account
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 4 Days Grid -->
    <section id="4days">
        @include('events.4days-tw')
    </section>
@stop

@section('footer')
<script type="text/javascript">
// init app module on document load
$(function()
{
    Home.init();
});
</script>
@stop
