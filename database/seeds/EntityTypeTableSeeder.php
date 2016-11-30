<?php // database/seeds/EntityTypeTableSeeder.php

use Illuminate\Database\Seeder;
use App\EntityType;

class EntityTypeTableSeeder extends Seeder {

    public function run()
    {
        DB::table('entity_types')->delete();
    
        EntityType::create(array(
            'name' => 'Space',
            'slug' => 'space',
            'short' => 'Space for events'
        ));
    
    
        EntityType::create(array(
            'name' => 'Group',
            'slug' => 'group',
            'short' => 'Collection of individuals'
        ));
    
        EntityType::create(array(
            'name' => 'Individual',
            'slug' => 'individual',
            'short' => 'Single individual'
        ));
    
        EntityType::create(array(
            'name' => 'Interest',
            'slug' => 'interest',
            'short' => 'Interest or topic'
        ));
    

    }    

}
