<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Entity;
use App\Models\Forum;
use App\Models\Group;
use App\Models\Link;
use App\Models\Location;
use App\Models\Permission;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Write endpoints save validated input only, and the ones that had no
 * validation now have it (#2180).
 */
class ValidatedWritesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $admin;

    private User $owner;

    private Entity $entity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        $this->admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->admin->groups()->attach(Group::firstOrCreate(['name' => 'admin'])->id);

        $this->owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->entity = Entity::factory()->create(['slug' => 'zz-validated-entity']);
        $this->entity->owners()->attach($this->owner->id);
    }

    public function test_controllers_do_not_pass_raw_request_input_to_model_writes(): void
    {
        $controllers = [
            'MenusController', 'ForumsController', 'ContactsController', 'RolesController', 'LinksController',
            'EntityTypesController', 'TagsController', 'EventReviewsController', 'PostsController',
            'CategoriesController', 'CommentsController', 'LocationsController', 'GroupsController', 'PermissionsController',
            'Api/EventTypesController', 'Api/EntityStatusesController', 'Api/EntityTypesController',
            'Api/EventStatusesController', 'Api/RolesController', 'Api/MenusController', 'Api/ForumsController',
            'Api/PostsController', 'Api/LocationsController', 'Api/TagsController',
        ];

        foreach ($controllers as $controller) {
            $source = file_get_contents(app_path("Http/Controllers/{$controller}.php"));
            $this->assertDoesNotMatchRegularExpression(
                '/(create|fill)\(\$request->(all|input)\(\)\)|\$input = \$request->(all|input)\(\);/',
                $source,
                "{$controller} passes raw request input to a model write"
            );
        }
    }

    public function test_group_update_is_validated_and_saves_its_description(): void
    {
        $group = Group::create(['name' => 'zzgroup', 'label' => 'ZZ Group', 'level' => 10, 'description' => '']);
        $permission = Permission::create(['name' => 'zz_perm', 'label' => 'ZZ Perm', 'level' => 10, 'description' => '']);
        $this->actingAs($this->admin);

        $this->put("/groups/{$group->id}", ['name' => 'x', 'label' => 'ZZ Group', 'level' => 10])
            ->assertSessionHasErrors('name');
        $this->assertSame('zzgroup', $group->fresh()->name);

        $this->put("/groups/{$group->id}", [
            'name' => 'zzgroup2', 'label' => 'ZZ Group', 'level' => 10,
            'description' => 'Described ZZ', 'permission_list' => [$permission->id],
        ])->assertSessionHasNoErrors();
        $this->assertSame('Described ZZ', $group->fresh()->description);
        $this->assertSame([$permission->id], $group->permissions()->pluck('permissions.id')->all());
    }

    public function test_a_group_without_a_description_saves_instead_of_erroring(): void
    {
        $this->actingAs($this->admin)
            ->post('/groups', ['name' => 'zznodesc', 'label' => 'ZZ No Desc', 'level' => 10])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame('', Group::where('name', 'zznodesc')->sole()->description);
    }

    public function test_permission_update_is_validated(): void
    {
        $permission = Permission::create(['name' => 'zz_perm', 'label' => 'ZZ Perm', 'level' => 10, 'description' => '']);

        $this->actingAs($this->admin)
            ->put("/permissions/{$permission->id}", ['name' => 'zz_perm', 'label' => '', 'level' => 10])
            ->assertSessionHasErrors('label');
    }

    public function test_location_update_is_validated_and_saves_address_fields(): void
    {
        $location = Location::factory()->create(['entity_id' => $this->entity->id, 'slug' => 'zz old slug']);
        $payload = [
            'name' => 'ZZ Location', 'slug' => 'zz old slug', 'city' => 'Pittsburgh',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC, 'location_type_id' => $location->location_type_id,
            'address_one' => '123 ZZ Street', 'latitude' => '40.4406',
        ];
        $this->actingAs($this->owner);

        $this->put("/entities/{$this->entity->slug}/locations/{$location->id}", ['name' => 'ZZ Location'])
            ->assertSessionHasErrors(['city', 'slug']);

        // an existing slug with spaces still saves: the web form keeps its looser slug rule
        $this->put("/entities/{$this->entity->slug}/locations/{$location->id}", $payload)
            ->assertSessionHasNoErrors();
        $this->assertSame('123 ZZ Street', $location->fresh()->address_one);
    }

    public function test_link_update_is_validated(): void
    {
        $link = Link::factory()->create(['url' => 'https://example.com/zz']);
        $this->entity->links()->attach($link->id);

        $this->actingAs($this->owner)
            ->put("/entities/{$this->entity->slug}/links/{$link->id}", ['text' => 'ZZ link', 'url' => ''])
            ->assertSessionHasErrors('url');
        $this->assertSame('https://example.com/zz', $link->fresh()->url);
    }

    public function test_contact_store_saves_optional_fields_without_requiring_a_type(): void
    {
        $this->actingAs($this->owner)
            ->post("/entities/{$this->entity->slug}/contacts", [
                'name' => 'ZZ Contact', 'visibility_id' => Visibility::VISIBILITY_PUBLIC, 'email' => 'zz@example.com',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('zz@example.com', Contact::where('name', 'ZZ Contact')->sole()->email);
    }

    public function test_an_admin_can_update_a_forum_another_admin_created(): void
    {
        $forum = Forum::factory()->create(['created_by' => User::factory()->create()->id]);

        $this->actingAs($this->admin)
            ->put("/forums/{$forum->id}", [
                'name' => 'ZZ Forum Renamed', 'slug' => 'zz-forum-renamed',
                'visibility_id' => Visibility::VISIBILITY_PUBLIC, 'description' => 'ZZ description',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('ZZ description', $forum->fresh()->description);
    }

    public function test_api_tag_update_validates_and_supports_partial_patches(): void
    {
        $tag = Tag::factory()->create(['name' => 'Zzapitag', 'slug' => 'zzapitag']);
        $this->actingAs($this->admin, 'sanctum');

        $this->patchJson("/api/tags/{$tag->slug}", ['description' => 'Only the description'])->assertOk();
        $this->assertSame('Zzapitag', $tag->fresh()->name);
        $this->assertSame('Only the description', $tag->fresh()->description);

        $this->putJson("/api/tags/{$tag->slug}", ['name' => 'Zzapitag', 'slug' => 'Not A Slug'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('slug');
    }

    public function test_api_location_update_rejects_invalid_coordinates(): void
    {
        $location = Location::factory()->create(['entity_id' => $this->entity->id, 'slug' => 'zz-api-location']);

        $this->actingAs($this->owner, 'sanctum')
            ->putJson("/api/locations/{$location->id}", [
                'name' => 'ZZ Location', 'slug' => 'zz-api-location', 'city' => 'Pittsburgh',
                'visibility_id' => Visibility::VISIBILITY_PUBLIC, 'location_type_id' => $location->location_type_id,
                'latitude' => 'north-ish',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('latitude');
    }

    public function test_api_role_put_without_short_saves_instead_of_erroring(): void
    {
        $role = \App\Models\Role::create(['name' => 'Zzrole', 'slug' => 'zzrole', 'short' => 'Short']);

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/roles/{$role->id}", ['name' => 'Zzrole', 'slug' => 'zzrole'])
            ->assertOk();

        $this->assertSame('', $role->fresh()->short);
    }

    public function test_api_location_put_without_entity_id_keeps_the_entity(): void
    {
        $location = Location::factory()->create(['entity_id' => $this->entity->id, 'slug' => 'zz-kept-location']);

        $this->actingAs($this->owner, 'sanctum')
            ->putJson("/api/locations/{$location->id}", [
                'name' => 'ZZ Location', 'slug' => 'zz-kept-location', 'city' => 'Pittsburgh',
                'visibility_id' => Visibility::VISIBILITY_PUBLIC, 'location_type_id' => $location->location_type_id,
            ])
            ->assertOk();

        $this->assertSame($this->entity->id, (int) $location->fresh()->entity_id);
    }

    public function test_editing_a_post_cannot_move_it_to_another_thread(): void
    {
        $this->actingAs($this->owner);
        $thread = \App\Models\Thread::factory()->create();
        $other = \App\Models\Thread::factory()->create();
        $post = \App\Models\Post::factory()->create(['thread_id' => $thread->id, 'created_by' => $this->owner->id]);

        $this->put("/posts/{$post->id}", [
            'body' => 'Edited body ZZ', 'visibility_id' => Visibility::VISIBILITY_PUBLIC, 'thread_id' => $other->id,
        ])->assertSessionHasNoErrors();
        $this->assertSame('Edited body ZZ', $post->fresh()->body);
        $this->assertSame($thread->id, (int) $post->fresh()->thread_id);

        $this->actingAs($this->owner, 'sanctum')
            ->patchJson("/api/posts/{$post->id}", ['thread_id' => $other->id])
            ->assertOk();
        $this->assertSame($thread->id, (int) $post->fresh()->thread_id);
    }

    public function test_api_location_update_rejects_a_null_entity(): void
    {
        $location = Location::factory()->create(['entity_id' => $this->entity->id, 'slug' => 'zz-null-entity']);

        $this->actingAs($this->owner, 'sanctum')
            ->patchJson("/api/locations/{$location->id}", ['entity_id' => null])
            ->assertStatus(422)
            ->assertJsonValidationErrors('entity_id');

        $this->assertSame($this->entity->id, (int) $location->fresh()->entity_id);
    }
}
