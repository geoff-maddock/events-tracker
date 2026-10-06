<?php

namespace Tests\Feature;

use App\Models\DiscordTarget;
use App\Models\Event;
use App\Models\Profile;
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

    public function test_the_delete_form_helper_asks_for_confirmation(): void
    {
        $html = delete_form(['blogs.destroy', 'some-blog']);

        $this->assertStringContainsString('data-confirm="You will not be able to recover this blog!"', $html);
        $this->assertStringNotContainsString('delete confirm', $html);
    }

    public function test_the_icon_form_helper_asks_for_confirmation_only_when_told_to(): void
    {
        $event = Event::factory()->create();

        $delete = link_form_bootstrap_icon('bi bi-trash', $event, 'DELETE', 'Delete the event');
        $this->assertStringContainsString('data-confirm="You will not be able to recover this event!"', $delete);

        // a "delete" class without the confirm argument still confirms
        $byClass = link_form_bootstrap_icon('bi bi-trash', $event, 'DELETE', 'Delete', null, 'delete', '');
        $this->assertStringContainsString('data-confirm="You will not be able to recover this event!"', $byClass);

        $post = link_form_bootstrap_icon('bi bi-star', '/photos/1/set-primary', 'POST', 'Set as primary photo');
        $this->assertStringContainsString('data-confirm="" data-confirm-button="Confirm"', $post);

        $plain = link_form_bootstrap_icon('bi bi-star', '/photos/1/set-primary', 'POST', 'Set as primary photo', '', '', '');
        $this->assertStringNotContainsString('data-confirm', $plain);
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
