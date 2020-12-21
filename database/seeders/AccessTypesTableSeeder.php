<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AccessTypesTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('access_types')->delete();

        DB::table('access_types')->insert([
            'id' => 1,
            'name' => 'Admin',
            'label' => 'Admin',
            'description' => 'administrator',
            'level' => 100,
            'created_at' => Carbon::now(),
            'updated_at' => null
        ]);

        DB::table('access_types')->insert([
            'id' => 2,
            'name' => 'Owner',
            'label' => 'Owner',
            'description' => 'owner',
            'level' => 10,
            'created_at' => Carbon::now(),
            'updated_at' => null
        ]);

        DB::table('access_types')->insert([
            'id' => 3,
            'name' => 'Member',
            'label' => 'Member',
            'description' => 'member',
            'level' => 5,
            'created_at' => Carbon::now(),
            'updated_at' => null
        ]);

        DB::table('access_types')->insert([
            'id' => 4,
            'name' => 'Follower',
            'label' => 'Follower',
            'description' => 'follower',
            'level' => 2,
            'created_at' => Carbon::now(),
            'updated_at' => null
        ]);

        DB::table('access_types')->insert([
            'id' => 5,
            'name' => 'Blocked',
            'label' => 'Blocked',
            'description' => 'blocked',
            'level' => 0,
            'created_at' => Carbon::now(),
            'updated_at' => null
        ]);
    }
}
