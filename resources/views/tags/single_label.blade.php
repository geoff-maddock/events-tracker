<span class="label label-tag"><a href="/events/tag/{{ $tag->slug }}" class="label-link" title="List all events related to {{ $tag->name }}.">{{
    $tag->name
    }}</a>
<a href="{!! route('tags.show', ['tag' => $tag->slug]) !!}" title="Show this tag."><span
        class='glyphicon glyphicon-link text-info'></span></a>
</span>