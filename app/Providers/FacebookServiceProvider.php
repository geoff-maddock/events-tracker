<?php

namespace App\Providers;

use Facebook\Facebook;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class FacebookServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(Facebook::class, function () {
            return new Facebook([
                'app_id' => $this->app['config']['services.facebook.client_id'],
                'app_secret' => $this->app['config']['services.facebook.client_secret'],
                'default_graph_version' => $this->app['config']['services.facebook.graph_version'],
            ]);
        });
    }

    public function provides()
    {
        return [
            Facebook::class,
        ];
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
