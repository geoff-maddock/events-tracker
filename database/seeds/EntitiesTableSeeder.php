<?php

// database/seeds/EntityTableSeeder.php

use Illuminate\Database\Seeder;
use App\Entity;
use App\EntityStatus;
use App\EntityType;
use App\Role;

class EntityTableSeeder extends Seeder
{
    public function run()
    {
        // PITTSBURGH data version

        DB::table('entities')->delete();

        $active = EntityStatus::where('name', '=', 'Active')->first();

        $space = EntityType::where('name', '=', 'Space')->first();
        $individual = EntityType::where('name', '=', 'Individual')->first();
        $group = EntityType::where('name', '=', 'Group')->first();

        $tag = Role::where('name', '=', 'Venue')->first();
        $promoter = Role::where('name', '=', 'Promoter')->first();
        $dj = Role::where('name', '=', 'Dj')->first();
        $producer = Role::where('name', '=', 'Producer')->first();

        // venue section

        $e = Entity::create([
            'name' => 'Brillobox',
            'slug' => 'brillobox',
            'short' => 'Two floor bar-restaurant and venue',
            'description' => '',
            'entity_status_id' => $active->id,
            'entity_type_id' => $space->id,
            'created_by' => 1,
            'updated_by' => 1
        ]);

        $e->roles()->attach($tag->id);

        $e = Entity::create([
            'name' => 'Belvederes',
            'slug' => 'belvederes',
            'short' => 'Punk friendly front bar and large back venue space',
            'description' => '',
            'entity_status_id' => $active->id,
            'entity_type_id' => $space->id,
            'created_by' => 1,
            'updated_by' => 1
        ]);

        $e->roles()->attach($tag->id);

        $e = Entity::create([
            'name' => 'Spirit',
            'slug' => 'spirit',
            'short' => 'Former moose lodge, upstairs hall, downstairs lodge and pizza place.',
            'description' => '',
            'entity_status_id' => $active->id,
            'entity_type_id' => $space->id,
            'created_by' => 1,
            'updated_by' => 1
        ]);

        $e->roles()->attach($tag->id);

        $e = Entity::create([
            'name' => 'Cantina',
            'slug' => 'cantina',
            'short' => 'Mexican restaurant and bar with back patio and djs.',
            'description' => '',
            'entity_status_id' => $active->id,
            'entity_type_id' => $space->id,
            'created_by' => 1,
            'updated_by' => 1
        ]);

        $e->roles()->attach($tag->id);

        $e = Entity::create([
            'name' => 'Goldmark',
            'slug' => 'goldmark',
            'short' => 'Small dj-oriented bar and club',
            'description' => '',
            'entity_status_id' => $active->id,
            'entity_type_id' => $space->id,
            'created_by' => 1,
            'updated_by' => 1
        ]);

        $e->roles()->attach($tag->id);

        $e = Entity::create([
            'name' => 'Smiling Moose',
            'slug' => 'smiling moose',
            'short' => 'Metal friendly two floor bar and venue',
            'description' => '',
            'entity_status_id' => $active->id,
            'entity_type_id' => $space->id,
            'created_by' => 1,
            'updated_by' => 1
        ]);

        $e->roles()->attach($tag->id);

        $e = Entity::create([
            'name' => 'Assemble',
            'slug' => 'assemble',
            'short' => 'diverse and friendly event space',
            'description' => '',
            'entity_status_id' => $active->id,
            'entity_type_id' => $space->id,
            'created_by' => 1,
            'updated_by' => 1
        ]);

        $e->roles()->attach($tag->id);

        $e = Entity::create([
            'name' => 'Roboto Project',
            'slug' => 'roboto project',
            'short' => 'DIY, all ages and straight edge event space and gallery',
            'description' => '',
            'entity_status_id' => $active->id,
            'entity_type_id' => $space->id,
            'created_by' => 1,
            'updated_by' => 1
        ]);

        $e->roles()->attach($tag->id);

        $e = Entity::create([
            'name' => 'Howlers',
            'slug' => 'howlers',
            'short' => 'Rock oriented live music venue and bar',
            'description' => '',
            'entity_status_id' => $active->id,
            'entity_type_id' => $space->id,
            'created_by' => 1,
            'updated_by' => 1
        ]);

        $e->roles()->attach($tag->id);

        $e = Entity::create([
            'name' => 'The Shop',
            'slug' => 'the shop',
            'short' => 'Raw DIY show show space and occasional gallery',
            'description' => '',
            'entity_status_id' => $active->id,
            'entity_type_id' => $space->id,
            'created_by' => 1,
            'updated_by' => 1
        ]);

        $e->roles()->attach($tag->id);

        $e = Entity::create([
            'name' => 'Gooskis',
            'slug' => 'gooskis',
            'short' => 'Punk and rock oriented neighborhood bar and venue',
            'description' => '',
            'entity_status_id' => $active->id,
            'entity_type_id' => $space->id,
            'created_by' => 1,
            'updated_by' => 1
        ]);

        $e->roles()->attach($tag->id);

        $e = Entity::create([
            'name' => 'Rock Room',
            'slug' => 'rock room',
            'short' => 'Punk friendly bar and show space',
            'description' => '',
            'entity_status_id' => $active->id,
            'entity_type_id' => $space->id,
            'created_by' => 1,
            'updated_by' => 1
        ]);

        $e->roles()->attach($tag->id);

        $e = Entity::create([
            'name' => 'Remedy',
            'slug' => 'remedy',
            'short' => 'Two floor bar-restaurant with dj nights',
            'description' => '',
            'entity_status_id' => $active->id,
            'entity_type_id' => $space->id,
            'created_by' => 1,
            'updated_by' => 1
        ]);

        $e->roles()->attach($tag->id);

        $e = Entity::create([
            'name' => 'Gus\'s',
            'slug' => 'gus',
            'short' => 'Bar-restaurant with occasional DJ nights',
            'description' => '',
            'entity_status_id' => $active->id,
            'entity_type_id' => $space->id,
            'created_by' => 1,
            'updated_by' => 1
        ]);

        $e->roles()->attach($tag->id);

        $e = Entity::create([
            'name' => 'Cattivo',
            'slug' => 'cattivo',
            'short' => 'Two floor bar, restaurant, venue.',
            'description' => '',
            'entity_status_id' => $active->id,
            'entity_type_id' => $space->id,
            'created_by' => 1,
            'updated_by' => 1
        ]);

        $e->roles()->attach($tag->id);

        $e = Entity::create([
            'name' => 'Ace Hotel',
            'slug' => 'ace hotel',
            'short' => 'Former YMCA converted into a hotel that holds events.',
            'description' => '',
            'entity_status_id' => $active->id,
            'entity_type_id' => $space->id,
            'created_by' => 1,
            'updated_by' => 1
        ]);

        $e->roles()->attach($tag->id);

        $e = Entity::create([
            'name' => 'Rex Theater',
            'slug' => 'rex theater',
            'short' => 'Former movie theater now a one room convert venue.',
            'description' => '',
            'entity_status_id' => $active->id,
            'entity_type_id' => $space->id,
            'created_by' => 1,
            'updated_by' => 1
        ]);

        $e->roles()->attach($tag->id);

        // dj

        $e = Entity::create([
            'name' => 'Cutups',
            'slug' => 'cutups',
            'short' => 'i like sounds',
            'description' => '',
            'entity_status_id' => $active->id,
            'entity_type_id' => $individual->id,
            'created_by' => 1,
            'updated_by' => 1
        ]);

        $e->roles()->attach($dj->id);
        $e->roles()->attach($promoter->id);

        $e = Entity::create([
            'name' => 'Keeb$',
            'slug' => 'keeb$',
            'short' => 'multi-genre controllerism',
            'description' => '',
            'entity_status_id' => $active->id,
            'entity_type_id' => $individual->id,
            'created_by' => 1,
            'updated_by' => 1
        ]);

        $e->roles()->attach($dj->id);
        $e->roles()->attach($promoter->id);

        $e = Entity::create([
            'name' => 'Stackin Paper',
            'slug' => 'stackin paper',
            'short' => 'cutups and keebs event promo',
            'description' => '',
            'entity_status_id' => $active->id,
            'entity_type_id' => $group->id,
            'created_by' => 1,
            'updated_by' => 1
        ]);

        $e->roles()->attach($promoter->id);

        $e = Entity::create([
            'name' => 'Manny',
            'slug' => 'manny',
            'short' => 'indie and avant garde promoter',
            'description' => '',
            'entity_status_id' => $active->id,
            'entity_type_id' => $individual->id,
            'created_by' => 1,
            'updated_by' => 1
        ]);

        $e->roles()->attach($promoter->id);
    }
}
