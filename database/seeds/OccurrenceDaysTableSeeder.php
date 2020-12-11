<?php

use Illuminate\Database\Seeder;

class OccurrenceDaysTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('occurrence_days')->delete();
        
        \DB::table('occurrence_days')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'Sunday',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'Monday',
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'Tuesday',
            ),
            3 => 
            array (
                'id' => 4,
                'name' => 'Wednesday',
            ),
            4 => 
            array (
                'id' => 5,
                'name' => 'Thursday',
            ),
            5 => 
            array (
                'id' => 6,
                'name' => 'Friday',
            ),
            6 => 
            array (
                'id' => 7,
                'name' => 'Saturday',
            ),
        ));
        
        
    }
}