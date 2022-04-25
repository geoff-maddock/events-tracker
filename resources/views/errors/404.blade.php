@extends('app')

@section('title','Event View')

@section('content')

	<head>
		<link href='//fonts.googleapis.com/css?family=Lato:100' rel='stylesheet' type='text/css'>
	    <!-- select based on default-theme -->
		<!-- select based on default-theme -->
		@if ($theme !== config('app.default_theme'))
			<link href="{{ asset('/css/light.css') }}" rel="stylesheet">
		@else
			<link href="{{ asset('/css/dark.css') }}" rel="stylesheet">
		@endif
		<style>
			body {
				margin: 0;
				padding: 0;
				width: 100%;
				height: 100%;
				color: #B0BEC5;
				display: table;
				font-weight: 100;
				font-family: 'Lato';
			}

			.container {
				text-align: center;
				display: table-cell;
				vertical-align: middle;
			}

			.content {
				text-align: center;
				display: inline-block;
			}

			.title {
				font-size: 4em;
				margin-bottom: 3em;
			}
		</style>
	</head>

	<body id="event-repo" class="@if ($theme !== config('app.default_theme')) light @else dark @endif">
			<div class="title">404 page not found.</div> <a href="{{ URL::previous() }}">Go Back</a>
	</body>
@stop
