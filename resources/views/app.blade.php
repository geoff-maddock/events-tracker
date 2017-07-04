<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta property="og:url" content="{{ Request::url() }}">
	<meta property="og:title" content="@yield('title')">
	<meta property="og:description" content="A guide and calander of events, weekly and  monthly series, promoters, artists, producers, djs, venues and other entities.">
	<meta property="fb:app_id" content="{{ config('app.fb_app_id') }}">
	<title>{{ config('app.app_name')}} - Event + Club Guide - @yield('title')</title>
  	<link rel="alternate" type="application/rss+xml" href="{{ url('rss') }}"
    	    title="RSS Feed {{ config('blog.title') }}">

	<link href="{{ asset('/css/app.css') }}" rel="stylesheet">
	<link href="{{ asset('/css/light-theme.css') }}" rel="stylesheet">
	<!--
	<link href="{{ asset('/css/bootstrap.min.css') }}" rel="stylesheet">
	-->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">	<link href="{{ asset('/css/select2.css') }}" rel="stylesheet">
	<link href="{{ asset('/css/sweetalert.css') }}" rel="stylesheet">
	<link href="//cdnjs.cloudflare.com/ajax/libs/fullcalendar/2.6.0/fullcalendar.min.css" rel="stylesheet">
	<link href="//cdnjs.cloudflare.com/ajax/libs/dropzone/4.2.0/min/dropzone.min.css" rel="stylesheet">
	<link href="//cdnjs.cloudflare.com/ajax/libs/lightbox2/2.8.2/css/lightbox.css" rel="stylesheet">
	<!-- Material Icons -->
	<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
	<!-- Fonts -->
	<link href='//fonts.googleapis.com/css?family=Roboto:400,300' rel='stylesheet' type='text/css'>

	<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
	<!--[if lt IE 9]>
		<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
		<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script> 
	<![endif]-->
</head>
<body id="event-repo">
	<script src="{{ asset('/js/global-config.js') }}"></script>
	@include('partials.nav')

	<div class="container-fluid">

		<div class="row">
			<div class="col-md-12">
				@yield('content')
			</div>
		</div>
	</div>
	<!-- Scripts -->
	<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
	<!--
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
	-->
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-hover-dropdown/2.2.1/bootstrap-hover-dropdown.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/js/select2.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.11.1/moment.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/fullcalendar/2.6.0/fullcalendar.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/lightbox2/2.8.2/js/lightbox.min.js"></script>


	<script src="{{ asset('/js/sweetalert-dev.js') }}"></script>

	@yield('scripts.footer')
	@yield('footer')
	@include('flash')
</body>
</html>
