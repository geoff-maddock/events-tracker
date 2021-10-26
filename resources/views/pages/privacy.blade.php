@extends('app')

@section('content')

<h1 class="display-6 text-primary">Privacy</h1>


	<ul>
		<li>We respect your privacy.</li>

		<li>We don’t use your data for any purpose other than direct interaction via this site.</li>

		<li>We don’t share your data with any 3rd parties.</li>

		<li>We display no advertisements and share no data with advertisers.</li>
	
		<li>You can request the deletion of any or all of your data by emailing the administrator at <a href="mailto:{{ Config::get('app.admin'); }}">{{ Config::get('app.admin'); }}</a> and they will follow up within two business days.</li>
	</ul>

	Direct any other questions to <b>{{ Config::get('app.admin'); }}</b>

@stop

@section('footer')

@stop
