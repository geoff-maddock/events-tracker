<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\Event;
use App\Models\Forum;
use App\Models\Group;
use App\Models\Menu;
use App\Models\Thread;
use App\Models\ThreadCategory;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Thread and blog writes save validated input only, and a thread's lock is
 * changed only through lock/unlock by someone who may edit it (#2180).
 */
class ThreadBlogValidatedWritesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
    }

    private function ownedThread(): Thread
    {
        $this->actingAs($this->owner);

        return Thread::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);
    }

    /**
     * @return array<string, mixed>
     */
    private function threadPayload(): array
    {
        return [
            'name' => 'ZZ Validated Thread',
            'body' => 'A ZZ body',
            'description' => 'A ZZ description',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'forum_id' => Forum::factory()->create()->id,
            'thread_category_id' => ThreadCategory::factory()->create()->id,
            'event_id' => Event::factory()->create()->id,
        ];
    }

    public function test_creating_a_thread_saves_the_form_fields_but_not_server_controlled_ones(): void
    {
        $payload = $this->threadPayload();

        $this->actingAs($this->owner)
            ->post('/threads', $payload + ['views' => 9999, 'locked_by' => $this->owner->id, 'locked_at' => '2026-01-01 00:00:00'])
            ->assertSessionHasNoErrors();

        $thread = Thread::where('name', 'ZZ Validated Thread')->sole();
        $this->assertSame('A ZZ description', $thread->description);
        $this->assertSame($payload['event_id'], (int) $thread->event_id);
        $this->assertSame($payload['thread_category_id'], (int) $thread->thread_category_id);
        $this->assertSame(0, (int) $thread->views);
        $this->assertNull($thread->locked_at);
    }

    public function test_thread_fields_are_validated(): void
    {
        $thread = $this->ownedThread();

        $this->put("/threads/{$thread->id}", ['event_id' => 999999, 'thread_category_id' => 999999] + $this->threadPayload())
            ->assertSessionHasErrors(['event_id', 'thread_category_id']);
    }

    public function test_only_someone_who_may_edit_a_thread_can_lock_or_unlock_it(): void
    {
        $thread = $this->ownedThread();
        $stranger = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($stranger)->post("/threads/{$thread->id}/lock")->assertForbidden();
        $this->assertNull($thread->fresh()->locked_at);

        $this->actingAs($this->owner)->post("/threads/{$thread->id}/lock")->assertRedirect();
        $this->assertNotNull($thread->fresh()->locked_at);

        $this->actingAs($stranger)->post("/threads/{$thread->id}/unlock")->assertForbidden();
        $this->assertNotNull($thread->fresh()->locked_at);

        auth()->logout();
        $this->post("/threads/{$thread->id}/unlock")->assertRedirect('/login');
        $this->assertNotNull($thread->fresh()->locked_at);
    }

    public function test_an_api_put_does_not_unlock_a_locked_thread(): void
    {
        $thread = $this->ownedThread();
        $admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $admin->groups()->attach(Group::firstOrCreate(['name' => 'admin'])->id);
        $thread->forceFill(['locked_at' => now(), 'locked_by' => $admin->id])->save();

        $this->actingAs($this->owner, 'sanctum')
            ->putJson("/api/threads/{$thread->id}", [
                'name' => 'ZZ Renamed', 'body' => 'A new ZZ body', 'visibility_id' => Visibility::VISIBILITY_PUBLIC, 'forum_id' => $thread->forum_id,
            ])
            ->assertOk();

        $this->assertSame('ZZ Renamed', $thread->fresh()->name);
        $this->assertNotNull($thread->fresh()->locked_at);
        $this->assertSame($admin->id, (int) $thread->fresh()->locked_by);
    }

    public function test_blog_writes_save_menu_and_sort_order_and_validate_them(): void
    {
        $admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $admin->groups()->attach(Group::firstOrCreate(['name' => 'admin'])->id);
        $menu = Menu::factory()->create();
        $payload = [
            'name' => 'ZZ Validated Blog', 'slug' => 'zz-validated-blog', 'body' => 'A ZZ blog body',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC, 'content_type_id' => 1,
            'menu_id' => $menu->id, 'sort_order' => 3,
        ];

        $this->actingAs($admin)->post('/blogs', $payload)->assertSessionHasNoErrors();
        $blog = Blog::where('slug', 'zz-validated-blog')->sole();
        $this->assertSame($menu->id, (int) $blog->menu_id);
        $this->assertSame(3, (int) $blog->sort_order);

        $this->actingAs($admin)->put("/blogs/{$blog->slug}", ['menu_id' => 999999, 'sort_order' => 'first'] + $payload)
            ->assertSessionHasErrors(['menu_id', 'sort_order']);
    }

    public function test_no_controller_passes_raw_request_input_to_a_model_write(): void
    {
        $files = array_merge(glob(app_path('Http/Controllers/*.php')), glob(app_path('Http/Controllers/Api/*.php')));

        foreach ($files as $file) {
            $this->assertDoesNotMatchRegularExpression(
                '/(create|fill|update)\(\$request->(all|input)\(\)\)|\$input = \$request->(all|input)\(\);/',
                file_get_contents($file),
                basename($file).' passes raw request input to a model write'
            );
        }
    }
}
