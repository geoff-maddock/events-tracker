<!DOCTYPE html>
<html lang="en">
<head>
    <!-- select based on default-theme -->
    @if ($theme !== config('app.default_theme'))
    	<link href="{{ asset('/css/light.css') }}" rel="stylesheet">
	@else
        {{--<link href="{{ asset('/css/'.$theme.'.css') }}" rel="stylesheet">--}}
		<link href="{{ asset('/css/dark.css') }}" rel="stylesheet">
    @endif
	<link href="{{ asset('/css/select2.css') }}" rel="stylesheet">
	<link href="{{ asset('/css/select2-bootstrap.min.css') }}" rel="stylesheet">
	<!-- Material Icons -->
	<link href="https://fonts.googleapis.com/icon?family=Material+Icons&display=swap" rel="stylesheet">
	<!-- Fonts -->
	<link href="//fonts.googleapis.com/css?family=Roboto:400,300&display=swap" rel="stylesheet" type="text/css">
	<link href="https://fonts.googleapis.com/css?family=Rubik&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

</head>
<body id="email-body" class="bg-light">

	@yield('content')

</body>
</html>
