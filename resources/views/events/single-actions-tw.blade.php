{{-- The edit, thread and external-link icons of an event row or card (events.single-tw). --}}
@if ($signedIn && $user->can('update', $event))
<a href="{!! route('events.edit', ['event' => $event->slug]) !!}"
    class="text-muted-foreground hover:text-primary transition-colors"
    title="Edit this event">
    <i class="bi bi-pencil-fill"></i>
</a>
@endif

@if ($thread = $event->threads->first())
<a href="{!! route('threads.show', ['thread' => $thread->id]) !!}"
   class="text-muted-foreground hover:text-primary transition-colors"
   title="Show related thread">
    <i class="bi bi-chat-fill"></i>
</a>
@endif

@if ($link = $event->primary_link)
<a href="{{ $link }}"
   class="text-muted-foreground hover:text-primary transition-colors"
   title="External link" target="_blank" rel="noopener">
    <i class="bi bi-link-45deg text-xl"></i>
</a>
@endif
