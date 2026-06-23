<?php

namespace Tests\Feature;

use App\Models\EntityStatus;
use App\Models\EntityType;
use App\Models\EventStatus;
use App\Models\EventType;
use App\Models\Menu;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Global reference data (entity/event types & statuses, roles, menus and
 * tags) may only be mutated by admins. Previously any authenticated user
 * could create/update/delete these shared records. The destroy endpoints
 * take no FormRequest, so they exercise the controller's requireAdmin()
 * guard directly.
 */
class ApiReferenceDataAuthTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $member;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->member = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->admin->assignGroup('admin');
    }

    public static function referenceEndpoints(): array
    {
        return [
            'entity-types' => ['entity-types', EntityType::class],
            'entity-statuses' => ['entity-statuses', EntityStatus::class],
            'event-types' => ['event-types', EventType::class],
            'event-statuses' => ['event-statuses', EventStatus::class],
            'roles' => ['roles', Role::class],
            'tags' => ['tags', Tag::class],
        ];
    }

    /**
     * @dataProvider referenceEndpoints
     */
    public function test_non_admin_cannot_delete_reference_record(string $endpoint, string $modelClass): void
    {
        /** @var \Illuminate\Database\Eloquent\Model $record */
        $record = $modelClass::query()->first();
        $this->assertNotNull($record, "Expected a seeded {$endpoint} record.");

        $this->actingAs($this->member, 'sanctum');
        $this->deleteJson('/api/'.$endpoint.'/'.$record->getRouteKey())
            ->assertStatus(403);

        $this->assertNotNull($modelClass::find($record->getKey()), 'Record must survive a non-admin delete.');
    }

    public function test_non_admin_cannot_delete_menu(): void
    {
        $menu = Menu::create([
            'name' => 'ZZ Menu',
            'slug' => 'zz-menu',
            'body' => 'body',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($this->member, 'sanctum');
        $this->deleteJson('/api/menus/'.$menu->getRouteKey())->assertStatus(403);

        $this->assertNotNull(Menu::find($menu->id));
    }

    public function test_admin_can_delete_reference_record(): void
    {
        $type = EntityType::create([
            'name' => 'ZZ Disposable Type',
            'slug' => 'zz-disposable-type',
            'short' => 'temp',
        ]);

        $this->actingAs($this->admin, 'sanctum');
        $this->deleteJson('/api/entity-types/'.$type->getRouteKey())->assertStatus(204);

        $this->assertNull(EntityType::find($type->id));
    }

    public function test_non_admin_cannot_create_entity_type_with_valid_payload(): void
    {
        $this->actingAs($this->member, 'sanctum');
        $this->postJson('/api/entity-types', [
            'name' => 'ZZ New Type',
            'slug' => 'zz-new-type',
            'short' => 'blurb',
        ])->assertStatus(403);

        $this->assertDatabaseMissing('entity_types', ['slug' => 'zz-new-type']);
    }

    public function test_admin_can_create_entity_type(): void
    {
        $this->actingAs($this->admin, 'sanctum');
        $this->postJson('/api/entity-types', [
            'name' => 'ZZ New Type',
            'slug' => 'zz-new-type',
            'short' => 'blurb',
        ])->assertOk();

        $this->assertDatabaseHas('entity_types', ['slug' => 'zz-new-type']);
    }
}
