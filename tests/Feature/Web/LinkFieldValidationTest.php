<?php

namespace Tests\Feature\Web;

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ticket_link and primary_link on the event and series forms must be
 * absolute http(s) URLs.
 *
 * The rule used to be regex:/^http:\/\/|https:\/\/|^$/ — the middle branch
 * is unanchored, so "Doors at 8 https://x" passed and only the leading
 * "http://" form was actually checked. Search Console reports every
 * non-URL that reached offers.url as an invalid item.
 */
class LinkFieldValidationTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function rejectedLinks(): array
    {
        return [
            'bare domain' => ['Ticketfly.com'],
            'prose' => ['Admission: Free'],
            'prose with a url inside' => ['Doors at 8 https://tickets.example/x'],
            'missing scheme letter' => ['ttps://tickets.example/x'],
            'mailto' => ['mailto:booking@example.test'],
        ];
    }

    /**
     * @dataProvider rejectedLinks
     */
    public function test_event_form_rejects_a_link_that_is_not_an_absolute_url(string $link): void
    {
        $this->actingAs(User::factory()->create(['user_status_id' => UserStatus::ACTIVE]))
            ->post('/events', ['ticket_link' => $link, 'primary_link' => $link])
            ->assertSessionHasErrors(['ticket_link', 'primary_link']);
    }

    /**
     * @dataProvider rejectedLinks
     */
    public function test_series_form_rejects_a_link_that_is_not_an_absolute_url(string $link): void
    {
        $this->actingAs(User::factory()->create(['user_status_id' => UserStatus::ACTIVE]))
            ->post('/series', ['ticket_link' => $link, 'primary_link' => $link])
            ->assertSessionHasErrors(['ticket_link', 'primary_link']);
    }

    public function test_valid_and_blank_links_pass_the_link_rules(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($user)
            ->post('/events', ['ticket_link' => 'https://tickets.example/x', 'primary_link' => 'http://example.test/x'])
            ->assertSessionDoesntHaveErrors(['ticket_link', 'primary_link']);

        $this->actingAs($user)
            ->post('/events', ['ticket_link' => '', 'primary_link' => ''])
            ->assertSessionDoesntHaveErrors(['ticket_link', 'primary_link']);
    }
}
