@php
    $event = $tag->events()->visible($user ?? null)->latest('start_at')->first();
    $photo = $event ? $event->getPrimaryPhoto() : null;
    $following = $signedIn ? ($user->getTagsFollowing()->contains($tag)) : false;
@endphp
<div class="bg-card border border-border rounded-lg overflow-hidden hover:border-primary transition-colors group">
    <!-- Tag Image -->
    <a href="/tags/{{ $tag->slug }}" class="block aspect-square relative overflow-hidden">
        @if($photo)
            <img src="{{ Storage::disk('external')->url($photo->getStorageThumbnail()) }}" alt="{{ $tag->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
        @else
            <div class="w-full h-full flex items-center justify-center bg-muted">
                <i class="bi bi-tag text-4xl text-muted-foreground/30"></i>
            </div>
        @endif
    </a>

    <!-- Tag Info -->
    <div class="p-3">
        <div class="flex items-start justify-between gap-2">
            <div class="flex-1 min-w-0">
                <a href="/tags/{{ $tag->slug }}" class="block font-medium text-foreground hover:text-primary truncate text-sm">
                    {{ $tag->name }}
                </a>
            </div>
            @if ($signedIn)
                @if ($following)
                <a href="{!! route('tags.unfollow', ['id' => $tag->id]) !!}"
                    title="Unfollow"
                    class="flex-shrink-0 text-primary hover:text-primary/70 transition-colors">
                    <i class="bi bi-heart-fill"></i>
                </a>
                @else
                <a href="{!! route('tags.follow', ['id' => $tag->id]) !!}"
                    title="Follow"
                    class="flex-shrink-0 text-muted-foreground hover:text-primary transition-colors">
                    <i class="bi bi-heart"></i>
                </a>
                @endif
            @else
                <a href="{!! route('login') !!}"
                    title="Sign in to follow"
                    class="flex-shrink-0 text-muted-foreground hover:text-primary transition-colors">
                    <i class="bi bi-heart"></i>
                </a>
            @endif
        </div>
    </div>
</div>
