@if (isset($embeds) && count($embeds) > 0)
<div>      
    @php 
        $count = 0;
        $limit = 1;
    @endphp 
    @foreach ($embeds as $embed)
        {!! $embed !!}
        @php
            $count++
        @endphp
        @if ($count >= $limit)
            @php break; @endphp
        @endif
    @endforeach
</div>
@else
    {{-- @elseif, not three independent @ifs: @include inherits the caller's scope, so a
         stray $event/$entity from a surrounding @foreach must not render a second
         placeholder alongside the subject the caller actually passed. --}}
    @php
        $isRecord = fn ($value) => is_object($value) && !($value instanceof \Illuminate\Pagination\LengthAwarePaginator);
        [$playlistType, $playlistRecord] = match (true) {
            isset($event) && $isRecord($event) => ['events', $event],
            isset($series) && $isRecord($series) => ['series', $series],
            isset($entity) && $isRecord($entity) => ['entities', $entity],
            default => [null, null],
        };
    @endphp
    {{-- No placeholder, and so no request, when nothing on the record could become a
         player. data-version changes when the record, its entities or their links do,
         which is what makes the browser drop its own cached answer (#2389). --}}
    @if ($playlistRecord && \App\Services\Embeds\OembedExtractor::mayHaveEmbeds($playlistRecord))
        <div id="playlist-{{ $playlistType }}-{{ $playlistRecord->id }}" class="playlist-id rounded-lg border bg-card shadow p-4 hidden" data-url="/{{ $playlistType }}/{{ $playlistRecord->id }}/load-minimal-embeds" data-slug="{{ $playlistRecord->slug }}" data-resource-type="{{ $playlistType }}" data-version="{{ \App\Services\Embeds\OembedExtractor::versionFor($playlistRecord) }}" data-lazy>
            <div class="flex items-center justify-center py-4">
                <div class="load-spinner">
                    <div class="double-bounce1"></div>
                    <div class="double-bounce2"></div>
                </div>
            </div>
        </div>
    @endif
@endif
