@extends('layouts.app-tw')

@section('content')
	<div class="max-w-4xl mx-auto">
		@include('reviews.single-tw', ['review' => $review])
	</div>
@endsection
