<div class="col-md-2">
    <a href="{{ Storage::disk('external')->url($photo->getStoragePath()) }}" data-lightbox="grid" title="Click to see enlarged image"  data-toggle="tooltip" data-placement="bottom">
        <img src="{{ Storage::disk('external')->url($photo->getStorageThumbnail()) }}" alt="{{ $event->name}}"  class="mw-100">
    </a>
</div>