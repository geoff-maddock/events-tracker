<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class OccurrenceWeeksTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('occurrence_weeks')->delete();

        \DB::table('occurrence_weeks')->insert([
            0 => [
                'id' => 1,
                'name' => 'First',
                'created_at' => '2016-02-25 07:54:15',
                'updated_at' => '2016-02-25 07:54:15',
            ],
            1 => [
                'id' => 2,
                'name' => 'Second',
                'created_at' => '2016-02-25 07:54:15',
                'updated_at' => '2016-02-25 07:54:15',
            ],
            2 => [
                'id' => 3,
                'name' => 'Third',
                'created_at' => '2016-02-25 07:54:15',
                'updated_at' => '2016-02-25 07:54:15',
            ],
            3 => [
                'id' => 4,
                'name' => 'Fourth',
                'created_at' => '2016-02-25 07:54:15',
                'updated_at' => '2016-02-25 07:54:15',
            ],
            4 => [
                'id' => 5,
                'name' => 'Last',
                'created_at' => '2016-02-25 07:54:15',
                'updated_at' => '2016-02-25 07:54:15',
            ],
        ]);
    }
}
