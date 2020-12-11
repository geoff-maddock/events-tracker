<?php

use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('roles')->delete();
        
        \DB::table('roles')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'Venue',
                'slug' => 'venue',
                'short' => 'Public site for events',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'Artist',
                'slug' => 'artist',
                'short' => 'Visual artist',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'Producer',
                'slug' => 'producer',
                'short' => 'Music producer',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ),
            3 => 
            array (
                'id' => 4,
                'name' => 'DJ',
                'slug' => 'dj',
                'short' => 'DJ',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ),
            4 => 
            array (
                'id' => 5,
                'name' => 'Promoter',
                'slug' => 'promoter',
                'short' => 'Event promoter',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ),
            5 => 
            array (
                'id' => 6,
                'name' => 'Shop',
                'slug' => 'shop',
                'short' => 'Retail shop',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ),
            6 => 
            array (
                'id' => 7,
                'name' => 'Band',
                'slug' => 'band',
                'short' => 'Live band',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ),
        ));
        
        
    }
}