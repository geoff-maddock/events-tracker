@extends('layouts.app-tw')

@section('title', 'Forums')

@section('content')

<div class="container mx-auto">
	<!-- Page Header -->
	<div class="flex justify-between items-center mb-6">
		<h1 class="text-3xl font-bold text-primary">Forums</h1>
		<a href="{{ route('forums.create') }}" class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
			<i class="bi bi-plus-circle mr-2"></i>
			Add Forum
		</a>
	</div>

	<!-- Forums List -->
	@include('forums.list-tw', ['forums' => $forums])
</div>

@stop
