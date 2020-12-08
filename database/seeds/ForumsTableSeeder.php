<?php

use App\Visibility;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ForumsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create one base forum
        DB::table('forums')->insert([
            'name' => 'Forum',
            'slug' => 'forum',
            'description' => 'Discussion forum',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'sort_order' => 0,
            'is_active' => 1,
            'created_by' => 1,
            'updated_by' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
    }
}
