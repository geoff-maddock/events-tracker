<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use ReflectionMethod;
use ReflectionNamedType;
use Tests\TestCase;

/**
 * Route parameters that reach an int-typed controller argument must be
 * constrained to digits. Otherwise a non-numeric value (a scanner's
 * "/events/8715 AND .../load-minimal-embeds") throws a TypeError, a 500
 * (EVENTREPO-YR), instead of not matching the route, a 404. The constraints
 * are global patterns in RouteServiceProvider.
 */
class NumericRouteParameterTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_every_int_route_parameter_is_constrained_to_digits(): void
    {
        $unconstrained = [];

        /** @var Route $route */
        foreach (app('router')->getRoutes()->getRoutes() as $route) {
            $action = $route->getAction('uses');
            if (! is_string($action) || ! str_contains($action, '@')) {
                continue;
            }
            [$class, $method] = explode('@', $action);
            if (! method_exists($class, $method)) {
                continue;
            }

            foreach ((new ReflectionMethod($class, $method))->getParameters() as $parameter) {
                $type = $parameter->getType();
                if (! $type instanceof ReflectionNamedType || ! in_array($type->getName(), ['int', 'float'], true)
                    || ! in_array($parameter->getName(), $route->parameterNames(), true)) {
                    continue;
                }

                if (($route->wheres[$parameter->getName()] ?? null) !== '[0-9]+') {
                    $unconstrained[] = '/'.$route->uri().' {'.$parameter->getName().'}';
                }
            }
        }

        $this->assertSame([], $unconstrained, 'add the parameter name to Route::patterns() in RouteServiceProvider');
    }

    public function test_a_non_numeric_id_is_not_found(): void
    {
        $this->withExceptionHandling();

        foreach (['events', 'entities', 'series'] as $type) {
            foreach (['load-embeds', 'load-minimal-embeds'] as $endpoint) {
                $this->get("/{$type}/8715 AND 1=1/{$endpoint}")->assertNotFound();
            }
        }

        $this->get('/calendar/min-age/abc')->assertNotFound();
        $this->get('/users/abc/ical')->assertNotFound();
        $this->post('/events/abc/attend')->assertNotFound();
        $this->post('/tags/abc/follow')->assertNotFound();
        $this->getJson('/api/threads/abc/posts')->assertNotFound();
    }

    public function test_a_numeric_id_still_reaches_the_controller(): void
    {
        $this->withExceptionHandling();
        $event = Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);

        $this->assertNotSame(404, $this->get("/events/{$event->id}/load-minimal-embeds")->status());
        $this->get('/calendar/min-age/21')->assertOk();
    }
}
