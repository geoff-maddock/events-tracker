<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * EventsController::loadMinimalEmbeds() type-hints int $id. The route had no
 * numeric constraint, so a non-numeric {id} (e.g. a scanner probe like
 * "/events/8715 AND ... AS NUMERIC)/load-minimal-embeds") was passed as a string
 * and raised a TypeError 500 (EVENTREPO-YR). The route now constrains {id} to
 * digits, so a non-numeric id no longer matches and returns 404.
 */
class EventLoadMinimalEmbedsRouteTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_non_numeric_id_returns_not_found_instead_of_type_error(): void
    {
        $this->withExceptionHandling();

        $this->get('/events/not-a-number/load-minimal-embeds')
            ->assertNotFound();
    }
}
