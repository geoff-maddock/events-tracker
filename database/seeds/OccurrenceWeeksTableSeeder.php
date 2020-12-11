<?php

use Illuminate\Database\Seeder;

class OccurrenceWeeksTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('occurrence_weeks')->delete();
        
        \DB::table('occurrence_weeks')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'First',
                'created_at' => '2016-02-25 07:54:15',
                'updated_at' => '2016-02-25 07:54:15',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'Second',
                'created_at' => '2016-02-25 07:54:15',
                'updated_at' => '2016-02-25 07:54:15',
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'Third',
                'created_at' => '2016-02-25 07:54:15',
                'updated_at' => '2016-02-25 07:54:15',
            ),
            3 => 
            array (
                'id' => 4,
                'name' => 'Fourth',
                'created_at' => '2016-02-25 07:54:15',
                'updated_at' => '2016-02-25 07:54:15',
            ),
            4 => 
            array (
                'id' => 5,
                'name' => 'Last',
                'created_at' => '2016-02-25 07:54:15',
                'updated_at' => '2016-02-25 07:54:15',
            ),
        ));
        
        
    }
}