<span class="label label-tag"><a href="/events/relatedto/{{ urlencode($entity->slug) }}" title="List all events related to {{ $entity->name }}.">{{ $entity->name }}</a>
    <a href="{!! route('entities.show', ['entity' => $entity->slug]) !!}" title="Show this entity."><span
            class='glyphicon glyphicon-link text-info'></span></a>
</span>