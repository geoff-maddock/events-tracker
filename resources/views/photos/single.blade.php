<div class="col-md-2">
    <a href="{{ Storage::disk('external')->url($photo->getStoragePath()) }}" data-lightbox="grid" title="Click to see enlarged image"  data-toggle="tooltip" data-placement="bottom">
        <img src="{{ Storage::disk('external')->url($photo->getStorageThumbnail()) }}" alt="{{ $event->name}}"  class="mw-100">
    </a>
    @if ($user && (Auth::user()->id == $event->user?->id || $user->id == Config::get('app.superuser') ) )
        @if ($signedIn || $user->id == Config::get('app.superuser'))
            {!! link_form_bootstrap_icon('bi bi-trash-fill text-warning', $photo, 'DELETE', 'Delete the photo') !!}
            @if ($photo->is_primary)
            {!! link_form_bootstrap_icon('bi bi-star-fill text-primary', '/photos/'.$photo->id.'/unset-primary', 'POST', 'Primary Photo [Click to unset]','','','') !!}
            @else
            {!! link_form_bootstrap_icon('bi bi-star text-info', '/photos/'.$photo->id.'/set-primary', 'POST', 'Set as primary photo','','','') !!}
            @endif
            @if ($photo->is_event)
            {!! link_form_bootstrap_icon('bi bi-calendar2-event-fill text-primary', '/photos/'.$photo->id.'/unset-event', 'POST', 'Event photo [Click to unset]','','','') !!}
            @else
            {!! link_form_bootstrap_icon('bi bi-calendar2-event text-info', '/photos/'.$photo->id.'/set-event', 'POST', 'Set as event photo','','','') !!}
            @endif
            {!! link_form_bootstrap_icon('bi bi-eye text-info', '/photos/'.$photo->id, 'GET', 'Show photo','','','') !!}

        @endif
    @endif
</div>