<?php

use Illuminate\Database\Seeder;

class LocationTypesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('location_types')->delete();
        
        \DB::table('location_types')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'Public',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'Business',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'Home',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ),
            3 => 
            array (
                'id' => 4,
                'name' => 'Outdoor',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ),
            4 => 
            array (
                'id' => 5,
                'name' => 'Gallery',
                'created_at' => '2016-03-17 13:36:13',
                'updated_at' => '2016-03-17 13:36:18',
            ),
            5 => 
            array (
                'id' => 6,
                'name' => 'DIY',
                'created_at' => '2016-07-08 11:37:20',
                'updated_at' => '2016-07-08 11:37:20',
            ),
        ));
        
        
    }
}