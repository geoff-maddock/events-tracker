<?php // database/seeds/EventTypeTableSeeder.php

use Illuminate\Database\Seeder;
use App\EventType;

class EventTypeTableSeeder extends Seeder {

    public function run()
    {
        DB::table('event_types')->delete();
    
        EventType::create(array(
            'name' => 'Art Opening',

        ));
    
       EventType::create(array(
            'name' => 'Concert',

        ));

       EventType::create(array(
            'name' => 'Festival',

        ));

       EventType::create(array(
            'name' => 'House Show',

        ));

       EventType::create(array(
            'name' => 'Club Night',

        ));

    }    

}
