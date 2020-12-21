<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ContentTypesTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('content_types')->delete();

        \DB::table('content_types')->insert([
            0 => [
                'id' => 1,
                'name' => 'Plain Text',
                'created_at' => null,
                'updated_at' => null,
            ],
            1 => [
                'id' => 2,
                'name' => 'HTML',
                'created_at' => null,
                'updated_at' => null,
            ],
        ]);
    }
}
