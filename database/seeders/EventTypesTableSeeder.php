<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class EventTypesTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('event_types')->delete();

        \DB::table('event_types')->insert([
            0 => [
                'id' => 1,
                'name' => 'Art Opening',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ],
            1 => [
                'id' => 2,
                'name' => 'Concert',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ],
            2 => [
                'id' => 3,
                'name' => 'Festival',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ],
            3 => [
                'id' => 4,
                'name' => 'House Show',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ],
            4 => [
                'id' => 5,
                'name' => 'Club Night',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ],
            5 => [
                'id' => 6,
                'name' => 'Film Screening',
                'created_at' => '2016-03-18 11:43:02',
                'updated_at' => '2016-03-18 11:43:07',
            ],
            6 => [
                'id' => 7,
                'name' => 'Radio Show',
                'created_at' => '2016-03-18 11:43:19',
                'updated_at' => '2016-03-18 11:43:21',
            ],
            7 => [
                'id' => 8,
                'name' => 'Rave',
                'created_at' => '2016-03-28 18:20:51',
                'updated_at' => '2016-03-28 18:20:54',
            ],
            8 => [
                'id' => 9,
                'name' => 'Benefit',
                'created_at' => '2016-04-19 15:02:54',
                'updated_at' => '2016-03-28 18:20:54',
            ],
            9 => [
                'id' => 10,
                'name' => 'Renegade',
                'created_at' => '2016-06-16 11:01:36',
                'updated_at' => '2016-03-28 18:20:54',
            ],
            10 => [
                'id' => 11,
                'name' => 'Pop-up',
                'created_at' => '2016-06-16 11:01:50',
                'updated_at' => '2016-03-28 18:20:54',
            ],
            11 => [
                'id' => 12,
                'name' => 'Activism',
                'created_at' => '2017-01-31 15:40:50',
                'updated_at' => '2016-03-28 18:20:54',
            ],
            12 => [
                'id' => 13,
                'name' => 'Open Mic',
                'created_at' => '2016-03-28 18:20:54',
                'updated_at' => '2016-03-28 18:20:54',
            ],
            13 => [
                'id' => 14,
                'name' => 'Karaoke',
                'created_at' => '2016-03-28 18:20:54',
                'updated_at' => '2016-03-28 18:20:54',
            ],
            14 => [
                'id' => 15,
                'name' => 'Workshop',
                'created_at' => '2016-03-28 18:20:54',
                'updated_at' => '2016-03-28 18:20:54',
            ],
            15 => [
                'id' => 16,
                'name' => 'Live Stream',
                'created_at' => '2016-03-28 18:20:54',
                'updated_at' => '2016-03-28 18:20:54',
            ],
        ]);
    }
}
