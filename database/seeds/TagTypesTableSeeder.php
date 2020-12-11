<?php

use Illuminate\Database\Seeder;

class TagTypesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('tag_types')->delete();
        
        \DB::table('tag_types')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'Genre',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'Region',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'Category',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ),
            3 => 
            array (
                'id' => 4,
                'name' => 'Topics',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ),
            4 => 
            array (
                'id' => 5,
                'name' => 'Reaction',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ),
        ));
        
        
    }
}