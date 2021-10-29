@extends('app')

@section('content')

<h1 class="display-6 text-primary">Privacy</h1>


	<ol>
		<li>General Policy</li>
		
			<ul>
			<li>We respect your privacy and strive to keep your data safe and secure. </li>
			<li>We don’t use your data for any purpose other than your use and interaction with this site.</li>
			<li>We don’t share your data with any 3rd parties.  Sensitive data such as passwords are encrypted and not available to users or administrators.</li>
			<li>We display no advertisements and share no data with advertisers.</li>
			</ul>
		<li>Types and Purpose of Collected Information</li>
			<ul>
				<li><b>Personal information.</b>  Your name, email address, bio, event data and and other information you provide when signing up or sharing event data via a form.  This data is collected to identify you as a user of the app to admistrators as well as other site users.</li>
				<li><b>Facebook Data</b> When you auth using the FB integration, we collect the account ID for future authentications. When you grant access to your attended events, and choose to import that data, we store public data related to FB events you are attending.  This data is collected to allow you to log in more easily as well as share your event information more easily.</li>
				<li><b>Settings and Acocunt information.</b>  We store data such as your notification preferences, time zone, theme choice and other settings data you submit while using the site.  This data is collected to improve the overall user experience and retain your preferences.</li>
			</ul>
		<li>How you can request deletion of data</li>
			<ul>
				<li>You can request the deletion of any or all of your data by emailing the administrator at <a href="mailto:{{ Config::get('app.admin'); }}">{{ Config::get('app.admin'); }}</a> and they will follow up within two business days.</li>
			</ul>
	</ol>

	Direct any other questions to <b>{{ Config::get('app.admin'); }}</b>

@stop

@section('footer')

@stop
