<?php

namespace App\Providers;

use App\Listeners\LogSuccessfulLogin;
use App\Listeners\RouterMatchedListener;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Routing\Events\RouteMatched;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        // 'event.name' => [
        //     'EventListener',
        // ],
        RouteMatched::class => [
            RouterMatchedListener::class,
        ],
        Login::class => [
            LogSuccessfulLogin::class,
        ],
        Registered::class => [
            SendEmailVerificationNotification::class,
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
