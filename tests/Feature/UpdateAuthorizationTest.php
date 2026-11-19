<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Series;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Who may edit an event or series: its owner, or an admin (`admin` or
 * `super_admin`). One rule, in EventPolicy and SeriesPolicy, for the web and
 * the API; the API used to let only the owner through (#2395).
 */
class UpdateAuthorizationTest extends TestCase
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

        return $user;
    }

    /** @return array<string, array{string|null, bool}> */
    public static function editors(): array
    {
        return [
            'another member' => [null, false],
            'admin' => ['admin', true],
            'super admin' => ['super_admin', true],
        ];
    }

    public function test_policy_allows_the_owner_and_admins_only(): void
    {
        $owner = $this->makeUser();
        $event = Event::factory()->create(['created_by' => $owner->id]);
        $series = Series::factory()->create(['created_by' => $owner->id]);

        foreach ([$event, $series] as $model) {
            $this->assertTrue($owner->can('update', $model));
            $this->assertFalse($this->makeUser()->can('update', $model));
            $this->assertTrue($this->makeUser('admin')->can('update', $model));
            $this->assertTrue($this->makeUser('super_admin')->can('update', $model));
        }
    }

    #[DataProvider('editors')]
    public function test_api_event_patch(?string $group, bool $allowed): void
    {
        $event = Event::factory()->create(['created_by' => $this->makeUser()->id, 'name' => 'Original event name']);

        $response = $this->actingAs($this->makeUser($group), 'sanctum')
            ->patchJson('/api/events/'.$event->id, ['name' => 'Changed event name']);

        $allowed ? $response->assertOk() : $response->assertForbidden();
        $this->assertSame($allowed ? 'Changed event name' : 'Original event name', $event->fresh()->name);
    }

    #[DataProvider('editors')]
    public function test_api_series_patch(?string $group, bool $allowed): void
    {
        $series = Series::factory()->create(['created_by' => $this->makeUser()->id, 'name' => 'Original series name']);

        $response = $this->actingAs($this->makeUser($group), 'sanctum')
            ->patchJson('/api/series/'.$series->id, ['name' => 'Changed series name']);

        $allowed ? $response->assertOk() : $response->assertForbidden();
        $this->assertSame($allowed ? 'Changed series name' : 'Original series name', $series->fresh()->name);
    }

    public function test_api_owner_can_still_patch(): void
    {
        $owner = $this->makeUser();
        $event = Event::factory()->create(['created_by' => $owner->id]);
        $series = Series::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($owner, 'sanctum')->patchJson('/api/events/'.$event->id, ['name' => 'Owner event name'])->assertOk();
        $this->actingAs($owner, 'sanctum')->patchJson('/api/series/'.$series->id, ['name' => 'Owner series name'])->assertOk();
    }

    #[DataProvider('editors')]
    public function test_web_edit_forms(?string $group, bool $allowed): void
    {
        $owner = $this->makeUser();
        $event = Event::factory()->create(['created_by' => $owner->id]);
        $series = Series::factory()->create(['created_by' => $owner->id]);
        $editor = $this->makeUser($group);

        $eventForm = $this->actingAs($editor)->get('/events/'.$event->slug.'/edit');
        $seriesForm = $this->actingAs($editor)->get('/series/'.$series->slug.'/edit');

        if ($allowed) {
            $eventForm->assertOk();
            $seriesForm->assertOk();
        } else {
            $eventForm->assertRedirect();
            $seriesForm->assertRedirect();
        }
    }

    /** The edit link on the event page follows the same rule as the form it opens. */
    #[DataProvider('editors')]
    public function test_event_page_edit_link(?string $group, bool $allowed): void
    {
        $event = Event::factory()->create(['created_by' => $this->makeUser()->id]);

        $response = $this->actingAs($this->makeUser($group))->get('/events/'.$event->slug)->assertOk();

        $allowed ? $response->assertSee('Edit Event') : $response->assertDontSee('Edit Event');
    }
}
