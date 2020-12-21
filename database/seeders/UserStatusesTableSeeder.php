<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UserStatusesTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('user_statuses')->delete();

        \DB::table('user_statuses')->insert([
            0 => [
                'id' => 1,
                'name' => 'Pending',
                'can_login' => 0,
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ],
            1 => [
                'id' => 2,
                'name' => 'Active',
                'can_login' => 1,
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ],
            2 => [
                'id' => 3,
                'name' => 'Suspended',
                'can_login' => 0,
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ],
            3 => [
                'id' => 4,
                'name' => 'Banned',
                'can_login' => 0,
                'created_at' => '2017-04-18 12:54:30',
                'updated_at' => '2017-04-18 12:54:30',
            ],
            4 => [
                'id' => 5,
                'name' => 'Deleted',
                'can_login' => 0,
                'created_at' => '2017-04-18 12:54:30',
                'updated_at' => '2017-04-18 12:54:30',
            ],
        ]);
    }
}
