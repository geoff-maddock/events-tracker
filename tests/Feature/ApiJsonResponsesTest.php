<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Group;
use App\Models\Link;
use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * API endpoints answer with JSON and status codes, never views, redirects,
 * flash messages or session-held list state (#2179).
 */
class ApiJsonResponsesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $admin->groups()->attach(Group::firstOrCreate(['name' => 'admin'])->id);

        return $admin;
    }

    public function test_api_controllers_do_not_render_views_redirect_or_touch_the_session(): void
    {
        foreach (glob(app_path('Http/Controllers/Api/*.php')) as $file) {
            $source = file_get_contents($file);
            foreach (['view(', 'redirect(', 'session(', 'Session::', 'back()', 'flash('] as $needle) {
                $this->assertStringNotContainsString($needle, $source, basename($file)." calls {$needle}");
            }
        }
    }

    public function test_session_list_state_routes_are_gone_from_the_api(): void
    {
        $uris = collect(Route::getRoutes()->getRoutes())->map->uri();

        $this->assertEmpty($uris->filter(fn ($uri) => str_starts_with($uri, 'api/') && preg_match('#/(rpp-)?reset$#', $uri)));
    }

    public function test_instagram_web_actions_are_served_by_a_web_controller(): void
    {
        $this->assertSame(
            'App\Http\Controllers\EventInstagramController@postToInstagram',
            Route::getRoutes()->getByName('events.instagramPostSingle')->getActionName()
        );
        $this->assertSame(
            'App\Http\Controllers\Api\EventInstagramController@postCarouselToInstagramApi',
            Route::getRoutes()->match(request()->create('/api/events/1/instagram-post', 'POST'))->getActionName()
        );
    }

    public function test_link_show_returns_the_link_as_json(): void
    {
        $link = Link::factory()->create(['url' => 'https://example.com/zzlink']);

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/links/{$link->id}")
            ->assertOk()
            ->assertJsonPath('id', $link->id)
            ->assertJsonPath('url', 'https://example.com/zzlink');
    }

    public function test_post_store_adds_the_post_to_the_given_thread(): void
    {
        $thread = Thread::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/posts', ['thread_id' => $thread->id, 'body' => 'An API reply ZZ'])
            ->assertStatus(201)
            ->assertJsonPath('body', 'An API reply ZZ');

        $post = Post::where('body', 'An API reply ZZ')->sole();
        $this->assertSame($thread->id, (int) $post->thread_id);
        $this->assertSame($this->user->id, (int) $post->created_by);
    }

    public function test_post_store_requires_a_thread_and_a_body(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/posts', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['thread_id', 'body']);
    }

    public function test_post_show_returns_json(): void
    {
        $post = Post::factory()->create(['body' => 'Shown post ZZ']);

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson("/api/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('id', $post->id);
    }

    public function test_post_destroy_answers_403_json_for_a_non_owner_and_204_for_the_owner(): void
    {
        $post = Post::factory()->create(['created_by' => User::factory()->create()->id]);

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/posts/{$post->id}")
            ->assertStatus(403)
            ->assertJsonStructure(['message']);
        $this->assertNotNull(Post::find($post->id));

        $own = Post::factory()->create(['created_by' => $this->user->id]);
        $this->deleteJson("/api/posts/{$own->id}")->assertNoContent();
        $this->assertNull(Post::find($own->id));
    }

    public function test_thread_destroy_answers_204(): void
    {
        $this->actingAs($this->user, 'sanctum');
        $thread = Thread::factory()->create();

        $this->deleteJson("/api/threads/{$thread->id}")->assertNoContent();
        $this->assertNull(Thread::find($thread->id));
    }

    public function test_unauthorized_update_answers_403_json_not_a_redirect(): void
    {
        $entity = Entity::factory()->create(['created_by' => User::factory()->create()->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/entities/{$entity->slug}", ['name' => 'Hijacked ZZ']);

        $response->assertStatus(403)->assertJsonStructure(['message']);
    }

    public function test_filter_endpoints_return_json(): void
    {
        $this->actingAs($this->admin(), 'sanctum');

        $this->getJson('/api/posts/filter')->assertOk()->assertJsonStructure(['data']);
        $this->getJson('/api/activities/filter')->assertOk()->assertJsonStructure(['data']);
    }
}
