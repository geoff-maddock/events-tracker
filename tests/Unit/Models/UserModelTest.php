<?php

namespace Tests\Unit\Models;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Forum;
use App\Models\User;
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
}
