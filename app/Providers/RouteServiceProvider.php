<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        // These route parameters are always numeric ids, and the actions type
        // them int. Without a pattern a non-numeric value (a scanner's
        // "8715 AND ...") reached the int parameter and threw a TypeError, a
        // 500; now it doesn't match the route, a 404 (EVENTREPO-YR). Set here,
        // before the route files load, so every route using these names gets it.
        Route::patterns([
            'id' => '[0-9]+',
            'contactId' => '[0-9]+',
            'linkId' => '[0-9]+',
            'locationId' => '[0-9]+',
            'threadId' => '[0-9]+',
            'age' => '[0-9]+',
        ]);

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapWebRoutes();
        $this->mapApiRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::group([
            'middleware' => 'web',  // changing this from middleware to middlewareGroups worked
        ], function ($router) {
            require base_path('routes/web.php');
        });
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::group([
            'middleware' => 'api',
            'prefix' => 'api',
        ], function ($router) {
            require base_path('routes/api.php');
        });
    }

    /**
     * Configure the rate limiters for the application
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        // keyed per user (or per IP for anonymous callers) so one busy client
        // can't exhaust the budget for everyone. AuthenticateEither sorts ahead
        // of the throttle, so basic auth users resolve here; token requests
        // need the sanctum guard asked explicitly.
        RateLimiter::for('api', function (Request $request) {
            $user = $request->user() ?? $request->user('sanctum');

            return $user
                ? Limit::perMinute(240)->by('user:' . $user->id)
                : Limit::perMinute(120)->by('ip:' . $request->ip());
        });

        // each photos/from-url call makes an outbound request on the caller's
        // behalf, so it gets a much tighter per-user budget than the rest of the API
        // posting threads, posts, comments and reviews (#2166)
        RateLimiter::for('content-writes', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('photo-from-url', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });
    }
}
