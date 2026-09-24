<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Databases that tests must never touch: RefreshDatabase runs migrate:fresh.
     */
    private static array $protectedDatabases = ['dev_events_tracker', 'events_tracker'];

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        self::assertSafeTestDatabase($app);

        return $app;
    }

    /**
     * Abort the whole run before RefreshDatabase can wipe a real database (#2193).
     *
     * A cached config (bootstrap/cache/config.php) ignores phpunit.xml and
     * .env.testing, so tests would boot with APP_ENV=local and the dev DB.
     * That wiped dev_events_tracker on 2026-08-02 and 2026-09-24.
     */
    private static function assertSafeTestDatabase(Application $app): void
    {
        $connection = $app['config']->get('database.default');
        $database = (string) $app['config']->get("database.connections.{$connection}.database");

        $problem = match (true) {
            $app->configurationIsCached() => 'the config is cached (bootstrap/cache/config.php), so phpunit.xml and .env.testing are ignored',
            !$app->environment('testing') => "APP_ENV is '{$app->environment()}', not 'testing'",
            in_array($database, self::$protectedDatabases, true) => "database '{$database}' is a live database",
            default => null,
        };

        if ($problem === null) {
            return;
        }

        fwrite(STDERR, PHP_EOL."Refusing to run tests against database '{$database}': {$problem}.".PHP_EOL
            .'Run `php artisan config:clear` and try again.'.PHP_EOL);

        exit(1);
    }
}
