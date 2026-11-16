<?php

namespace Tests\Unit\Filters;

use App\Filters\TagFilters;
use App\Models\Follow;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TagFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function applyFilters(array $filters)
    {
        $request = Request::create('/', 'GET', $filters);
        $filter = new TagFilters($request);

        return $filter->apply(Tag::query());
    }

    public function test_name_filter_does_partial_match(): void
    {
        Tag::factory()->create(['name' => 'Synthwave']);
        Tag::factory()->create(['name' => 'Jazz']);

        $results = $this->applyFilters(['name' => 'Synth'])->get();

        $this->assertCount(1, $results);
    }

    public function test_id_filter_matches_single_value(): void
    {
        $tag = Tag::factory()->create();
        Tag::factory()->create();

        $results = $this->applyFilters(['id' => $tag->id])->get();

        $this->assertCount(1, $results);
        $this->assertEquals($tag->id, $results->first()->id);
    }

    public function test_id_filter_matches_comma_separated_values(): void
    {
        $a = Tag::factory()->create();
        $b = Tag::factory()->create();
        Tag::factory()->create();

        $results = $this->applyFilters(['id' => $a->id.','.$b->id])->get();

        $this->assertCount(2, $results);
    }

    public function test_description_filter_does_partial_match(): void
    {
        Tag::factory()->create(['name' => 'Synthwave', 'description' => 'Music with synths']);
        Tag::factory()->create(['name' => 'Other', 'description' => 'Acoustic only']);

        $results = $this->applyFilters(['description' => 'synth'])->get();

        $this->assertCount(1, $results);
    }

    /**
     * @return array{0: User, 1: Tag, 2: Tag, 3: Tag}
     */
    private function displayTypeFixture(): array
    {
        $user = User::factory()->create();
        $followed = Tag::factory()->create(['name' => 'Followed Tag']);
        $created = Tag::factory()->create(['name' => 'Created Tag']);
        $created->forceFill(['created_by' => $user->id])->save();
        $other = Tag::factory()->create(['name' => 'Other Tag']);
        $other->forceFill(['created_by' => null])->save();

        Follow::create(['object_id' => $followed->id, 'object_type' => 'tag', 'user_id' => $user->id]);
        // someone else following a tag doesn't count as this user following it
        Follow::create(['object_id' => $other->id, 'object_type' => 'tag', 'user_id' => User::factory()->create()->id]);

        $this->actingAs($user);

        return [$user, $followed, $created, $other];
    }

    public function test_display_type_following_returns_only_followed_tags(): void
    {
        [, $followed] = $this->displayTypeFixture();

        $ids = $this->applyFilters(['display_type' => 'following'])->pluck('id');

        $this->assertEquals([$followed->id], $ids->all());
    }

    public function test_display_type_not_following_excludes_followed_tags(): void
    {
        [, $followed, $created, $other] = $this->displayTypeFixture();

        $ids = $this->applyFilters(['display_type' => 'not_following'])->pluck('id');

        $this->assertNotContains($followed->id, $ids);
        $this->assertContains($created->id, $ids);
        $this->assertContains($other->id, $ids);
    }

    public function test_display_type_created_returns_only_the_users_tags(): void
    {
        [, , $created] = $this->displayTypeFixture();

        $ids = $this->applyFilters(['display_type' => 'created'])->pluck('id');

        $this->assertEquals([$created->id], $ids->all());
    }

    public function test_display_type_not_created_includes_tags_without_a_creator(): void
    {
        [, , $created, $other] = $this->displayTypeFixture();

        $ids = $this->applyFilters(['display_type' => 'not_created'])->pluck('id');

        $this->assertNotContains($created->id, $ids);
        $this->assertContains($other->id, $ids);
    }

    public function test_display_type_is_passthrough_for_guests_and_unknown_values(): void
    {
        Tag::factory()->count(2)->create();
        $total = Tag::count();

        $this->assertSame($total, $this->applyFilters(['display_type' => 'following'])->count());

        $this->actingAs(User::factory()->create());

        $this->assertSame($total, $this->applyFilters(['display_type' => 'all'])->count());
        $this->assertSame($total, $this->applyFilters(['display_type' => 'bogus'])->count());
    }
}
