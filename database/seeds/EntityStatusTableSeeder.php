<?php // database/seeds/EntityStatusTableSeeder.php

use Illuminate\Database\Seeder;
use App\EntityStatus;

class EntityStatusTableSeeder extends Seeder {

    public function run()
    {
        DB::table('entity_statuses')->delete();
    
        EntityStatus::create(array(
            'name' => 'Draft',
        ));
    
        EntityStatus::create(array(
            'name' => 'Active',
        ));
    
        EntityStatus::create(array(
            'name' => 'Inactive',
        ));


    }    

}
