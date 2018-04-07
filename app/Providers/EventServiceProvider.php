<?php namespace App\Providers;

use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider {

	/**
	 * The event handler mappings for the application.
	 *
	 * @var array
	 */
	protected $listen = [
		'event.name' => [
			'EventListener',
		],
        'Illuminate\Routing\Events\RouteMatched' => [
            'App\Listeners\RouterMatchedListener',
        ],
        'Illuminate\Auth\Events\Login' => [
            'App\Listeners\LogSuccessfulLogin',
        ],
	];

	/**
	 * Register any other events for your application.
	 *
	 * @return void
	 */
    public function boot()
	{
		parent::boot();

	}

}
