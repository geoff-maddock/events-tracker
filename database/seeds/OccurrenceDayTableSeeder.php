<?php // database/seeds/OccurrenceDayTableSeeder.php

use Illuminate\Database\Seeder;
use App\OccurrenceDay;

class OccurrenceDayTableSeeder extends Seeder {

    public function run()
    {
        DB::table('occurrence_days')->delete();
    
       OccurrenceDay::create(array(
            'name' => 'Sunday',
        ));
    
       OccurrenceDay::create(array(
            'name' => 'Monday',
        ));

       OccurrenceDay::create(array(
            'name' => 'Tuesday',

        ));

       OccurrenceDay::create(array(
            'name' => 'Wednesday',

        ));

       OccurrenceDay::create(array(
            'name' => 'Thursday',

        ));

       OccurrenceDay::create(array(
            'name' => 'Friday',

        ));

       OccurrenceDay::create(array(
            'name' => 'Saturday',

        ));

    }    

}
