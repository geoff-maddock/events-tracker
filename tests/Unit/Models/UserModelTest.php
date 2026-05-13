<?php

namespace Tests\Unit\Models;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Follow;
use App\Models\Forum;
use App\Models\Group;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\Series;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_password_mutator_hashes_plain_text_password(): void
    {
        $user = User::factory()->create();
        $user->password = 'plain-text-password';
        $user->save();

        $this->assertTrue(Hash::check('plain-text-password', $user->fresh()->password));
    }

    public function test_owns_returns_true_for_event_created_by_user(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->forceFill(['created_by' => $user->id])->save();

        $this->assertTrue($user->owns($event->fresh()));
    }

    public function test_owns_returns_false_for_event_not_created_by_user(): void
    {
        $creator = User::factory()->create();
        $other = User::factory()->create();
        $event = Event::factory()->create();
        $event->forceFill(['created_by' => $creator->id])->save();

        $this->assertFalse($other->owns($event->fresh()));
    }

    public function test_owns_returns_true_for_entity_created_by_user(): void
    {
        $user = User::factory()->create();
        $entity = Entity::factory()->create();
        $entity->forceFill(['created_by' => $user->id])->save();

        $this->assertTrue($user->owns($entity->fresh()));
    }

    public function test_owns_returns_true_for_forum_created_by_user(): void
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create();
        $forum->forceFill(['created_by' => $user->id])->save();

        $this->assertTrue($user->owns($forum->fresh()));
    }

    public function test_full_name_attribute_falls_back_to_user_name_without_profile(): void
    {
        $user = User::factory()->create(['name' => 'just_a_username']);

        $this->assertEquals('just_a_username', $user->full_name);
    }

    public function test_event_count_attribute_counts_user_events(): void
    {
        $user = User::factory()->create();
        $count = $user->event_count;

        $this->assertIsInt($count);
    }

    public function test_attending_count_attribute_returns_integer(): void
    {
        $user = User::factory()->create();

        $this->assertIsInt($user->attending_count);
    }

    public function test_count_entities_following_returns_integer(): void
    {
        $user = User::factory()->create();

        $this->assertIsInt($user->countEntitiesFollowing());
    }

    public function test_count_tags_following_returns_integer(): void
    {
        $user = User::factory()->create();

        $this->assertIsInt($user->countTagsFollowing());
    }

    public function test_is_active_true_when_status_is_active(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->assertTrue($user->is_active);
    }

    public function test_is_active_false_when_status_is_pending(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::PENDING]);

        $this->assertFalse($user->is_active);
    }

    public function test_full_name_uses_profile_first_and_last_when_present(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create([
            'user_id' => $user->id,
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
        ]);

        $this->assertSame('Ada Lovelace', $user->fresh()->full_name);
    }

    public function test_followed_entities_returns_only_entities_followed_by_user(): void
    {
        $user = User::factory()->create();
        $followed = Entity::factory()->create();
        Entity::factory()->create(); // not followed

        Follow::create([
            'user_id' => $user->id,
            'object_id' => $followed->id,
            'object_type' => 'entity',
        ]);

        $names = $user->followedEntities()->pluck('entities.id')->all();
        $this->assertContains($followed->id, $names);
        $this->assertCount(1, $names);
    }

    public function test_followed_series_returns_only_series_followed_by_user(): void
    {
        $user = User::factory()->create();
        $series = Series::factory()->create();
        Series::factory()->create();

        Follow::create([
            'user_id' => $user->id,
            'object_id' => $series->id,
            'object_type' => 'series',
        ]);

        $this->assertEquals([$series->id], $user->followedSeries()->pluck('series.id')->all());
    }

    public function test_followed_tags_returns_only_tags_followed_by_user(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();
        Tag::factory()->create();

        Follow::create([
            'user_id' => $user->id,
            'object_id' => $tag->id,
            'object_type' => 'tag',
        ]);

        $this->assertEquals([$tag->id], $user->followedTags()->pluck('tags.id')->all());
    }

    public function test_group_list_returns_ids_of_assigned_groups(): void
    {
        $user = User::factory()->create();
        $group = Group::first();
        $this->assertNotNull($group);
        $user->groups()->attach($group->id);

        $this->assertContains($group->id, $user->fresh()->group_list);
    }

    public function test_has_group_returns_true_when_user_belongs_to_named_group(): void
    {
        $user = User::factory()->create();
        $group = Group::first();
        $user->groups()->attach($group->id);

        $this->assertTrue($user->fresh()->hasGroup($group->name));
        $this->assertFalse($user->fresh()->hasGroup('zz-unknown-group'));
    }
}
