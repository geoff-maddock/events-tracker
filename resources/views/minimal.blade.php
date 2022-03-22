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
	<meta name="description" content="A calender of events, concerts, club nights, weekly and monthly events series, promoters, artists, producers, djs, venues and other entities.">
	<title>@yield('title','Event Guide') • {{ config('app.app_name')}}</title>
	
	<link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/apple-icon-180x180.png">
  	<link rel="alternate" type="application/rss+xml" href="{{ url('rss') }}"
    	    title="RSS Feed {{ config('app.app_name')}}">
    <style>
        body {
            font-family: sans-serif;
            font-size: 16px;
        }
    </style>
</head>
<body id="event-repo">
		<main id="app-content" class="container-fluid mt-2">

				@yield('content')

		</main>
	</div>
</body>
</html>
