<?php // database/seeds/EventTypeTableSeeder.php

use Illuminate\Database\Seeder;
use App\Models\Tag;
use App\Models\TagType;

class TagTableSeeder extends Seeder {

    public function run()
    {
        DB::table('tags')->delete();
    
        $type = TagType::where('name', 'Genre')->first();

        Tag::create(array(
            'name' => 'Jungle',
            'tag_type_id' => $type->id,
        ));
    
       Tag::create(array(
            'name' => 'Club Music',
            'tag_type_id' => $type->id,

        ));

       Tag::create(array(
            'name' => 'Footwork',
            'tag_type_id' => $type->id,

        ));



    }    

}
