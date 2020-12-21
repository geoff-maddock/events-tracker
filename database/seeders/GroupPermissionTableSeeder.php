<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class GroupPermissionTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('group_permission')->delete();

        \DB::table('group_permission')->insert([
            0 => [
                'group_id' => 1,
                'permission_id' => 18,
            ],
            1 => [
                'group_id' => 1,
                'permission_id' => 16,
            ],
            2 => [
                'group_id' => 1,
                'permission_id' => 14,
            ],
            3 => [
                'group_id' => 1,
                'permission_id' => 19,
            ],
            4 => [
                'group_id' => 1,
                'permission_id' => 20,
            ],
            5 => [
                'group_id' => 1,
                'permission_id' => 21,
            ],
            6 => [
                'group_id' => 1,
                'permission_id' => 17,
            ],
            7 => [
                'group_id' => 1,
                'permission_id' => 11,
            ],
            8 => [
                'group_id' => 1,
                'permission_id' => 12,
            ],
        ]);
    }
}
