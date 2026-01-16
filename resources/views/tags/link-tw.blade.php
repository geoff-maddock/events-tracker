@if (isset($tag) && (strtolower($tag) === strtolower($t->name)))
    <?php $match = $t;?>
    <li class="flex items-center justify-between p-3 bg-primary/10 border border-primary/30 rounded-lg" id="tag-{{ $t->id }}">
        <a href="/tags/{{ $t->slug }}" class="text-lg font-medium text-primary hover:underline" title="Click to show all related events and entities.">
            {{ $t->name }}
        </a>
        @if ($signedIn)
            @if ($follow = $t->followedBy($user))
                <a href="{!! route('tags.unfollow', ['id' => $t->id]) !!}" data-target="#tag-{{ $t->id }}" class="ajax-action text-amber-500 hover:text-amber-400 transition-colors" title="Click to unfollow">
                    <i class="bi bi-dash-circle-fill"></i>
                </a>
            @else
                <a href="{!! route('tags.follow', ['id' => $t->id]) !!}" data-target="#tag-{{ $t->id }}" class="ajax-action text-primary hover:text-primary/80 transition-colors" title="Click to follow">
                    <i class="bi bi-plus-circle-fill"></i>
                </a>
            @endif
        @endif
    </li>
@else
    <li class="flex items-center justify-between p-3 bg-card border border-border rounded-lg hover:bg-accent/50 transition-colors" id="tag-{{ $t->id }}">
        <a href="/tags/{{ $t->slug }}" class="text-lg font-medium text-foreground hover:text-primary transition-colors">
            {{ $t->name }}
        </a>
        @if ($signedIn)
            @if ($follow = $t->followedBy($user))
                <a href="{!! route('tags.unfollow', ['id' => $t->id]) !!}" data-target="#tag-{{ $t->id }}" class="ajax-action text-amber-500 hover:text-amber-400 transition-colors" title="Click to unfollow">
                    <i class="bi bi-dash-circle-fill"></i>
                </a>
            @else
                <a href="{!! route('tags.follow', ['id' => $t->id]) !!}" data-target="#tag-{{ $t->id }}" class="ajax-action text-primary hover:text-primary/80 transition-colors" title="Click to follow">
                    <i class="bi bi-plus-circle-fill"></i>
                </a>
            @endif
        @endif
    </li>
@endif
