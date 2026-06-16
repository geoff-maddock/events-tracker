<?php

namespace Tests\Feature;

use App\Models\Thread;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Page-load smoke tests for the Tailwind (-tw) index/show pages whose filter
 * bars were migrated off the laravelcollective/html Form:: helpers to native
 * Blade + <x-ui.select-field>. These pages had no automated coverage, so this
 * guards against Blade-compile / missing-helper / undefined-variable
 * regressions (e.g. removing a global helper like link_to_route) by asserting
 * each page renders without erroring.
 */
class TwPageRenderSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        // The admin group bypasses gates (show_forum/show_thread) and the
        // auth-only pages, so every -tw page is reachable in one pass.
        $this->user->assignGroup('admin');
        $this->actingAs($this->user);
    }

    /**
     * @dataProvider twPages
     */
    public function test_tw_page_renders(string $url): void
    {
        $this->get($url)->assertOk();
    }

    public static function twPages(): array
    {
        return [
            'events index'    => ['/events'],
            'events grid'     => ['/events/grid'],
            'events calendar' => ['/calendar'],
            'entities index'  => ['/entities'],
            'photos index'    => ['/photos'],
            'posts index'     => ['/posts'],
            'reviews index'   => ['/reviews'],
            'series index'    => ['/series'],
            'activity index'  => ['/activity'],
            'threads index'   => ['/threads'],
            'users index'     => ['/users'],
        ];
    }

    public function test_thread_show_page_renders(): void
    {
        $thread = Thread::factory()->create();

        $this->get('/threads/' . $thread->id)->assertOk();
    }
}
