<?php // database/seeds/RoleTableSeeder.php

use Illuminate\Database\Seeder;
use App\Role;

class RoleTableSeeder extends Seeder {

    public function run()
    {
        DB::table('roles')->delete();
    
        Role::create(array(
            'name' => 'Venue',
            'slug' => 'venue',
            'short' => 'Public site for events'
        ));
    
    
        Role::create(array(
            'name' => 'Artist',
            'slug' => 'artist',
            'short' => 'Visual artist'
        ));
    
        Role::create(array(
            'name' => 'Producer',
            'slug' => 'producer',
            'short' => 'Music producer'
        ));
    
        Role::create(array(
            'name' => 'DJ',
            'slug' => 'dj',
            'short' => 'DJ'
        ));
    
        Role::create(array(
            'name' => 'Promoter',
            'slug' => 'promoter',
            'short' => 'Event promoter'
        ));
    

    }    

}
