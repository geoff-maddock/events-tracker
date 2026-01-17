@extends('layouts.app-tw')

@section('title', 'Photo - '. $photo->name)

@section('og-description')
@include('photos.slug-text', ['photo' => $photo])
@stop

@section('og-image')
{{ Storage::disk('external')->url($photo->getStoragePath()) }}
@endsection

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <h1 class="text-3xl font-bold text-foreground">Photo</h1>
        <div class="flex gap-2 mt-4 sm:mt-0">
            <x-ui.button variant="secondary" href="{{ route('photos.index') }}">
                <i class="bi bi-images mr-2"></i>Photo Index
            </x-ui.button>
            @if ($event = $photo->events->first())
                <x-ui.button variant="secondary" href="{{ route('events.show', ['event' => $event->id]) }}">
                    <i class="bi bi-calendar-event mr-2"></i>View Event
                </x-ui.button>
            @endif
        </div>
    </div>

    <div class="card-tw overflow-hidden">
        <div class="p-6">
            @if ($photo->name)
                <h2 class="text-2xl font-semibold text-foreground mb-4">{{ $photo->name }}</h2>
            @endif

            <div class="rounded-lg overflow-hidden bg-muted">
                <img src="{{ Storage::disk('external')->url($photo->getStoragePath()) }}"
                     alt="{{ $photo->name ?? 'Photo' }}"
                     class="w-full h-auto object-contain max-h-[80vh]">
            </div>

            @if ($photo->caption)
                <p class="mt-4 text-muted-foreground">{{ $photo->caption }}</p>
            @endif

            <div class="grid gap-4 mt-6">
                @unless ($photo->events->isEmpty())
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Related Events</label>
                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach ($photo->events as $event)
                                <a href="{{ route('events.show', ['event' => $event->id]) }}" class="badge-tw badge-primary-tw">
                                    {{ $event->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endunless

                @unless ($photo->entities->isEmpty())
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Related Entities</label>
                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach ($photo->entities as $entity)
                                <x-entity-badge :entity="$entity" context="photos" variant="secondary" />
                            @endforeach
                        </div>
                    </div>
                @endunless

                @if ($photo->created_at)
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Uploaded</label>
                        <p class="text-foreground mt-1">{{ $photo->created_at->format('F j, Y g:i A') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@stop
