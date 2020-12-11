<?php

use Illuminate\Database\Seeder;

class OccurrenceTypesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('occurrence_types')->delete();
        
        \DB::table('occurrence_types')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'No Schedule',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'Weekly',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'Biweekly',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ),
            3 => 
            array (
                'id' => 4,
                'name' => 'Monthly',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ),
            4 => 
            array (
                'id' => 5,
                'name' => 'Bimonthly',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ),
            5 => 
            array (
                'id' => 6,
                'name' => 'Yearly',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ),
        ));
        
        
    }
}