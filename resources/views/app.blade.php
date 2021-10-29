<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta property="og:url" content="{{ Request::url() }}">
	<meta property="og:type" content="website">
	<meta property="og:title" content="@yield('title', config('app.app_name'))">
	<meta property="og:image" content="@yield('og-image', url('/').'/apple-icon-180x180.png')">
	<meta property="og:description" content="@yield('og-description', 'A calender of events, converts, club nights, weekly and monthly events series, promoters, artists, producers, djs, venues and other entities.')">
	<meta name="description" content="A calender of events, converts, club nights, weekly and monthly events series, promoters, artists, producers, djs, venues and other entities.">
	<meta property="fb:app_id" content="{{ config('app.fb_app_id') }}">
	@yield('facebook.meta')
	<meta name="theme-color" content="#636b6f"/>
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<title>{{ config('app.app_name')}} - Event + Club Guide - @yield('title')</title>
	
	<link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/apple-icon-180x180.png">
  	<link rel="alternate" type="application/rss+xml" href="{{ url('rss') }}"
    	    title="RSS Feed {{ config('app.app_name')}}">

    <!-- select based on default-theme -->
    @if ($theme !== config('app.default_theme'))
    	<link href="{{ asset('/css/light.css') }}" rel="stylesheet">
	@else
		<link href="{{ asset('/css/dark.css') }}" rel="stylesheet">
    @endif
	<!-- Select2 -->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.1.1/dist/select2-bootstrap-5-theme.min.css" />
	<!-- Icons -->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
	<!-- Fonts -->
	<link href="//fonts.googleapis.com/css?family=Roboto:400,300&display=swap" rel="stylesheet" type="text/css">
	<!-- Lightbox -->
	<link href="{{ asset('/css/lightbox.min.css') }}" rel="stylesheet">
	<!-- Full Calendar -->
	<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.6.0/main.min.css' rel='stylesheet' />
	<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.6.0/main.min.js"></script>
	@include ('partials.analytics') 
</head>
<body id="event-repo">
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ config('app.google_tags')}}"
	height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<!-- End Google Tag Manager (noscript) -->
	<script src="{{ asset('/js/global-config.js') }}"></script>

	<div id="loading" class="loading">
		<div class="spinner"></div>
	</div>
	<div id="flash" class="flash"></div>

	@include('partials.nav')

	<div id="app-container">
		<div id="app-mobile-search" class="container-fluid d-block d-md-none my-2">
			<form class="col-sm-12" role="search" action="/search">
				<div class="form-group">
					<input type="text" class="form-control form-background" placeholder="Search" name="keyword"  value="{{ isset($slug) ? $slug : '' }}">
				</div>
			</form>
		</div>

		<div id="app-content" class="container-fluid mt-2">

				@yield('content')

            <event-list></event-list>
		</div>
	</div>
	<script src="{{ asset('/js/app.js') }}"></script>
	<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.full.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.10.0/js/lightbox.min.js"></script>
    <script src="{{ asset('/js/jquery.ba-throttle-debounce.min.js') }}"></script>
	<script src="{{ asset('/js/auto-submit.js') }}"></script>
	<script src="{{ asset('/js/custom.js') }}"></script>
	@yield('scripts.footer')
	@yield('footer')
	@include('flash')
</body>
</html>
