<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SemanticEntityRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /**
     * Test that semantic index routes work for each role type
     * e.g., /venue, /artist, /dj
     */
    public function testSemanticIndexRoutes()
    {
        $user = User::factory()->create(['user_status_id' => 2]);
        $this->actingAs($user);

        // Test each role type
        $roles = ['venue', 'artist', 'dj', 'producer', 'promoter', 'shop', 'band'];

        foreach ($roles as $roleSlug) {
            $response = $this->get("/{$roleSlug}");
            $response->assertStatus(200);
        }
    }

    /**
     * Test that semantic detail routes work for entities with specific roles
     * e.g., /venue/brillobox, /dj/cutups
     */
    public function testSemanticDetailRoutes()
    {
        $user = User::factory()->create(['user_status_id' => 2]);
        $this->actingAs($user);

        // Create a venue entity
        $venueRole = Role::where('slug', 'venue')->firstOrFail();
        $venue = Entity::factory()->create([
            'name' => 'Brillobox',
            'slug' => 'brillobox',
        ]);
        $venue->roles()->attach($venueRole);

        // Create a DJ entity
        $djRole = Role::where('slug', 'dj')->firstOrFail();
        $dj = Entity::factory()->create([
            'name' => 'Cutups',
            'slug' => 'cutups',
        ]);
        $dj->roles()->attach($djRole);

        // Test venue route
        $response = $this->get('/venue/brillobox');
        $response->assertStatus(200);
        $response->assertSee('Brillobox');

        // Test DJ route
        $response = $this->get('/dj/cutups');
        $response->assertStatus(200);
        $response->assertSee('Cutups');
    }

    /**
     * Test that semantic detail routes fail when entity doesn't have the specified role
     */
    public function testSemanticDetailRoutesFailWithWrongRole()
    {
        $this->withExceptionHandling();
        
        $user = User::factory()->create(['user_status_id' => 2]);
        $this->actingAs($user);

        // Create a venue entity
        $venueRole = Role::where('slug', 'venue')->firstOrFail();
        $venue = Entity::factory()->create([
            'name' => 'Brillobox',
            'slug' => 'brillobox',
        ]);
        $venue->roles()->attach($venueRole);

        // Try to access it as a DJ (wrong role)
        $response = $this->get('/dj/brillobox');
        $response->assertStatus(404);
    }

    /**
     * Test that semantic detail routes work for entities with multiple roles
     */
    public function testSemanticDetailRoutesWithMultipleRoles()
    {
        $user = User::factory()->create(['user_status_id' => 2]);
        $this->actingAs($user);

        // Create an entity with both artist and producer roles
        $artistRole = Role::where('slug', 'artist')->firstOrFail();
        $producerRole = Role::where('slug', 'producer')->firstOrFail();
        
        $entity = Entity::factory()->create([
            'name' => 'Multi Role Artist',
            'slug' => 'multi-role-artist',
        ]);
        $entity->roles()->attach([$artistRole->id, $producerRole->id]);

        // Should be accessible via both routes
        $response = $this->get('/artist/multi-role-artist');
        $response->assertStatus(200);
        $response->assertSee('Multi Role Artist');

        $response = $this->get('/producer/multi-role-artist');
        $response->assertStatus(200);
        $response->assertSee('Multi Role Artist');
    }
}
