@extends('layouts.app-tw')

@section('title', 'Add Comment')

@section('content')

<div class="max-w-4xl mx-auto">
	<!-- Page Header -->
	<div class="mb-6">
		<h1 class="text-3xl font-bold text-primary mb-2">
			{{ ucfirst($type) }} - <a href="{{ route(Str::plural($type).'.show', $object->slug) }}" class="hover:underline">{{ $object->name }}</a>
		</h1>
		<p class="text-muted-foreground">Add a new comment</p>
	</div>

	<!-- Form Card -->
	<div class="card-tw">
		<div class="p-6">
			<form action="{{ route(Str::plural($type).'.comments.store', $object->getRouteKey()) }}" method="POST">
				@csrf

				@include('comments.form-tw')
			</form>
		</div>
	</div>
</div>

@stop
