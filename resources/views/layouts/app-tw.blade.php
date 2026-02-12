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
	<meta name="theme-color" content="#0f172a"/>
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<title>@yield('title','Event Guide') • {{ config('app.app_name')}}</title>

	<!-- Theme initialization script - must run before page renders to prevent flash -->
	<script>
		(function() {
			const theme = localStorage.getItem('theme') || '{{ $theme ?? config("app.default_theme") }}';
			document.documentElement.classList.add(theme);
		})();
	</script>

	<link rel="manifest" href="/manifest.json">
	<link rel="apple-touch-icon" href="/apple-icon-180x180.png">
	<link rel="alternate" type="application/rss+xml" href="{{ url('rss') }}"
		title="RSS Feed {{ config('app.app_name')}}">

	<!-- Tailwind CSS with Arcane City design system -->
	<link href="{{ asset('/css/tailwind.css') }}" rel="stylesheet">

	@yield('select2.include')

	<!-- Icons -->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.2/font/bootstrap-icons.css">
	<!-- Fonts -->
	<link rel="DNS-prefetch" href="//fonts.googleapis.com"/>
	<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin/>
	<link href="//fonts.googleapis.com/css?family=Roboto:400,300&display=swap" rel="stylesheet" type="text/css" media="print" onload="this.onload=null;this.removeAttribute('media');">
	<link href="https://fonts.bunny.net/css?family=nunito:400,600,700,800" rel="stylesheet" />
	<!-- Lightbox -->
	<link href="{{ asset('/css/lightbox.min.css') }}" rel="stylesheet" type="text/css" media="print" onload="this.onload=null;this.removeAttribute('media');">

	<!-- Full Calendar -->
	@yield('calendar.include')

	@if (config('app.google_tags') !== "")
	@include ('partials.analytics')
	@endif
</head>
<body>
	<script>
		// Set body theme class from localStorage
		(function() {
			const theme = localStorage.getItem('theme') || '{{ $theme ?? config("app.default_theme") }}';
			document.body.classList.add(theme);
		})();
	</script>
	@if (config('app.google_tags') !== "")
	<!-- Google Tag Manager (noscript) -->
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ config('app.google_tags')}}"
	height="0" width="0" class="hidden" title="Google Tag Manager"></iframe></noscript>
	<!-- End Google Tag Manager (noscript) -->
	@endif
	<script src="{{ asset('/js/global-config.js') }}"></script>

	<div id="loading" class="loading">
		<div class="spinner"></div>
	</div>
	<div id="flash" class="flash"></div>

	<div class="flex min-h-screen lg:h-screen bg-background">
		<!-- Sidebar -->
		@include('partials.sidebar-tw')

		<!-- Main Content -->
		<div class="flex-1 flex flex-col bg-background lg:overflow-hidden">
			<!-- Top Bar -->
			@include('partials.topbar-tw')

			<!-- Main Content Area -->
			<main class="flex-1 min-w-0 w-full mx-auto p-4 md:p-6 overflow-x-hidden overflow-y-auto bg-background max-w-[2400px]">
				@yield('content')
			</main>
		</div>
	</div>

	<!-- Alpine.js for reactive components -->
	<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

	<!-- Webpack bundles must load in correct order: manifest -> vendor -> app -->
	<script src="{{ asset('/js/manifest.js') }}"></script>
	<script src="{{ asset('/js/vendor.js') }}"></script>
	<script src="{{ asset('/js/app.js') }}"></script>
	<script src="{{ asset('/js/jquery-3.5.1.min.js') }}"></script>
	<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.full.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.10.0/js/lightbox.min.js"></script>
	<script src="{{ asset('/js/jquery.ba-throttle-debounce.min.js') }}"></script>
	<script src="{{ asset('/js/auto-submit.js') }}"></script>
	<script src="{{ asset('/js/embed-cache.js') }}"></script>
	<script src="{{ asset('/js/custom.js') }}"></script>
	<script src="{{ asset('/js/password-toggle.js') }}"></script>
	
	@yield('scripts.footer')
	@yield('footer')
	@include('flash')
</body>
</html>
