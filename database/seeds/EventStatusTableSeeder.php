<?php // database/seeds/EventStatusTableSeeder.php

use Illuminate\Database\Seeder;
use App\EventStatus;

class EventStatusTableSeeder extends Seeder {

    public function run()
    {
        DB::table('event_statuses')->delete();
    
        EventStatus::create(array(
            'name' => 'Draft',
        ));
    
        EventStatus::create(array(
            'name' => 'Proposal',
        ));
    
        EventStatus::create(array(
            'name' => 'Approved',
        ));

        EventStatus::create(array(
            'name' => 'Happening Now',
        ));

        EventStatus::create(array(
            'name' => 'Past',
        ));

        EventStatus::create(array(
            'name' => 'Rejected',
        ));
    
        EventStatus::create(array(
            'name' => 'Cancelled',
        ));
    

    }    

}
