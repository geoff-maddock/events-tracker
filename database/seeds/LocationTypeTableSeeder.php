<?php // database/seeds/EventTypeTableSeeder.php

use Illuminate\Database\Seeder;
use App\LocationType;

class LocationTypeTableSeeder extends Seeder {

    public function run()
    {
        DB::table('location_types')->delete();
    
        LocationType::create(array(
            'name' => 'Public',
        ));
    
       LocationType::create(array(
            'name' => 'Business',
        ));

       LocationType::create(array(
            'name' => 'Home',
        ));

       LocationType::create(array(
            'name' => 'Outdoor',
        ));

    }    

}
