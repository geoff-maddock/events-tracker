<?php

namespace Tests\Feature;

use App\Models\Alias;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiEntityFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function testFilterMatchesNameOrAlias()
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user);

        $nameMatch = Entity::factory()->create([
            'name' => 'Alpha Co',
            'slug' => 'alpha-co',
        ]);

        $aliasEntity = Entity::factory()->create([
            'name' => 'Other Co',
            'slug' => 'other-co',
        ]);

        $alias = Alias::create(['name' => 'Alpha']);
        $aliasEntity->aliases()->attach($alias);

        $other = Entity::factory()->create([
            'name' => 'Gamma Co',
            'slug' => 'gamma-co',
        ]);

        $response = $this->getJson('/api/entities?filters[name]=Alpha');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Alpha Co'])
            ->assertJsonFragment(['name' => 'Other Co'])
            ->assertJsonMissing(['name' => 'Gamma Co']);
    }
}

