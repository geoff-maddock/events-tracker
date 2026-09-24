@foreach ($entity->locations as $location)
@if (isset($location->visibility) && ($location->visibility->name != 'Guarded' || ($location->visibility->name == 'Guarded' && $signedIn)))
       @if ($location->safeMapUrl())
        <a href="{{ $location->safeMapUrl() }}" target="_" title="Link to map.">
            <i class="bi bi bi-geo-alt-fill card-actions"></i>
        </a>
        @endif
@endif
@endforeach