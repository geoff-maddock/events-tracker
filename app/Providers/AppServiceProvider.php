<?php namespace App\Providers;


use App\Entity;
use App\EventType;
use App\Visibility;
use App\Tag;
use App\Series;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Laravel\Dusk\DuskServiceProvider;


class AppServiceProvider extends ServiceProvider {

	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot()
	{
	    // get the user, set the theme and pass to the view
        \View::composer('app', function($view) {
            if (Auth::check() && Auth::user()->profile->default_theme) {
                $theme = Auth::user()->profile->default_theme;
            } else {
                $theme = config('app.default_theme');
            };
            $view->with('theme', $theme);
        });

        \View::composer('events.edit', function($view) {
            $view->with('venues',[''=>''] + Entity::getVenues()->pluck('name','id')->all());
            $view->with('promoters',[''=>''] + Entity::whereHas('roles', function($q)
                {
                    $q->where('name','=','Promoter');
                })->orderBy('name','ASC')->pluck('name','id')->all());
            $view->with('eventTypes',[''=>''] + EventType::orderBy('name','ASC')->pluck('name', 'id')->all());
            $view->with('visibilities',[''=>''] + Visibility::pluck('name', 'id')->all());
            $view->with('tags',Tag::orderBy('name','ASC')->pluck('name','id')->all());
            $view->with('entities',Entity::orderBy('name','ASC')->pluck('name','id')->all());
            $view->with('seriesList',[''=>''] + Series::orderBy('name', 'ASC')->pluck('name', 'id')->all());
        });
	}

	/**
	 * Register any application services.
	 *
	 * This service provider is a great spot to register your various container
	 * bindings with the application. As you can see, we are registering our
	 * "Registrar" implementation here. You can add your own bindings too!
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bind(
			'Illuminate\Contracts\Auth\Registrar',
			'App\Services\Registrar'
		);

        if ($this->app->environment('local', 'testing')) {
            $this->app->register(DuskServiceProvider::class);
        };
	}

}
