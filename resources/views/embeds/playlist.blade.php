@if (count($embeds) > 0)
<P><b>Audio</b><br>
	@foreach ($embeds as $embed)
    {!! $embed !!}
    @endforeach
</p>
@endif
