<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class OccurrenceDaysTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('occurrence_days')->delete();

        \DB::table('occurrence_days')->insert([
            0 => [
                'id' => 1,
                'name' => 'Sunday',
            ],
            1 => [
                'id' => 2,
                'name' => 'Monday',
            ],
            2 => [
                'id' => 3,
                'name' => 'Tuesday',
            ],
            3 => [
                'id' => 4,
                'name' => 'Wednesday',
            ],
            4 => [
                'id' => 5,
                'name' => 'Thursday',
            ],
            5 => [
                'id' => 6,
                'name' => 'Friday',
            ],
            6 => [
                'id' => 7,
                'name' => 'Saturday',
            ],
        ]);
    }
}
