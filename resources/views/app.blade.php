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
	<meta property="og:description" content="@yield('og-description', 'A calender of events, concerts, club nights, weekly and monthly events series, promoters, artists, producers, djs, venues and other entities.')">
	<meta name="description" content="@yield('description', 'A calender of events, concerts, club nights, weekly and monthly events series, promoters, artists, producers, djs, venues and other entities.')">
	<meta property="fb:app_id" content="{{ config('app.fb_app_id') }}">
	@yield('facebook.meta')
	@yield('google.event.json')
	<meta name="theme-color" content="#636b6f"/>
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<title>@yield('title','Event Guide') • {{ config('app.app_name')}}</title>
	
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

	@yield('select2.include')

	<!-- Icons -->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.2/font/bootstrap-icons.css">
	<!-- Fonts -->
	<link rel="DNS-prefetch" href="//fonts.googleapis.com"/>
	<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin/>
	<link href="//fonts.googleapis.com/css?family=Roboto:400,300&display=swap" rel="stylesheet" type="text/css" media="print" onload="this.onload=null;this.removeAttribute('media');">
	<!-- Lightbox -->
	{{-- <link href="{{ asset('/css/lightbox.min.css') }}" rel="stylesheet"> --}}
	<link href="{{ asset('/css/lightbox.min.css') }}" rel="stylesheet" type="text/css" media="print" onload="this.onload=null;this.removeAttribute('media');">

	<!-- Full Calendar -->
	@yield('calendar.include')

	@if (config('app.google_tags') !== "")
	@include ('partials.analytics')
	@endif
</head>
<body id="event-repo" class="@if ($theme !== config('app.default_theme')) light @else dark @endif">
	@if (config('app.google_tags') !== "")
	<!-- Google Tag Manager (noscript) -->
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ config('app.google_tags')}}"
	height="0" width="0" class="d-none"></iframe></noscript>
	<!-- End Google Tag Manager (noscript) -->
	@endif
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
					<input type="text" class="form-control form-background" placeholder="Search" name="keyword" title="Search" aria-label="Search" value="{{ isset($slug) ? $slug : '' }}">
				</div>
			</form>
		</div>

		<main id="app-content" class="container-fluid mt-2 max-viewport">

				@yield('content')

            <event-list></event-list>
		</main>
	</div>
	<script src="{{ asset('/js/app.js') }}"></script>
	<script src="{{ asset('/js/jquery-3.5.1.min.js') }}"></script>
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
