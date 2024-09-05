<div class="col-md-2">
    <a href="{{ Storage::disk('external')->url($photo->getStoragePath()) }}" data-lightbox="grid" title="Click to see enlarged image"  data-toggle="tooltip" data-placement="bottom">
        <img src="{{ Storage::disk('external')->url($photo->getStorageThumbnail()) }}" alt="{{ $event->name}}"  class="mw-100">
    </a>

        @php $entities = $photo->entities; @endphp
        @foreach ($entities as $entity)
            {!! link_form_bootstrap_icon('bi bi-calendar2-event text-info', '/entities/'.$entity->slug, 'GET', 'Show '.$entity->name,'','','') !!}
        @endforeach

        {!! link_form_bootstrap_icon('bi bi-eye text-info', '/photos/'.$photo->id, 'GET', 'Show photo','','','') !!}


</div>