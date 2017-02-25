<?php // database/seeds/EntityTypeTableSeeder.php

use Illuminate\Database\Seeder;
use App\Action;

class ActionTableSeeder extends Seeder {

    public function run()
    {
        // CREATE, UPDATE, DELETE, LOGIN, LOGOUT, FOLLOW, UNFOLLOW
        DB::table('actions')->delete();
    
        Action::create(array(
            'name' => 'Create',
        ));
    
        Action::create(array(
            'name' => 'Update',
        ));
    
        Action::create(array(
            'name' => 'Delete',
        ));
    
        Action::create(array(
            'name' => 'Login',
        ));
    
        Action::create(array(
            'name' => 'Logout',
        ));
    
        Action::create(array(
            'name' => 'Follow',
        ));
    
        Action::create(array(
            'name' => 'Unfollow',
        ));
    

    }    

}
