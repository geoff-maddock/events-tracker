<?php

namespace Tests\Feature\Web;

use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeriesControllerSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['user_status_id' => UserStatus::ACTIVE]));
    }

    public function test_series_index_renders(): void
    {
        Series::factory()->count(2)->create();

        $this->get('/series')->assertOk();
    }

    public function test_series_show_renders(): void
    {
        $series = Series::factory()->create();

        $this->get('/series/'.$series->id)->assertOk();
    }

    public function test_series_create_renders(): void
    {
        $this->get('/series/create')->assertOk();
    }

    public function test_series_tag_index_renders(): void
    {
        $tag = Tag::factory()->create(['slug' => 'zz-series-tag']);
        $series = Series::factory()->create();
        $series->tags()->attach($tag->id);

        $this->get('/series/tag/zz-series-tag')->assertOk();
    }

    public function test_series_reset_redirects(): void
    {
        $this->get('/series/reset')->assertRedirect('/series');
    }
}
