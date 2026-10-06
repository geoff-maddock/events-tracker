<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\DiscordTarget;
use App\Models\Event;
use App\Models\Post;
use App\Models\Profile;
use App\Models\Thread;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every confirmation goes through the one data-confirm handler in
 * resources/assets/js/bootstrap.js (#2269). The jQuery handlers that hooked onto
 * class="delete" / class="confirm" are gone, so markup still using those classes
 * would act without asking.
 */
class ConfirmMarkupTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_no_view_or_helper_relies_on_the_removed_confirm_classes(): void
    {
        $files = [app_path('Http/helpers.php')];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'))) as $file) {
            if (str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }
        $this->assertGreaterThan(100, count($files));

        $offenders = [];
        foreach ($files as $file) {
            preg_match_all('/class\s*=\s*(["\'])(.*?)\1/s', (string) file_get_contents($file), $matches);
            foreach ($matches[2] as $classes) {
                $tokens = preg_split('/\s+/', $classes) ?: [];
                if (array_intersect(['delete', 'confirm'], $tokens) !== []) {
                    $offenders[] = str_replace(base_path().'/', '', $file).': class="'.trim($classes).'"';
                }
            }
        }

        $this->assertSame([], $offenders, 'Use data-confirm instead of the "delete" / "confirm" classes');
    }

    public function test_thread_post_and_blog_deletes_confirm(): void
    {
        $admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $admin->assignGroup('admin');
        // thread and post deletes show to a recent owner or a super admin
        $admin->assignGroup('super_admin');
        $admin = $admin->fresh();
        $thread = Thread::factory()->create(['created_by' => $admin->id, 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        Post::factory()->create(['thread_id' => $thread->id, 'created_by' => $admin->id, 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        $blog = Blog::factory()->create(['created_by' => $admin->id, 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);

        $this->actingAs($admin)->get("/threads/{$thread->id}")->assertOk()
            ->assertSee('data-confirm="You will not be able to recover this thread!"', false)
            ->assertSee('data-confirm="You will not be able to recover this post!"', false);
        $this->actingAs($admin)->get('/posts')->assertOk()
            ->assertSee('data-confirm="You will not be able to recover this post!"', false);
        $this->actingAs($admin)->get("/blogs/{$blog->slug}")->assertOk()
            ->assertSee('data-confirm="You will not be able to recover this blog!"', false);
    }

    public function test_the_event_delete_form_confirms(): void
    {
        $owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $event = Event::factory()->create(['created_by' => $owner->id, 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);

        $this->actingAs($owner)->get("/events/{$event->slug}")->assertOk()
            ->assertSee('class="block" data-confirm="You will not be able to recover this event!"', false);
    }

    public function test_user_page_actions_confirm(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        Profile::factory()->create(['user_id' => $user->id]);

        $html = $this->actingAs($user)->get("/users/{$user->id}")->assertOk()->getContent();

        $this->assertStringContainsString('data-confirm="This will generate a ZIP file with all your data and email you a download link." data-confirm-button="Continue"', $html);
        // a state-changing link: confirmed, then POSTed
        $this->assertMatchesRegularExpression('#<a data-confirm="" data-confirm-button="Confirm" data-method="post" href="[^"]*/users/'.$user->id.'/weekly"#', $html);
    }

    public function test_admin_tools_and_discord_target_deletes_confirm(): void
    {
        $admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $admin->assignGroup('admin');
        $admin = $admin->fresh();

        $this->actingAs($admin)->get('/tools')->assertOk()
            ->assertSee('data-confirm="Unverified users will be permanently removed. This action cannot be undone."', false);

        $target = DiscordTarget::factory()->create();
        $this->actingAs($admin)->get(route('discord-targets.edit', ['discordTarget' => $target->id]))->assertOk()
            ->assertSee('data-confirm="Delete the Discord target', false);
    }
}
