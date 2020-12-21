<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tag;
use App\Models\TagType;

class TagTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('tags')->delete();

        $type = TagType::where('name', 'Genre')->first();

        Tag::create([
            'name' => 'Jungle',
            'tag_type_id' => $type->id,
        ]);

        Tag::create([
            'name' => 'Club Music',
            'tag_type_id' => $type->id,
        ]);

        Tag::create([
            'name' => 'Footwork',
            'tag_type_id' => $type->id,
        ]);
    }
}
