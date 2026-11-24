<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `/api/docs` shows `public/postman/schemas/api.yml`, which is written by hand
 * (#2405). This keeps it level with `routes/api.php`: a new API route fails
 * here until it is in the spec, or in UNDOCUMENTED with the reason.
 */
class OpenApiSpecParityTest extends TestCase
{
    private const SPEC = 'postman/schemas/api.yml';

    /**
     * Resources with a `/filter` route beside their list route. It returns
     * the same list from the same filters, and the spec documents the list.
     */
    private const FILTER_ROUTES = [
        'activities', 'blogs', 'forums', 'posts', 'threads', 'users', 'visibilities',
        'tag-types', 'occurrence-days', 'occurrence-types', 'occurrence-weeks',
    ];

    /**
     * Routes left out of the spec on purpose: operation => why.
     */
    private const UNDOCUMENTED = [
        'GET /api/docs' => 'the documentation page itself',
        'GET /api/email/verify/{}/{}' => 'the signed link in the verification email, not called by a client',
        'GET /api/tag-calendar-events' => 'feeds the site\'s own tag calendar page',
    ];

    #[Test]
    public function every_api_route_is_in_the_spec(): void
    {
        $missing = array_diff($this->routeOperations(), $this->specOperations(), $this->undocumented());

        $this->assertSame([], array_values($missing), 'API routes missing from '.self::SPEC.' (add them, or list them in UNDOCUMENTED with a reason).');
    }

    #[Test]
    public function every_spec_operation_has_a_route(): void
    {
        $orphans = array_diff($this->specOperations(), $this->routeOperations());

        $this->assertSame([], array_values($orphans), 'Operations in '.self::SPEC.' that no route serves.');
    }

    #[Test]
    public function the_undocumented_list_names_only_real_undocumented_routes(): void
    {
        $this->assertSame([], array_values(array_diff($this->undocumented(), $this->routeOperations())), 'UNDOCUMENTED names a route that no longer exists.');
        $this->assertSame([], array_values(array_intersect($this->undocumented(), $this->specOperations())), 'UNDOCUMENTED names an operation the spec now documents.');
    }

    #[Test]
    public function operation_ids_are_unique(): void
    {
        preg_match_all('/^      operationId: (\S+)\s*$/m', $this->spec(), $matches);

        $this->assertNotEmpty($matches[1]);
        $repeated = array_keys(array_filter(array_count_values($matches[1]), fn (int $count) => $count > 1));
        $this->assertSame([], $repeated, 'operationId used more than once.');
    }

    /**
     * @return list<string>
     */
    private function undocumented(): array
    {
        $operations = array_keys(self::UNDOCUMENTED);
        foreach (self::FILTER_ROUTES as $resource) {
            $operations[] = "GET /api/{$resource}/filter";
            $operations[] = "POST /api/{$resource}/filter";
        }

        return $operations;
    }

    /**
     * "METHOD /api/path/{}" for every API route. A route with optional
     * parameters counts once for each length it accepts.
     *
     * @return list<string>
     */
    private function routeOperations(): array
    {
        $operations = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $uri = '/'.ltrim($route->uri(), '/');
            if (!str_starts_with($uri, '/api/')) {
                continue;
            }

            $paths = [];
            while (1 === preg_match('#^(.*)/\{[^}]+\?\}$#', $uri, $optional)) {
                $paths[] = $uri;
                $uri = $optional[1];
            }
            $paths[] = $uri;

            foreach ($paths as $path) {
                foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                    $operations[] = $method.' '.$this->normalise($path);
                }
            }
        }

        return array_values(array_unique($operations));
    }

    /**
     * The spec is read line by line (there is no YAML parser in the app): a
     * path is a key at two spaces under `paths:`, a method a key at four.
     *
     * @return list<string>
     */
    private function specOperations(): array
    {
        $operations = [];
        $inPaths = false;
        $path = null;

        foreach (preg_split('/\R/', $this->spec()) ?: [] as $line) {
            if (1 === preg_match('/^\S/', $line)) {
                $inPaths = str_starts_with($line, 'paths:');
            } elseif (!$inPaths) {
                continue;
            } elseif (1 === preg_match('#^  ["\']?(/[^"\':]*)["\']?:\s*$#', $line, $match)) {
                $path = $match[1];
            } elseif (null !== $path && 1 === preg_match('/^    (get|post|put|patch|delete):\s*$/', $line, $match)) {
                $operations[] = strtoupper($match[1]).' '.$this->normalise($path);
            }
        }

        $this->assertGreaterThan(150, count($operations), 'The spec could not be read.');

        return $operations;
    }

    private function normalise(string $path): string
    {
        return preg_replace('/\{[^}]+\}/', '{}', $path) ?? $path;
    }

    private function spec(): string
    {
        return (string) file_get_contents(public_path(self::SPEC));
    }
}
