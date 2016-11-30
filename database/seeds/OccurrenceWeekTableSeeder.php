<?php // database/seeds/OccurrenceWeekTableSeeder.php

use Illuminate\Database\Seeder;
use App\OccurrenceWeek;

class OccurrenceWeekTableSeeder extends Seeder {

    public function run()
    {
        DB::table('occurrence_weeks')->delete();
    
        OccurrenceWeek::create(array(
            'name' => 'First',

        ));
    
       OccurrenceWeek::create(array(
            'name' => 'Second',

        ));

       OccurrenceWeek::create(array(
            'name' => 'Third',

        ));

       OccurrenceWeek::create(array(
            'name' => 'Fourth',

        ));

       OccurrenceWeek::create(array(
            'name' => 'Last',

        ));

    }    

}
