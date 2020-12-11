<?php

use Illuminate\Database\Seeder;

class ReviewTypesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('review_types')->delete();
        
        \DB::table('review_types')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'Informational',
                'created_at' => '2016-11-29 00:00:00',
                'updated_at' => '2016-11-29 00:00:00',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'Positive',
                'created_at' => '2016-11-29 00:00:00',
                'updated_at' => '2016-11-29 00:00:00',
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'Neutral',
                'created_at' => '2016-11-29 00:00:00',
                'updated_at' => '2016-11-29 00:00:00',
            ),
            3 => 
            array (
                'id' => 4,
                'name' => 'Negative',
                'created_at' => '2016-11-29 00:00:00',
                'updated_at' => '2016-11-29 00:00:00',
            ),
        ));
        
        
    }
}