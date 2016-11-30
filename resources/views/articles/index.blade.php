@extends('layout')

@section('content')

  @foreach ($articles as $article)
  <article>
    <h2>
	     <a href="{{ url('/articles', $article->id) }}">{{ $article->title }}</a>
    </h2>
    <small>{{$article->published_at}}</small>
  </article>
  @endforeach

  <br>

	{!! link_to_route('articles.create','Create an article') !!}
@stop
