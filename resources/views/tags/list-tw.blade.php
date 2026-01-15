@if (isset($tags) && count($tags) > 0)

<ul class="space-y-2">
    @foreach ($tags as $tag)
        <li class="flex items-center justify-between p-3 bg-card border border-border rounded-lg hover:bg-accent/50 transition-colors">
            <div class="flex items-center gap-3 flex-wrap">
                <a href="{{ route('tags.show', [$tag->slug]) }}" class="text-lg font-medium text-foreground hover:text-primary transition-colors">
                    {{ $tag->name }}
                </a>

                @if ($signedIn)
                    @if ($follow = $tag->followedBy($user))
                        <a href="{{ route('tags.unfollow', ['id' => $tag->id]) }}" title="You are following this tag. Click to unfollow" class="text-primary hover:text-primary/80 transition-colors">
                            <i class="bi bi-check-circle-fill"></i>
                        </a>
                    @else
                        <a href="{{ route('tags.follow', ['id' => $tag->id]) }}" title="Click to follow this tag" class="text-muted-foreground hover:text-primary transition-colors">
                            <i class="bi bi-plus-circle"></i>
                        </a>
                    @endif

                    @if (Auth::user()->id == Config::get('app.superuser'))
                        <a href="{{ route('tags.edit', ['tag' => $tag->slug]) }}" title="Click to edit" class="text-muted-foreground hover:text-foreground transition-colors">
                            <i class="bi bi-pencil-fill"></i>
                        </a>
                    @endif
                @endif
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                @if (isset($tag->events_count) && $tag->events_count > 0)
                    <a href="{{ route('events.tag', [$tag->slug]) }}" class="badge-tw badge-secondary-tw">
                        Events {{ $tag->events_count }}
                    </a>
                @endif
                @if (isset($tag->series_count) && $tag->series_count > 0)
                    <a href="{{ route('series.tag', [$tag->slug]) }}" class="badge-tw badge-secondary-tw">
                        Series {{ $tag->series_count }}
                    </a>
                @endif
                @if (isset($tag->entities_count) && $tag->entities_count > 0)
                    <a href="{{ route('entities.tag', [$tag->slug]) }}" class="badge-tw badge-secondary-tw">
                        Entities {{ $tag->entities_count }}
                    </a>
                @endif
                @if (isset($tag->threads_count) && $tag->threads_count > 0)
                    <a href="{{ route('threads.tag', [$tag->slug]) }}" class="badge-tw badge-secondary-tw">
                        Threads {{ $tag->threads_count }}
                    </a>
                @endif
            </div>
        </li>
    @endforeach
</ul>

@else
    <div class="text-center py-8 text-muted-foreground italic">
        No tags listed
    </div>
@endif
