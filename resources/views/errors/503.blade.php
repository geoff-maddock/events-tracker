@extends('app')

@section('title','Event View')

@section('content')

	<head>
		<link href='//fonts.googleapis.com/css?family=Lato:100' rel='stylesheet' type='text/css'>
	    <!-- select based on default-theme -->
	    @if ($u = Auth::user() && (Auth::user()->profile->default_theme != 'dark-theme'))
	    	<link href="{{ asset('/css/'.Auth::user()->profile->default_theme.'.css') }}" rel="stylesheet">
	        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
	    @else
	        <!-- TODO Fix Bootstrap Theme "Superhero" - ORDER OF style vs superhero theme matters-->
	        <link href="{{ asset('/css/dark-theme.css') }}" rel="stylesheet">
	        <link href="{{ asset('/css/superhero-bootstrap.min.css') }}" rel="stylesheet">
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
				font-size: 72px;
				margin-bottom: 40px;
			}
		</style>
	</head>

	<body id="event-repo">
			<div class="title">Be right back.</a>
	</body>
@stop
