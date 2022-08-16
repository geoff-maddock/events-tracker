@foreach ($entity->locations as $location)
@if (isset($location->visibility) && ($location->visibility->name != 'Guarded' || ($location->visibility->name == 'Guarded' && $signedIn)))
       @if (isset($location->map_url) && $location->map_url != '')
        <a href="{!! $location->map_url !!}" target="_" title="Link to map.">
            <i class="bi bi bi-geo-alt-fill card-actions"></i>
        </a>
        @endif
@endif
@endforeach