<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Every controller route must point at a method that exists; a dangling action
 * only shows up as a 500 when someone hits the URL (#2176).
 */
class RoutesResolveTest extends TestCase
{
    public function test_every_controller_route_points_at_an_existing_method(): void
    {
        $missing = [];

        foreach (Route::getRoutes() as $route) {
            $uses = $route->getAction('uses');

            if (!is_string($uses)) {
                continue; // closures
            }

            [$class, $method] = str_contains($uses, '@') ? explode('@', $uses, 2) : [$uses, '__invoke'];
            $class = ltrim($class, '\\');

            if (!class_exists($class) || !method_exists($class, $method)) {
                $missing[] = implode('|', $route->methods()).' '.$route->uri().' -> '.$uses;
            }
        }

        $this->assertSame([], $missing, "Routes pointing at missing controller methods:\n".implode("\n", $missing));
    }

    public function test_legacy_all_urls_redirect_to_their_index(): void
    {
        foreach (['menus', 'permissions', 'entity-types', 'groups'] as $name) {
            $this->get('/'.$name.'/all')->assertStatus(301)->assertRedirect('/'.$name);
        }
    }
}
