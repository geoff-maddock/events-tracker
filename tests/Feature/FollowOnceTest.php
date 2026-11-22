<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\Follow;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * One follow per user per thing (#2398): repeating a follow adds nothing, and
 * the table itself refuses a second row.
 */
class FollowOnceTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_following_an_entity_twice_keeps_one_follow(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $entity = Entity::factory()->create(['entity_status_id' => EntityStatus::ACTIVE]);
        $mine = fn () => Follow::where(['object_type' => 'entity', 'object_id' => $entity->id, 'user_id' => $user->id])->count();

        $this->actingAs($user)->post("/entities/{$entity->id}/follow");
        $this->actingAs($user)->post("/entities/{$entity->id}/follow");
        $this->assertSame(1, $mine());

        $this->actingAs($user, 'sanctum')->postJson("/api/entities/{$entity->slug}/follow")->assertOk();
        $this->assertSame(1, $mine());
    }

    public function test_the_table_refuses_a_second_follow_from_the_same_user(): void
    {
        $user = User::factory()->create();
        $row = ['object_type' => 'tag', 'object_id' => 1, 'user_id' => $user->id];
        Follow::create($row);

        $this->expectException(UniqueConstraintViolationException::class);
        Follow::create($row);
    }
}
