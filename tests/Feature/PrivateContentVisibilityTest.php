<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Series;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Private/proposal events and series are only for their creator (and admins) on
 * show pages and API records, and user emails are only returned to the user or
 * grant_access (#2164).
 */
class PrivateContentVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function makeUser(?string $group = null): User
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        if ($group) {
            $user->assignGroup($group);
        }

        return $user->fresh();
    }

    private function eventWith(int $visibility, User $creator): Event
    {
        $event = Event::factory()->create(['visibility_id' => $visibility]);
        $event->forceFill(['created_by' => $creator->id])->saveQuietly();

        return $event->fresh();
    }

    public function test_private_event_pages_are_hidden_from_everyone_but_the_creator_and_admins(): void
    {
        $creator = $this->makeUser();
        $event = $this->eventWith(Visibility::VISIBILITY_PRIVATE, $creator);

        $this->get('/events/'.$event->id)->assertNotFound();
        $this->get('/events/'.$event->slug)->assertNotFound();
        $this->actingAs($this->makeUser())->get('/events/'.$event->id)->assertNotFound();

        $this->actingAs($creator)->get('/events/'.$event->slug)->assertOk();
        $this->actingAs($this->makeUser('admin'))->get('/events/'.$event->slug)->assertOk();
    }

    public function test_proposal_guarded_cancelled_and_public_event_rules(): void
    {
        $creator = $this->makeUser();
        $proposal = $this->eventWith(Visibility::VISIBILITY_PROPOSAL, $creator);
        $guarded = $this->eventWith(Visibility::VISIBILITY_GUARDED, $creator);
        $cancelled = $this->eventWith(Visibility::VISIBILITY_CANCELLED, $creator);
        $public = $this->eventWith(Visibility::VISIBILITY_PUBLIC, $creator);

        $this->get('/events/'.$proposal->slug)->assertNotFound();
        $this->get('/events/'.$guarded->slug)->assertNotFound();
        $this->actingAs($this->makeUser())->get('/events/'.$guarded->slug)->assertOk();
        // cancelled pages stay reachable from shared links
        $this->get('/events/'.$cancelled->slug)->assertOk();
        $this->get('/events/'.$public->slug)->assertOk();
    }

    public function test_private_event_api_records_are_hidden(): void
    {
        $creator = $this->makeUser();
        $event = $this->eventWith(Visibility::VISIBILITY_PRIVATE, $creator);
        $other = $this->makeUser();

        foreach (['', '/photos', '/all-photos', '/embeds', '/minimal-embeds'] as $suffix) {
            $this->actingAs($other, 'sanctum')->getJson('/api/events/'.$event->id.$suffix)->assertNotFound();
        }

        $this->actingAs($creator, 'sanctum')->getJson('/api/events/'.$event->id)->assertOk();
    }

    public function test_private_series_are_hidden_on_web_and_api(): void
    {
        $creator = $this->makeUser();
        $series = Series::factory()->create(['visibility_id' => Visibility::VISIBILITY_PRIVATE]);
        $series->forceFill(['created_by' => $creator->id])->saveQuietly();
        $other = $this->makeUser();

        $this->get(route('series.show', $series))->assertNotFound();
        $this->actingAs($other, 'sanctum')->getJson('/api/series/'.$series->id)->assertNotFound();

        $this->actingAs($creator)->get(route('series.show', $series))->assertOk();
        $this->actingAs($creator, 'sanctum')->getJson('/api/series/'.$series->id)->assertOk();
    }

    public function test_user_api_only_returns_email_to_self_and_grant_access(): void
    {
        $target = $this->makeUser();
        $other = $this->makeUser();

        $this->actingAs($other, 'sanctum')->getJson('/api/users/'.$target->id)
            ->assertOk()
            ->assertJsonMissingPath('email')
            ->assertJsonMissingPath('followed_tags');
        $this->assertStringNotContainsString($target->email, $this->actingAs($other, 'sanctum')->getJson('/api/users')->getContent());

        $this->actingAs($target, 'sanctum')->getJson('/api/users/'.$target->id)
            ->assertOk()
            ->assertJsonPath('email', $target->email);
        $this->actingAs($this->makeUser('admin'), 'sanctum')->getJson('/api/users/'.$target->id)
            ->assertOk()
            ->assertJsonPath('email', $target->email);
    }
}
