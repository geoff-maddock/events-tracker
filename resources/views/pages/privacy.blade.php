@extends('app')

@section('content')

<h1 class="display-6 text-primary">Privacy</h1>


	<ul>
		<li>We respect your privacy. We won’t use your contact for any purpose other than direct interaction via this site.</li>

		<li>We won’t share your personal data with any 3rd parties.</li>

		<li>We display no advertisements.</li>
	</ul>

	Direct any questions to <b>{{ Config::get('app.admin'); }}</b>

@stop

@section('footer')

@stop
