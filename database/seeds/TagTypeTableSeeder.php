<?php // database/seeds/TagTypeTableSeeder.php

use Illuminate\Database\Seeder;
use App\TagType;

class TagTypeTableSeeder extends Seeder {

    public function run()
    {
        DB::table('tag_types')->delete();
    
        TagType::create(array(
            'name' => 'Genre',

        ));
    
       TagType::create(array(
            'name' => 'Region',

        ));

       TagType::create(array(
            'name' => 'Category',

        ));

       TagType::create(array(
            'name' => 'Topics',

        ));

       TagType::create(array(
            'name' => 'Reaction',

        ));

    }    

}
