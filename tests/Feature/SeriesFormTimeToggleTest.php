<?php

namespace Tests\Feature;

use App\Models\Series;
use App\Models\User;
use App\Models\UserStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The soundcheck/door fields on the series form are toggled with Alpine (#2308).
 * They used to sit in a Bootstrap `collapse` wrapper that Tailwind 4 hides
 * with visibility:collapse, so they could never be seen.
 */
class SeriesFormTimeToggleTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function owner(): User
    {
        return User::factory()->create(['email_verified_at' => Carbon::now(), 'user_status_id' => UserStatus::ACTIVE]);
    }

    public function test_edit_form_shows_time_fields_when_series_has_door_time(): void
    {
        $user = $this->owner();
        $series = Series::factory()->create([
            'created_by' => $user->id,
            'door_at' => Carbon::parse('2026-10-20 20:00:00'),
            'soundcheck_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/series/'.$series->slug.'/edit');

        $response->assertOk();
        $response->assertSee('x-data="{ showExtraTimes: true }"', false);
        $this->assertMatchesRegularExpression('/<div x-show="showExtraTimes" id="form-time"\s*>/', $response->getContent());
        $response->assertSee('value="2026-10-20T20:00"', false);
        $response->assertDontSee('class="collapse', false);
        $response->assertDontSee('data-bs-toggle', false);
    }

    public function test_create_form_starts_with_time_fields_hidden_but_toggleable(): void
    {
        $response = $this->actingAs($this->owner())->get('/series/create');

        $response->assertOk();
        $response->assertSee('x-data="{ showExtraTimes: false }"', false);
        $this->assertMatchesRegularExpression('/id="form-time"\s+style="display: none;"/', $response->getContent());
        $response->assertSee('@click="showExtraTimes = !showExtraTimes"', false);
        $response->assertDontSee('class="collapse', false);
    }

    public function test_event_form_has_no_dead_time_toggle(): void
    {
        $response = $this->actingAs($this->owner())->get('/events/create');

        $response->assertOk();
        $response->assertDontSee('data-bs-toggle', false);
        $response->assertDontSee('Show additional time options', false);
    }
}
