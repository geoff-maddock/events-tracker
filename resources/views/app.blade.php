<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta property="og:url" content="{{ Request::url() }}">
	<meta property="og:title" content="@yield('title')">
	<meta property="og:description" content="A calender of events, converts, club nights, weekly and monthly events series, promoters, artists, producers, djs, venues and other entities.">
	<meta name="description" content="A calender of events, converts, club nights, weekly and monthly events series, promoters, artists, producers, djs, venues and other entities.">
	<meta property="fb:app_id" content="{{ config('app.fb_app_id') }}">
	<meta name="theme-color" content="#636b6f"/>
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<title>{{ config('app.app_name')}} - Event + Club Guide - @yield('title')</title>
	<link rel="manifest" href="/manifest.json">
  	<link rel="alternate" type="application/rss+xml" href="{{ url('rss') }}"
    	    title="RSS Feed {{ config('blog.title') }}">

    <!-- select based on default-theme -->
    @if ($theme != config('app.default_theme'))
    	<link href="{{ asset('/css/light.css') }}" rel="stylesheet">
	@else
        {{--<link href="{{ asset('/css/'.$theme.'.css') }}" rel="stylesheet">--}}
		<link href="{{ asset('/css/dark.css') }}" rel="stylesheet">
    @endif
	<link href="{{ asset('/css/select2.css') }}" rel="stylesheet">
	<link href="{{ asset('/css/select2-bootstrap.min.css') }}" rel="stylesheet">
	<!-- Material Icons -->
	<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
	<!-- Fonts -->
	<link href='//fonts.googleapis.com/css?family=Roboto:400,300' rel='stylesheet' type='text/css'>
	<link href="https://fonts.googleapis.com/css?family=Rubik&display=swap" rel="stylesheet">

</head>
<body id="event-repo">

	<script src="{{ asset('/js/global-config.js') }}"></script>

	<div id="loading" class="loading">
		<div class="spinner"></div>
	</div>
	<div id="flash" class="flash"></div>

	@include('partials.nav')

	<div id="app-container" class="container-fluid">
		<div id="app-mobile-search" class="container-fluid visible-xs-block">
			<form class="col-xs-12" role="search" action="/search">
				<div class="form-group">
					<input type="text" class="form-control" placeholder="Search" name="keyword"  value="{{ isset($slug) ? $slug : '' }}">
				</div>
			</form>
		</div>

		<div id="app-content" class="row">
			<div id="app-content-columns" class="col-md-12">
				@yield('content')
			</div>
		</div>
	</div>

	<script src="{{ asset('/js/app.js') }}"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-hover-dropdown/2.2.1/bootstrap-hover-dropdown.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.10/js/select2.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.3.1/fullcalendar.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/lightbox2/2.10.0/js/lightbox.min.js"></script>
    <script src="{{ asset('/js/jquery.ba-throttle-debounce.min.js') }}"></script>
	<script src="{{ asset('/js/auto-submit.js') }}"></script>
	<script src="{{ asset('/js/custom.js') }}"></script>

	@yield('scripts.footer')
	@yield('footer')
	@include('flash')
</body>
</html>
