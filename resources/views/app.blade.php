<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Event Repo - Club Guide - @yield('title')</title>

	<link href="{{ asset('/css/app.css') }}" rel="stylesheet">
	<link href="{{ asset('/css/style.css') }}" rel="stylesheet">
	<link href="{{ asset('/css/bootstrap.min.css') }}" rel="stylesheet">
	<link href="{{ asset('/css/select2.css') }}" rel="stylesheet">
	<link href="{{ asset('/css/sweetalert.css') }}" rel="stylesheet">
	<link href="//cdnjs.cloudflare.com/ajax/libs/fullcalendar/2.6.0/fullcalendar.min.css" rel="stylesheet">
	<link href="//cdnjs.cloudflare.com/ajax/libs/dropzone/4.2.0/min/dropzone.min.css" rel="stylesheet">
	<link href="//cdnjs.cloudflare.com/ajax/libs/lightbox2/2.8.2/css/lightbox.css" rel="stylesheet">

	<!-- Fonts -->
	<link href='//fonts.googleapis.com/css?family=Roboto:400,300' rel='stylesheet' type='text/css'>

	<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
	<!--[if lt IE 9]>
		<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
		<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script> 
	<![endif]-->
</head>
<body>

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
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-hover-dropdown/2.2.1/bootstrap-hover-dropdown.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/js/select2.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.11.1/moment.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/fullcalendar/2.6.0/fullcalendar.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/lightbox2/2.8.2/js/lightbox.min.js"></script>

	<script src="{{ asset('/js/sweetalert-dev.js') }}"></script>
	<script src="{{ asset('/js/facebook-sdk.js') }}"></script>

	@yield('scripts.footer')
	@yield('footer')
	@include('flash')
</body>
</html>
