<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Follow;
use App\Models\Series;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Entities, series, tags and threads share one Followable trait (#2409).
 */
class FollowableTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_each_model_reports_its_followers(): void
    {
        $this->actingAs(User::factory()->create());

        $this->assertFollows(Entity::factory()->create(), Entity::factory()->create(), 'entity');
        $this->assertFollows(Series::factory()->create(), Series::factory()->create(), 'series');
        $this->assertFollows(Tag::factory()->create(), Tag::factory()->create(), 'tag');
        $this->assertFollows(Thread::factory()->create(), Thread::factory()->create(), 'thread');
    }

    private function assertFollows(Entity|Series|Tag|Thread $model, Entity|Series|Tag|Thread $other, string $type): void
    {
        $fan = User::factory()->create();
        $stranger = User::factory()->create();

        $follow = Follow::create(['user_id' => $fan->id, 'object_type' => $type, 'object_id' => $model->id]);
        // a follow of another record of the same type, and of the same id under another type
        Follow::create(['user_id' => $stranger->id, 'object_type' => $type, 'object_id' => $other->id]);
        Follow::create(['user_id' => $stranger->id, 'object_type' => 'event', 'object_id' => $model->id]);

        $this->assertTrue($follow->is($model->followedBy($fan)));
        $this->assertNull($model->followedBy($stranger));
        $this->assertNull($model->followedBy(null));

        $this->assertSame([$fan->id], $model->followers()->pluck('id')->all());
        $this->assertTrue($model->followers()->first()->relationLoaded('profile'));

        $this->assertSame([$follow->id], $model->follows()->pluck('id')->all());
        $this->assertSame(1, $model::query()->whereKey($model->id)->withCount('follows')->firstOrFail()->follows_count);
    }
}
