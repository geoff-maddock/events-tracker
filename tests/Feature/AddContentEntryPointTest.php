<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every page has a "+" menu for adding events, series and entities, and a guest
 * who follows it is asked to sign in with a reason and brought back to the
 * form afterwards (#2108).
 */
class AddContentEntryPointTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_the_navigation_links_to_every_create_form(): void
    {
        $html = $this->get('/events')->assertOk()->getContent();

        // desktop sidebar and mobile topbar
        $this->assertSame(2, substr_count($html, 'aria-label="Add something"'));
        foreach (['events.create', 'series.create', 'entities.create'] as $route) {
            $this->assertGreaterThanOrEqual(2, substr_count($html, 'href="'.route($route).'"'), $route);
        }
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function createRoutes(): array
    {
        return [
            'event' => ['events.create', 'Sign in to add an event.'],
            'series' => ['series.create', 'Sign in to add a series.'],
            'entity' => ['entities.create', 'Sign in to add an entity.'],
        ];
    }

    #[DataProvider('createRoutes')]
    public function test_a_guest_is_sent_to_sign_in_with_a_reason(string $route, string $message): void
    {
        $this->get(route($route))
            ->assertRedirect(route('login'))
            ->assertSessionHas('sign_in_reason', $message);

        $this->get(route('login'))->assertOk()->assertSee($message);
    }

    #[DataProvider('createRoutes')]
    public function test_signing_in_returns_the_guest_to_the_form(string $route): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE, 'password' => bcrypt('secret-password')]);

        $this->get(route($route));

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-password'])
            ->assertRedirect(route($route));
    }

    public function test_other_sign_in_redirects_give_no_reason(): void
    {
        $this->get('/events/attending')->assertRedirect(route('login'))->assertSessionMissing('sign_in_reason');
    }

    public function test_an_unverified_user_still_gets_the_verify_notice(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE, 'email_verified_at' => null]);

        $this->actingAs($user)->get(route('events.create'))->assertRedirect(route('verification.notice'));
    }
}
