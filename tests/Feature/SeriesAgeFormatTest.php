<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Series;

class SeriesAgeFormatTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /**
     * Test that age_format returns "All Ages" for min_age of 0
     */
    public function test_age_format_returns_all_ages_for_zero()
    {
        $series = Series::factory()->create([
            'min_age' => 0,
        ]);

        $this->assertEquals('All Ages', $series->age_format);
    }

    /**
     * Test that age_format returns age with "+" suffix for 18
     */
    public function test_age_format_returns_18_plus()
    {
        $series = Series::factory()->create([
            'min_age' => 18,
        ]);

        $this->assertEquals('18+', $series->age_format);
    }

    /**
     * Test that age_format returns age with "+" suffix for 21
     */
    public function test_age_format_returns_21_plus()
    {
        $series = Series::factory()->create([
            'min_age' => 21,
        ]);

        $this->assertEquals('21+', $series->age_format);
    }

    /**
     * Test that age_format returns empty string when min_age is null
     */
    public function test_age_format_returns_empty_for_null()
    {
        $series = Series::factory()->create([
            'min_age' => null,
        ]);

        $this->assertEquals('', $series->age_format);
    }
}
