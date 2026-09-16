<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiGuidePageTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_guest_can_view_api_guide(): void
    {
        $this->get('/api-guide')
            ->assertOk()
            ->assertSee('Using the API')
            ->assertSee('/api/tokens/create')
            ->assertSee(url('/api/docs'), false)
            ->assertSee(url('/help'), false)
            ->assertDontSee('/api/auth/token');
    }

    public function test_help_page_links_to_api_guide(): void
    {
        $this->get('/help')
            ->assertOk()
            ->assertSee(route('pages.apiGuide'), false)
            ->assertDontSee('/api/auth/token');
    }
}
