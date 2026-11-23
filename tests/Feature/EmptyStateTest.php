<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * The "nothing to show" message of a list is the <x-empty-state> component (#2410).
 */
class EmptyStateTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_the_component_renders_its_message_icon_and_actions(): void
    {
        $plain = Blade::render('<x-empty-state>Nothing here.</x-empty-state>');

        $this->assertStringContainsString('<p class="text-muted-foreground">Nothing here.</p>', $plain);
        $this->assertStringContainsString('class="text-center py-12"', $plain);
        $this->assertStringNotContainsString('<i ', $plain);

        $full = Blade::render('<x-empty-state icon="bi-inbox" boxed compact>Nothing <b>here</b>.<x-slot:actions><a href="/x">Go</a></x-slot:actions></x-empty-state>');

        $this->assertStringContainsString('class="text-center py-8 px-4 bg-card rounded-lg border border-border"', $full);
        $this->assertStringContainsString('<i class="bi bi-inbox ', $full);
        $this->assertStringContainsString('aria-hidden="true"', $full);
        $this->assertStringContainsString('<div class="mt-4"><a href="/x">Go</a></div>', $full);
    }

    public function test_empty_lists_show_the_message(): void
    {
        // no events or series exist in a freshly seeded database
        $this->get('/events')->assertOk()
            ->assertSee('No matching events found.')
            ->assertSee('View all events');
        $this->get('/events/grid')->assertOk()->assertSee('No events found matching your criteria.');
        $this->get('/series')->assertOk()->assertSee('No series found.');
        $this->get('/events/tonight')->assertOk()->assertSee('No events found for this window yet.');
        $this->get('/search?keyword=zzqqxxnothing')->assertOk()
            ->assertSee('No matching events found.')
            ->assertSee('No matching entities found.');
    }
}
