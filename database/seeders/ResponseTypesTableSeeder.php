<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ResponseTypesTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('response_types')->delete();

        \DB::table('response_types')->insert([
            0 => [
                'id' => 1,
                'name' => 'Attending',
                'description' => 'General attending response',
                'created_at' => '2016-03-16 14:59:30',
                'updated_at' => '2020-12-08 09:34:05',
            ],
            1 => [
                'id' => 2,
                'name' => 'Interested',
                'description' => 'Planning on attending',
                'created_at' => '2016-03-16 14:59:53',
                'updated_at' => '2020-12-08 09:34:34',
            ],
            2 => [
                'id' => 3,
                'name' => 'Interested Unable',
                'description' => 'Interested but unable to attend',
                'created_at' => '2016-03-16 15:00:09',
                'updated_at' => '2020-12-08 09:34:40',
            ],
            3 => [
                'id' => 4,
                'name' => 'Uninterested',
                'description' => 'Uninterested in attending',
                'created_at' => '2016-03-16 15:00:34',
                'updated_at' => '2020-12-08 09:34:42',
            ],
            4 => [
                'id' => 5,
                'name' => 'Confirmed',
                'description' => 'Confirmed attendance',
                'created_at' => '2020-12-08 09:32:28',
                'updated_at' => '2020-12-08 09:34:56',
            ],
            5 => [
                'id' => 6,
                'name' => 'Ignore',
                'description' => 'Ignore events',
                'created_at' => '2020-12-10 00:17:23',
                'updated_at' => '2020-12-10 00:17:23',
            ],
        ]);
    }
}
