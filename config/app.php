<?php

return [
    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost/'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => 'EST',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY', 'SomeRandomString'),
    'cipher' => 'AES-256-CBC',

    /*
    |------------------
    | Feedback Email
    |------------------
    |
    | This is the email that will be used for sending application feedback
    |
    */
    'feedback' => env('APP_FEEDBACK_EMAIL', 'admin@localhost'),
    'admin' => env('APP_ADMIN_EMAIL', 'admin@localhost'),
    'noreplyemail' => env('APP_NOREPLY_EMAIL', 'noreply@localhost'),
    'superuser' => env('APP_SUPERUSER'),

    /*
    |--------------------------------------------------------------------------
    | Custom
    |--------------------------------------------------------------------------
    |
    | Here is where to add unique custom configs for this app
    |
    */

    'fb_app_id' => env('APP_FB_APP_ID'),
    'fb_app_secret' => env('FACEBOOK_APP_SECRET'),
    'fb_graph_version' => env('FACEBOOK_GRAPH_VERSION'),
    'fb_default_access_token' => env('FACEBOOK_DEFAULT_ACCESS_TOKEN'),

    'facebook_graph_api' => env('FACEBOOK_GRAPH_API'),
    'facebook_system_user_access_token' => env('FACEBOOK_SYSTEM_USER_ACCESS_TOKEN'),
    'facebook_system_page_access_token' => env('FACEBOOK_SYSTEM_PAGE_ACCESS_TOKEN'),
    'facebook_page_id' => env('FACEBOOK_PAGE_ID'),
    'facebook_ig_user_id' => env('FACEBOOK_IG_USER_ID'),

    'spider_blacklist' => env('SPIDER_BLACKLIST'),

    'app_name' => env('APP_NAME', 'Event Repo'),
    'name' => env('APP_NAME', 'Event Repo'),
    'tagline' => env('APP_TAGLINE', 'Event Repository'),
    'default_theme' => 'dark-theme',
    'twitter_consumer_key' => env('TWITTER_CONSUMER_KEY'),
    'default_hashtag' => env('APP_DEFAULT_HASHTAG', ''),
    'analytics' => env('GOOGLE_ANALYTICS', ''),
    'google_tags' => env('GOOGLE_TAGS', ''),

    'social_facebook' => env('SOCIAL_FACEBOOK', ''),
    'social_instagram' => env('SOCIAL_INSTAGRAM', ''),
    'social_twitter' => env('SOCIAL_TWITTER', ''),
    'social_github' => env('SOCIAL_GITHUB', ''),

    'password_reset_secret' => env('PASSWORD_RESET_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => [
        /*
         * Laravel Framework Service Providers...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        // lavelcollective forms
        Collective\Html\HtmlServiceProvider::class,

        // HTML5 forms
        //'Braunson\LaravelHTML5Forms\LaravelHTML5FormsServiceProvider',

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\BroadcastServiceProvider::class,
        // replacement for the bus service provider
        // \AltThree\Bus\BusServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
        \App\Providers\ViewComposerServiceProvider::class,

        // for social logins - fb, google, github
        Laravel\Socialite\SocialiteServiceProvider::class,

        // for image processing, thumbnails
        Intervention\Image\ImageServiceProvider::class,

        // laravel tinker provider
        Laravel\Tinker\TinkerServiceProvider::class,

        NotificationChannels\Twitter\TwitterServiceProvider::class,

        // facebook service
        // App\Providers\FacebookServiceProvider::class
    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => [
        'App' => Illuminate\Support\Facades\App::class,
        'Arr' => Illuminate\Support\Arr::class,
        'Artisan' => Illuminate\Support\Facades\Artisan::class,
        'Auth' => Illuminate\Support\Facades\Auth::class,
        'Blade' => Illuminate\Support\Facades\Blade::class,
        'Broadcast' => Illuminate\Support\Facades\Broadcast::class,
        'Bus' => Illuminate\Support\Facades\Bus::class,
        'Cache' => Illuminate\Support\Facades\Cache::class,
        'Config' => Illuminate\Support\Facades\Config::class,
        'Cookie' => Illuminate\Support\Facades\Cookie::class,
        'Crypt' => Illuminate\Support\Facades\Crypt::class,
        'DB' => Illuminate\Support\Facades\DB::class,
        'Eloquent' => Illuminate\Database\Eloquent\Model::class,
        'Event' => Illuminate\Support\Facades\Event::class,
        'File' => Illuminate\Support\Facades\File::class,
        // 'Gate' => Illuminate\Support\Facades\Gate::class,
        'Hash' => Illuminate\Support\Facades\Hash::class,
        'Lang' => Illuminate\Support\Facades\Lang::class,
        'Log' => Illuminate\Support\Facades\Log::class,
        'Mail' => Illuminate\Support\Facades\Mail::class,
        'Notification' => Illuminate\Support\Facades\Notification::class,
        'Password' => Illuminate\Support\Facades\Password::class,
        'Queue' => Illuminate\Support\Facades\Queue::class,
        'Redirect' => Illuminate\Support\Facades\Redirect::class,
        'Redis' => Illuminate\Support\Facades\Redis::class,
        'Request' => Illuminate\Support\Facades\Request::class,
        'Response' => Illuminate\Support\Facades\Response::class,
        'Route' => Illuminate\Support\Facades\Route::class,
        'Schema' => Illuminate\Support\Facades\Schema::class,
        'Session' => Illuminate\Support\Facades\Session::class,
        'Storage' => Illuminate\Support\Facades\Storage::class,
        'Str' => Illuminate\Support\Str::class,
        'URL' => Illuminate\Support\Facades\URL::class,
        'Validator' => Illuminate\Support\Facades\Validator::class,
        'View' => Illuminate\Support\Facades\View::class,
        'Socialite' => Laravel\Socialite\Facades\Socialite::class,
        'Image' => \Intervention\Image\Facades\Image::class,
        // 'Calendar' => \MaddHatter\LaravelFullcalendar\Facades\Calendar::class,
        'Form' => Collective\Html\FormFacade::class,
        'Html' => Collective\Html\HtmlFacade::class,
    ],
];
