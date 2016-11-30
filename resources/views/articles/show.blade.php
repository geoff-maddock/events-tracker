@extends('layout')

@section('content')

	<h2>{{ $article->title }}</h2>

	@if ($article->body)
	<article class="body">
		{!! nl2br($article->body) !!}
	</article>
	@endif

	{!! link_to_route('articles.index','Return to list') !!}
@stop
