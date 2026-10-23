<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventType;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The age, price and ticket filters on the event lists (#2361).
 */
class EventListAudienceFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function publicEvent(array $attributes): Event
    {
        return Event::factory()->create(array_merge([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'event_type_id' => EventType::query()->value('id'),
            'start_at' => now()->addDays(3),
        ], $attributes));
    }

    public function test_all_ages_filter_lists_only_all_ages_events(): void
    {
        $this->publicEvent(['name' => 'ZZ Matinee Show', 'min_age' => 0]);
        $this->publicEvent(['name' => 'ZZ Bar Show', 'min_age' => 21]);

        $this->post(route('events.filter'), ['filters' => ['age' => 'all_ages']])
            ->assertOk()
            ->assertSee('ZZ Matinee Show')
            ->assertDontSee('ZZ Bar Show')
            ->assertSee('Ages: All Ages');
    }

    public function test_21_plus_filter_lists_only_21_plus_events(): void
    {
        $this->publicEvent(['name' => 'ZZ Matinee Show', 'min_age' => 0]);
        $this->publicEvent(['name' => 'ZZ Bar Show', 'min_age' => 21]);

        $this->post(route('events.filter'), ['filters' => ['age' => '21_plus']])
            ->assertOk()
            ->assertSee('ZZ Bar Show')
            ->assertDontSee('ZZ Matinee Show');
    }

    public function test_free_and_ticket_filters_combine(): void
    {
        $this->publicEvent(['name' => 'ZZ Free Ticketed', 'door_price' => 0, 'ticket_link' => 'https://tickets.example.com/zz']);
        $this->publicEvent(['name' => 'ZZ Free Walk-in', 'door_price' => 0, 'ticket_link' => null]);
        $this->publicEvent(['name' => 'ZZ Paid Ticketed', 'door_price' => 20, 'ticket_link' => 'https://tickets.example.com/zz2']);

        $this->post(route('events.filter'), ['filters' => ['price' => 'free', 'has_tickets' => '1']])
            ->assertOk()
            ->assertSee('ZZ Free Ticketed')
            ->assertDontSee('ZZ Free Walk-in')
            ->assertDontSee('ZZ Paid Ticketed');
    }

    public function test_filter_panels_render_the_new_fields(): void
    {
        foreach (['events.index', 'events.grid', 'events.photo'] as $route) {
            $this->get(route($route))
                ->assertOk()
                ->assertSee('name="filters[age]"', false)
                ->assertSee('name="filters[price]"', false)
                ->assertSee('name="filters[series]"', false);
        }
    }
}
