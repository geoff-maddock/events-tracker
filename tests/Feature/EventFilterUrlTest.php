<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EventFilterUrlTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /**
     * Test that the apply filter from URL route exists and is accessible.
     *
     * @test
     */
    public function testApplyFilterFromUrlRouteExists()
    {
        $response = $this->get(route('events.applyFilterFromUrl'));
        
        // Should redirect to events.filter
        $response->assertRedirect();
    }

    /**
     * Test that filters can be applied from URL parameters.
     *
     * @test
     */
    public function testFiltersCanBeAppliedFromUrl()
    {
        $filterParams = [
            'filters' => [
                'name' => 'Test Event',
            ],
        ];
        
        $response = $this->get(route('events.applyFilterFromUrl', $filterParams));
        
        // Should redirect to the filter page
        $response->assertRedirect(route('events.filter'));
    }

    /**
     * Test that complex filters with multiple parameters work.
     *
     * @test
     */
    public function testComplexFiltersFromUrl()
    {
        $filterParams = [
            'filters' => [
                'name' => 'Concert',
                'tag' => ['music', 'live'],
            ],
            'sort' => 'start_at',
            'direction' => 'desc',
            'limit' => 25,
        ];
        
        $response = $this->get(route('events.applyFilterFromUrl', $filterParams));
        
        // Should redirect to the filter page
        $response->assertRedirect(route('events.filter'));
    }

    /**
     * Test that the events filter page is accessible.
     *
     * @test
     */
    public function testEventsFilterPageAccessible()
    {
        $response = $this->get(route('events.filter'));
        
        $response->assertStatus(200);
        $response->assertSee('Events');
    }

    /**
     * Test that date range filters can be applied from URL.
     *
     * @test
     */
    public function testDateRangeFiltersFromUrl()
    {
        $filterParams = [
            'filters' => [
                'start_at' => [
                    'start' => '2024-01-01',
                    'end' => '2024-12-31',
                ],
            ],
        ];
        
        $response = $this->get(route('events.applyFilterFromUrl', $filterParams));
        
        // Should redirect to the filter page
        $response->assertRedirect(route('events.filter'));
    }
}
