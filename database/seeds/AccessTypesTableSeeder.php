<?php

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
    }
}
