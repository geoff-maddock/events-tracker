<?php

namespace Tests\Feature;

use App\Models\Thread;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Every controller route must point at a method that exists; a dangling action
 * only shows up as a 500 when someone hits the URL (#2176).
 */
class RoutesResolveTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

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

    public function test_api_thread_show_returns_visible_threads_only(): void
    {
        // show_thread is only granted through groups (admins pass Gate::before), same as the index
        $author = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $author->assignGroup('admin');
        $viewer = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $viewer->assignGroup('admin');
        $author = $author->fresh();
        $viewer = $viewer->fresh();
        $public = Thread::factory()->create(['created_by' => $author->id, 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        $private = Thread::factory()->create(['visibility_id' => Visibility::VISIBILITY_PRIVATE]);
        // Thread's creating hook stamps the signed-in user (or 1), so set the owner afterwards
        $private->forceFill(['created_by' => $author->id])->saveQuietly();

        $this->actingAs($viewer, 'sanctum')->getJson('/api/threads/'.$public->id)
            ->assertOk()->assertJsonFragment(['id' => $public->id]);
        $this->actingAs($viewer, 'sanctum')->getJson('/api/threads/'.$private->id)->assertNotFound();
        $this->actingAs($author, 'sanctum')->getJson('/api/threads/'.$private->id)->assertOk();
    }
}
