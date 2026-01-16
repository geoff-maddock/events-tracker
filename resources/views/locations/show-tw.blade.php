@extends('layouts.app-tw')

@section('title', 'Location View')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <h1 class="text-3xl font-bold text-foreground">Location</h1>
        <div class="flex gap-2 mt-4 sm:mt-0">
            @can('edit_entity')
                <x-ui.button variant="default" href="{{ route('entities.locations.edit', ['entity' => $entity->slug, 'location' => $location->id]) }}">
                    <i class="bi bi-pencil mr-2"></i>Edit
                </x-ui.button>
            @endcan
            <x-ui.button variant="secondary" href="{{ route('entities.show', ['entity' => $entity->slug]) }}">
                <i class="bi bi-arrow-left mr-2"></i>Back to Entity
            </x-ui.button>
        </div>
    </div>

    <div class="card-tw p-6">
        <h2 class="text-2xl font-semibold text-foreground mb-4">{{ $location->name }}</h2>

        <div class="grid gap-4">
            @if ($location->attn)
                <div>
                    <label class="text-sm font-medium text-muted-foreground">Attention</label>
                    <p class="text-foreground mt-1">{{ $location->attn }}</p>
                </div>
            @endif

            @if ($location->address_one || $location->address_two)
                <div>
                    <label class="text-sm font-medium text-muted-foreground">Address</label>
                    <p class="text-foreground mt-1">
                        {{ $location->address_one }}
                        @if ($location->address_two)
                            <br>{{ $location->address_two }}
                        @endif
                    </p>
                </div>
            @endif

            @if ($location->neighborhood)
                <div>
                    <label class="text-sm font-medium text-muted-foreground">Neighborhood</label>
                    <p class="text-foreground mt-1">{{ $location->neighborhood }}</p>
                </div>
            @endif

            @if ($location->city || $location->state || $location->postcode)
                <div>
                    <label class="text-sm font-medium text-muted-foreground">City / State / Postal Code</label>
                    <p class="text-foreground mt-1">
                        {{ $location->city }}@if ($location->state), {{ $location->state }}@endif
                        @if ($location->postcode) {{ $location->postcode }}@endif
                    </p>
                </div>
            @endif

            @if ($location->country)
                <div>
                    <label class="text-sm font-medium text-muted-foreground">Country</label>
                    <p class="text-foreground mt-1">{{ $location->country }}</p>
                </div>
            @endif

            @if ($location->capacity)
                <div>
                    <label class="text-sm font-medium text-muted-foreground">Capacity</label>
                    <p class="text-foreground mt-1">{{ $location->capacity }}</p>
                </div>
            @endif

            @if ($location->latitude && $location->longitude)
                <div>
                    <label class="text-sm font-medium text-muted-foreground">Coordinates</label>
                    <p class="text-foreground mt-1">{{ $location->latitude }}, {{ $location->longitude }}</p>
                </div>
            @endif

            @if ($location->map_url)
                <div>
                    <label class="text-sm font-medium text-muted-foreground">Map</label>
                    <p class="text-foreground mt-1">
                        <a href="{{ $location->map_url }}" target="_blank" class="text-primary hover:underline">
                            View on Map <i class="bi bi-box-arrow-up-right ml-1 text-xs"></i>
                        </a>
                    </p>
                </div>
            @endif

            @if ($location->locationType)
                <div>
                    <label class="text-sm font-medium text-muted-foreground">Location Type</label>
                    <p class="text-foreground mt-1">{{ $location->locationType->name }}</p>
                </div>
            @endif

            <div>
                <label class="text-sm font-medium text-muted-foreground">Entity</label>
                <p class="mt-1">
                    <a href="{{ route('entities.show', ['entity' => $entity->slug]) }}" class="text-primary hover:underline">
                        {{ $entity->name }}
                    </a>
                </p>
            </div>
        </div>

        @can('edit_entity')
            <div class="mt-6 pt-6 border-t border-border">
                <form action="{{ route('entities.locations.destroy', ['entity' => $entity->slug, 'location' => $location->id]) }}" method="POST"
                    onsubmit="return confirm('Are you sure you want to delete this location?')">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="destructive">
                        <i class="bi bi-trash mr-2"></i>Delete Location
                    </x-ui.button>
                </form>
            </div>
        @endcan
    </div>
</div>
@stop
