<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ReviewTypesTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('review_types')->delete();

        \DB::table('review_types')->insert([
            0 => [
                'id' => 1,
                'name' => 'Informational',
                'created_at' => '2016-11-29 00:00:00',
                'updated_at' => '2016-11-29 00:00:00',
            ],
            1 => [
                'id' => 2,
                'name' => 'Positive',
                'created_at' => '2016-11-29 00:00:00',
                'updated_at' => '2016-11-29 00:00:00',
            ],
            2 => [
                'id' => 3,
                'name' => 'Neutral',
                'created_at' => '2016-11-29 00:00:00',
                'updated_at' => '2016-11-29 00:00:00',
            ],
            3 => [
                'id' => 4,
                'name' => 'Negative',
                'created_at' => '2016-11-29 00:00:00',
                'updated_at' => '2016-11-29 00:00:00',
            ],
        ]);
    }
}
