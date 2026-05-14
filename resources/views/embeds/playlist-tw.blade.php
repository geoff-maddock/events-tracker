@if (isset($embeds) && count($embeds) > 0)

<div class="rounded-lg border bg-card shadow p-6 space-y-4">
    <div class="flex items-center gap-2 mb-2">
        <i class="bi bi-music-note-beamed text-lg"></i>
        <h2 class="text-xl font-semibold">Audio</h2>
    </div>
    
    <div class="space-y-4">
        @foreach ($embeds as $embed)
        <div class="rounded-md overflow-hidden">    
            {!! $embed !!}
        </div>
        @endforeach
    </div>
</div>

@else

    @if (isset($event))
        @php
            // Estimate embed count from the event description URLs + related entity links.
            $expectedEmbedCount = 0;
            $regex = "/\b(?:(?:https|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i";
            preg_match_all($regex, $event->description ?? '', $result);
            foreach (($result[0] ?? []) as $url) {
                if (str_contains($url, 'soundcloud.com') || str_contains($url, 'bandcamp.com')) {
                    $expectedEmbedCount++;
                }
            }
            if ($event->relationLoaded('entities')) {
                foreach ($event->entities as $relatedEntity) {
                    if ($relatedEntity->relationLoaded('links')) {
                        foreach ($relatedEntity->links as $link) {
                            if (str_contains($link->url, 'soundcloud.com') || str_contains($link->url, 'bandcamp.com')) {
                                $expectedEmbedCount++;
                            }
                        }
                    }
                }
            }
        @endphp
        @if ($expectedEmbedCount > 0)
        <div id="playlist-{{ $event->id}}" class="playlist-id rounded-lg border bg-card shadow p-6" data-url="/events/{{ $event->id }}/load-embeds" data-slug="{{ $event->slug }}" data-resource-type="events" style="min-height: {{ 80 + $expectedEmbedCount * 148 }}px;">
            <div class="flex items-center gap-2 mb-4">
                <i class="bi bi-music-note-beamed text-lg"></i>
                <h2 class="text-xl font-semibold">Audio</h2>
            </div>
            <div class="flex items-center justify-center py-8">
                <div class="load-spinner">
                    <div class="double-bounce1"></div>
                    <div class="double-bounce2"></div>
                </div>
            </div>
        </div>
        @endif
    @endif
    @if (isset($entity))
        @php
            // Estimate embed count to reserve space and avoid layout shift when the
            // deferred AJAX response replaces this placeholder. Medium-size iframes
            // render at 120px tall; we add ~28px per embed for the wrapper spacing,
            // plus ~80px for the section header.
            $expectedEmbedCount = 0;
            if ($entity->relationLoaded('links')) {
                foreach ($entity->links as $link) {
                    if (str_contains($link->url, 'soundcloud.com') || str_contains($link->url, 'bandcamp.com')) {
                        $expectedEmbedCount++;
                    }
                }
            }
        @endphp
        @if ($expectedEmbedCount > 0)
        <div id="playlist-{{ $entity->id}}" class="playlist-id rounded-lg border bg-card shadow p-6" data-url="/entities/{{ $entity->id }}/load-embeds" data-slug="{{ $entity->slug }}" data-resource-type="entities" style="min-height: {{ 80 + $expectedEmbedCount * 148 }}px;">
            <div class="flex items-center gap-2 mb-4">
                <i class="bi bi-music-note-beamed text-lg"></i>
                <h2 class="text-xl font-semibold">Audio</h2>
            </div>
            <div class="flex items-center justify-center py-8">
                <div class="load-spinner">
                    <div class="double-bounce1"></div>
                    <div class="double-bounce2"></div>
                </div>
            </div>
        </div>
        @endif
    @endif
    @if (isset($series))
        @php
            $expectedEmbedCount = 0;
            $regex = "/\b(?:(?:https|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i";
            preg_match_all($regex, $series->description ?? '', $result);
            foreach (($result[0] ?? []) as $url) {
                if (str_contains($url, 'soundcloud.com') || str_contains($url, 'bandcamp.com')) {
                    $expectedEmbedCount++;
                }
            }
            if ($series->relationLoaded('entities')) {
                foreach ($series->entities as $relatedEntity) {
                    if ($relatedEntity->relationLoaded('links')) {
                        foreach ($relatedEntity->links as $link) {
                            if (str_contains($link->url, 'soundcloud.com') || str_contains($link->url, 'bandcamp.com')) {
                                $expectedEmbedCount++;
                            }
                        }
                    }
                }
            }
        @endphp
        @if ($expectedEmbedCount > 0)
        <div id="playlist-{{ $series->id}}" class="playlist-id rounded-lg border bg-card shadow p-6" data-url="/series/{{ $series->id }}/load-embeds" data-slug="{{ $series->slug }}" data-resource-type="series" style="min-height: {{ 80 + $expectedEmbedCount * 148 }}px;">
            <div class="flex items-center gap-2 mb-4">
                <i class="bi bi-music-note-beamed text-lg"></i>
                <h2 class="text-xl font-semibold">Audio</h2>
            </div>
            <div class="flex items-center justify-center py-8">
                <div class="load-spinner">
                    <div class="double-bounce1"></div>
                    <div class="double-bounce2"></div>
                </div>
            </div>
        </div>
        @endif
    @endif

@endif
