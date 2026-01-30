@extends('app')

@section('title','Event Repo - Club Guide')

@section('content')

    <!-- Hero Section -->
    <div class="bg-dark-surface rounded-xl border border-dark-border p-6 mb-8 shadow-lg relative overflow-hidden">
        <!-- Background Pattern/Gradient -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>
        
        <div class="relative z-10">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-2xl font-bold text-white">
                    {{ config('app.tagline')}}
                </h3>
                <button id="event-close-box" 
                    class="text-gray-400 hover:text-white transition-colors" 
                    onclick="document.getElementById('jumbo-container').classList.toggle('hidden')">
                    <i class="bi bi-three-dots"></i>
                </button>
            </div>

            <div id="jumbo-container" class="transition-all duration-300">
                <p class="text-gray-300 mb-6 max-w-3xl text-lg">
                    Arcane City is a calendar and guide to events, weekly and monthly series, promoters, artists, producers, djs, venues and other entities.
                </p>
                
                <div class="flex flex-wrap gap-3">
                    <a href="{!! URL::route('events.index') !!}" class="px-4 py-2 bg-dark-card border border-dark-border text-white rounded-lg hover:bg-dark-border hover:text-primary transition-colors">
                        <i class="bi bi-calendar3 mr-2"></i>Show all events
                    </a>
                    <a href="{!! URL::route('events.future') !!}" class="px-4 py-2 bg-dark-card border border-dark-border text-white rounded-lg hover:bg-dark-border hover:text-primary transition-colors">
                        <i class="bi bi-calendar-check mr-2"></i>Show future events
                    </a>
                    <a href="{!! URL::route('series.index') !!}" class="px-4 py-2 bg-dark-card border border-dark-border text-white rounded-lg hover:bg-dark-border hover:text-primary transition-colors">
                        <i class="bi bi-collection mr-2"></i>Show event series
                    </a>
                    
                    @auth
                    <div class="w-full sm:w-auto border-l border-dark-border pl-3 ml-1 flex flex-wrap gap-3">
                        <a href="{!! URL::route('events.create') !!}" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-hover transition-colors shadow-lg shadow-primary/20">
                            <i class="bi bi-plus-lg mr-2"></i>Add Event
                        </a>
                        <a href="{!! URL::route('series.create') !!}" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-hover transition-colors shadow-lg shadow-primary/20">
                            <i class="bi bi-plus-lg mr-2"></i>Add Series
                        </a>
                        <a href="{!! URL::route('entities.create') !!}" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-hover transition-colors shadow-lg shadow-primary/20">
                            <i class="bi bi-plus-lg mr-2"></i>Add Entity
                        </a>
                    </div>
                    @endauth

                    @if (Auth::guest())
                        <a href="{!! URL::route('register') !!}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-500 transition-colors ml-auto">
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