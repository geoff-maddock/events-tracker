<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use App\Action;

class ActionTableSeeder extends Seeder {

    public function run()
    {
        // CREATE, UPDATE, DELETE, LOGIN, LOGOUT, FOLLOW, UNFOLLOW
        DB::table('actions')->delete();
    
        DB::table('actions')->insert(array(
            'name' => 'Create',
        ));
    
        DB::table('actions')->insert(array(
            'name' => 'Update',
        ));
    
        DB::table('actions')->insert(array(
            'name' => 'Delete',
        ));
    
        DB::table('actions')->insert(array(
            'name' => 'Login',
        ));
    
        DB::table('actions')->insert(array(
            'name' => 'Logout',
        ));
    
        DB::table('actions')->insert(array(
            'name' => 'Follow',
        ));
    
        DB::table('actions')->insert(array(
            'name' => 'Unfollow',
        ));
    

    }    

}
