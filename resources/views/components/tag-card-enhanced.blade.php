@props(['tag', 'user' => null])

@php
    // Get recent events for this tag
    $recentEvents = $tag->events()
        ->visible($user ?? null)
        ->latest('start_at')
        ->take(9)
        ->get();
    
    // Get primary event (most recent or featured)
    $primaryEvent = $recentEvents->first();
    $primaryPhoto = $primaryEvent?->getPrimaryPhoto();
    
    // Get additional thumbnails (up to 8 more)
    $thumbnailEvents = $recentEvents->slice(1, 8);
    
    // Check if user follows this tag
    $isFollowing = false;
    if ($user) {
        $isFollowing = \App\Models\Follow::where('user_id', $user->id)
            ->where('object_type', 'tag')
            ->where('object_id', $tag->id)
            ->exists();
    }
@endphp

<div class="relative group bg-card border border-border rounded-lg overflow-hidden hover:border-primary transition-all duration-300 shadow-sm hover:shadow-lg">
    <a href="{{ route('tags.show', $tag->slug) }}" class="block">
        <!-- Primary Image -->
        <div class="aspect-video w-full overflow-hidden bg-muted">
            @if($primaryPhoto)
                <img src="{{ Storage::disk('external')->url($primaryPhoto->getStoragePath()) }}" 
                     alt="{{ $tag->name }}" 
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
            @else
                <div class="w-full h-full flex items-center justify-center">
                    <i class="bi bi-tag text-6xl text-muted-foreground/30"></i>
                </div>
            @endif
        </div>

        <!-- Thumbnail Grid -->
        @if($thumbnailEvents->count() > 0)
        <div class="grid grid-cols-4 gap-1 p-2 bg-background/50">
            @foreach($thumbnailEvents as $event)
                @php
                    $photo = $event->getPrimaryPhoto();
                @endphp
                @if($photo)
                <div class="aspect-square overflow-hidden rounded">
                    <img src="{{ Storage::disk('external')->url($photo->getStorageThumbnail()) }}" 
                         alt="{{ $event->name }}" 
                         class="w-full h-full object-cover">
                </div>
                @else
                <div class="aspect-square bg-muted rounded flex items-center justify-center">
                    <i class="bi bi-calendar text-xs text-muted-foreground/50"></i>
                </div>
                @endif
            @endforeach
            
            {{-- Fill remaining slots with placeholders if needed --}}
            @for($i = $thumbnailEvents->count(); $i < 4; $i++)
            <div class="aspect-square bg-muted/30 rounded"></div>
            @endfor
        </div>
        @endif

        <!-- Tag Name and Follow Status -->
        <div class="p-3 flex items-center justify-between border-t border-border">
            <h3 class="font-semibold text-foreground truncate flex-1">{{ $tag->name }}</h3>
            <div class="flex items-center gap-1 shrink-0 ml-2">
                @if($isFollowing)
                <i class="bi bi-star-fill text-primary" title="Following"></i>
                @else
                <i class="bi bi-star text-muted-foreground" title="Not following"></i>
                @endif
            </div>
        </div>
    </a>
</div>
