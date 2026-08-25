<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Third Party Services
	|--------------------------------------------------------------------------
	|
	| This file is for storing the credentials for third party services such
	| as Stripe, Mailgun, Mandrill, and others. This file provides a sane
	| default location for this type of information, allowing packages
	| to have a conventional place to find your various credentials.
	|
	*/

	'mailgun' => [
		'domain' => env('MAIL_DOMAIN', 'localhost'),
		'secret' => env('MAIL_SECRET', 'secret'),
	],

	'ses' => [
		'key' => env('AWS_ACCESS_KEY_ID'),
		'secret' => env('AWS_SECRET_ACCESS_KEY'),

		// Deliberately no fallback region. The verified identity lives in
		// exactly one region; defaulting to a different one sends there and
		// fails with "Email address is not verified", which reads as a
		// verification problem rather than a region problem. Unset fails loudly.
		'region' => env('AWS_DEFAULT_REGION'),

		// Merged into the SES v2 SendEmail call. array_filter drops the key
		// when the variable is unset — SES rejects a null ConfigurationSetName.
		'options' => array_filter([
			'ConfigurationSetName' => env('SES_CONFIGURATION_SET'),
		]),
	],

	'stripe' => [
		'model'  => 'App\Models\User',
		'key' => '',
		'secret' => '',
	],

    'facebook' => [
        'client_id'  => env('FACEBOOK_APP_ID', true),
        'client_secret' => env('FACEBOOK_APP_SECRET', true),'',
        'redirect' => env('APP_URL', true).'callback',
    ],

    'twitter' => [
        'consumer_key'    => env('TWITTER_CONSUMER_KEY'),
        'consumer_secret' => env('TWITTER_CONSUMER_SECRET'),
        'access_token'    => env('TWITTER_ACCESS_TOKEN'),
        'access_secret'   => env('TWITTER_ACCESS_SECRET')
    ],
];
