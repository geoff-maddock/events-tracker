<?php

namespace Tests\Feature;

use App\Models\Follow;
use App\Models\Profile;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use App\Services\DigestBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Series lists apply visibility (#2249): a private series shows only to its
 * creator. Several lists relied on a "public" default filter that the request
 * can override, or had no filter at all.
 */
class SeriesListVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const PRIVATE_NAME = 'ZZ Private Listed Series';

    private User $owner;

    private User $other;

    private Series $private;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        $this->owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->other = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        Profile::factory()->create(['user_id' => $this->other->id]);
        $tag = Tag::factory()->create(['name' => 'Zzserieslisttag', 'slug' => 'zzserieslisttag']);

        foreach ([null, now()] as $cancelled) {
            $series = Series::factory()->create([
                'name' => self::PRIVATE_NAME.($cancelled ? ' Cancelled' : ''), 'visibility_id' => Visibility::VISIBILITY_PRIVATE,
                'created_by' => $this->owner->id, 'cancelled_at' => $cancelled, 'occurrence_type_id' => 2,
            ]);
            $series->tags()->attach($tag->id);
            // followed before it was made private
            Follow::create(['user_id' => $this->other->id, 'object_type' => 'series', 'object_id' => $series->id]);
        }
        $this->private = Series::where('name', self::PRIVATE_NAME)->sole();
    }

    public function test_other_users_do_not_see_private_series_in_web_lists(): void
    {
        $this->actingAs($this->other);

        foreach (['/series?filters[visibility]=2', '/series/filter?filters[visibility]=2', '/series/following', '/series/cancelled',
            '/series/cancelled?filters[visibility]=2', '/series/tag/zzserieslisttag', '/threads'] as $url) {
            $content = $this->get($url)->assertOk()->getContent();
            $this->assertStringNotContainsString(self::PRIVATE_NAME, $content, "{$url} lists a private series to another user");
        }
    }

    public function test_other_users_do_not_see_private_series_in_the_api(): void
    {
        $this->actingAs($this->other, 'sanctum');

        foreach (['/api/series/popular', '/api/series?filters[visibility]=2'] as $url) {
            $json = $this->getJson($url)->assertOk()->getContent();
            $this->assertStringNotContainsString(self::PRIVATE_NAME, $json, "{$url} lists a private series to another user");
        }

        $this->getJson("/api/series/{$this->private->id}/photos")->assertNotFound();
        $this->getJson("/api/series/{$this->private->id}/all-photos")->assertNotFound();
    }

    public function test_a_digest_does_not_list_someone_elses_private_series(): void
    {
        $this->assertSame([], app(DigestBuilder::class)->weekly($this->other)->series);
    }

    public function test_the_owner_still_sees_their_private_series(): void
    {
        $this->actingAs($this->owner);
        $this->get('/series/filter?filters[visibility]=2')->assertOk()->assertSee(self::PRIVATE_NAME);

        $this->actingAs($this->owner, 'sanctum');
        $this->getJson("/api/series/{$this->private->id}/photos")->assertOk();
    }
}
