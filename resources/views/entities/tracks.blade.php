@if (count($tracks) > 0)
<video class="op-player__media" id="player" controls playsinline>
	@foreach ($tracks as $track)
    <source src="{!! $track !!}" type="audio/mp3"/>
    @endforeach
</video>
@endif
