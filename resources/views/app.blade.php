<!DOCTYPE html>
<html lang="en" class="{{ $theme === config('app.default_theme') ? 'dark' : 'light' }}">
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
	
	<link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/apple-icon-180x180.png">
  	<link rel="alternate" type="application/rss+xml" href="{{ url('rss') }}"
    	    title="RSS Feed {{ config('app.app_name')}}">

	<!-- Tailwind CSS (New UI) -->
	<link href="{{ asset('/css/tailwind.css') }}" rel="stylesheet">

    <!-- Legacy Bootstrap CSS for backward compatibility -->
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
	<link href="https://fonts.bunny.net/css?family=nunito:400,600,700,800" rel="stylesheet" />
	<!-- Lightbox -->
	<link href="{{ asset('/css/lightbox.min.css') }}" rel="stylesheet" type="text/css" media="print" onload="this.onload=null;this.removeAttribute('media');">

	<!-- Full Calendar -->
	@yield('calendar.include')

	@if (config('app.google_tags') !== "")
	@include ('partials.analytics')
	@endif

	<style>
		/* Dark mode base styles */
		.dark body {
			background-color: #0f172a;
			color: #e2e8f0;
		}
		
		.light body {
			background-color: #f8fafc;
			color: #1e293b;
		}
		
		/* Sidebar styles */
		.sidebar {
			background-color: #1e293b;
			border-right: 1px solid #334155;
		}
		
		.light .sidebar {
			background-color: #ffffff;
			border-right: 1px solid #e2e8f0;
		}
		
		/* Main content area */
		.main-content {
			background-color: #0f172a;
		}
		
		.light .main-content {
			background-color: #f8fafc;
		}
	</style>
</head>
<body id="event-repo" class="{{ $theme === config('app.default_theme') ? 'dark' : 'light' }}">
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

	<div class="flex min-h-screen">
		<!-- Sidebar -->
		@include('partials.sidebar-tw')
		
		<!-- Main Content -->
		<div class="flex-1 flex flex-col main-content">
			<!-- Mobile Search -->
			<div class="md:hidden p-4">
				<form role="search" action="/search">
					<input type="text" 
						class="w-full px-4 py-2 bg-dark-card border border-dark-border rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary" 
						placeholder="Search events, venues, artists..." 
						name="keyword" 
						title="Search" 
						aria-label="Search" 
						value="{{ isset($search) ? $search : '' }}">
				</form>
			</div>

			<!-- Main Content Area -->
			<main id="app-content" class="flex-1 p-4 md:p-6 overflow-auto">
				@yield('content')
				<event-list></event-list>
			</main>
		</div>
	</div>

	<script src="{{ asset('/js/app.js') }}"></script>
	<script src="{{ asset('/js/jquery-3.5.1.min.js') }}"></script>
	<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.full.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.10.0/js/lightbox.min.js"></script>
    <script src="{{ asset('/js/jquery.ba-throttle-debounce.min.js') }}"></script>
	<script src="{{ asset('/js/auto-submit.js') }}"></script>
	<script src="{{ asset('/js/custom.js') }}"></script>

	<!-- Theme Toggle Script -->
	<script>
		function toggleTheme() {
			const html = document.documentElement;
			const body = document.body;
			const isDark = html.classList.contains('dark');
			
			if (isDark) {
				html.classList.remove('dark');
				html.classList.add('light');
				body.classList.remove('dark');
				body.classList.add('light');
				localStorage.setItem('theme', 'light');
				// Update server-side theme
				fetch('/theme/light', { method: 'GET' });
			} else {
				html.classList.remove('light');
				html.classList.add('dark');
				body.classList.remove('light');
				body.classList.add('dark');
				localStorage.setItem('theme', 'dark');
				fetch('/theme/dark', { method: 'GET' });
			}
		}
		
		// Initialize theme from localStorage
		document.addEventListener('DOMContentLoaded', function() {
			const savedTheme = localStorage.getItem('theme');
			if (savedTheme) {
				const html = document.documentElement;
				const body = document.body;
				html.classList.remove('dark', 'light');
				body.classList.remove('dark', 'light');
				html.classList.add(savedTheme);
				body.classList.add(savedTheme);
			}
		});
	</script>

	@yield('scripts.footer')
	@yield('footer')
	@include('flash')
</body>
</html>
