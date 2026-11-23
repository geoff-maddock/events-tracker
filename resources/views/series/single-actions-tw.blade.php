{{-- The edit, next-occurrence and follow icons of a series row or card (series.single-tw). --}}
@if ($signedIn && $user->can('update', $series))
<a href="{!! route('series.edit', ['series' => $series->slug]) !!}"
    class="text-muted-foreground hover:text-primary transition-colors"
    title="Edit this series">
    <i class="bi bi-pencil-fill"></i>
</a>

<a href="{!! route('series.createOccurrence', ['id' => $series->id]) !!}"
    class="text-muted-foreground hover:text-primary transition-colors"
    title="Create next occurrence">
    <i class="bi bi-calendar-plus"></i>
</a>
@endif

@if ($signedIn)
    @if (in_array($series->id, $user->followedIds('series'), true))
    <a data-method="post" href="{!! route('series.unfollow', ['id' => $series->id]) !!}"
       class="text-primary hover:text-destructive transition-colors"
       title="Unfollow">
        <i class="bi bi-dash-circle-fill"></i>
    </a>
    @else
    <a data-method="post" href="{!! route('series.follow', ['id' => $series->id]) !!}"
       class="text-muted-foreground hover:text-primary transition-colors"
       title="Follow">
        <i class="bi bi-plus-circle-fill"></i>
    </a>
    @endif
@endif
