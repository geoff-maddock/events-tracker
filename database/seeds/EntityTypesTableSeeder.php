<?php

use Illuminate\Database\Seeder;

class EntityTypesTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('entity_types')->delete();

        \DB::table('entity_types')->insert([
            0 => [
                'id' => 1,
                'name' => 'Space',
                'slug' => 'space',
                'short' => 'Space for events',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ],
            1 => [
                'id' => 2,
                'name' => 'Group',
                'slug' => 'group',
                'short' => 'Collection of individuals',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ],
            2 => [
                'id' => 3,
                'name' => 'Individual',
                'slug' => 'individual',
                'short' => 'Single individual',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ],
            3 => [
                'id' => 4,
                'name' => 'Interest',
                'slug' => 'interest',
                'short' => 'Interest or topic',
                'created_at' => '2016-02-25 07:54:14',
                'updated_at' => '2016-02-25 07:54:14',
            ],
        ]);
    }
}
