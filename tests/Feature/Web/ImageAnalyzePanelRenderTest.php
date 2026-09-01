<?php

namespace Tests\Feature\Web;

use App\Models\User;
use App\Models\UserStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The panel and its JS are shared across three create pages, so assert each
 * one is wired to the right context. There is no JS harness in this repo, so
 * this is the coverage the front end gets.
 */
class ImageAnalyzePanelRenderTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function activeUser(): User
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
            'user_status_id' => UserStatus::ACTIVE,
        ]);

        return $user;
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function createPageProvider(): array
    {
        return [
            'event' => ['/events/create', 'event'],
            'entity' => ['/entities/create', 'entity'],
            'series' => ['/series/create', 'series'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('createPageProvider')]
    public function test_the_create_page_renders_the_panel_for_its_context(string $url, string $context): void
    {
        $response = $this->actingAs($this->activeUser())->get($url);

        $response->assertOk()
            ->assertSee('id="image-analyze-panel"', false)
            ->assertSee('data-context="' . $context . '"', false)
            ->assertSee('name="image_temp_token"', false)
            ->assertSee('js/image-analyze.js', false);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('createPageProvider')]
    public function test_the_create_page_repopulates_the_token_after_a_validation_bounce(string $url): void
    {
        $response = $this->actingAs($this->activeUser())
            ->withSession(['_old_input' => ['image_temp_token' => 'kept-token']])
            ->get($url);

        $response->assertOk()
            ->assertSee('value="kept-token"', false)
            ->assertSee('data-has-stashed="1"', false);
    }
}
