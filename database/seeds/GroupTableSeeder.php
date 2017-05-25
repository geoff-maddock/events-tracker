<?php

use Illuminate\Database\Seeder;
use App\Group;
use App\Permission;
use App\User;

class GroupTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        DB::table('groups')->delete();
        DB::table('group_permission')->delete();
        DB::table('group_user')->delete();
    
        Group::create(array(
            'name' => 'user',
            'label' => 'User',
            'description' => 'User',
            'level' => 10
        ));
 
         Group::create(array(
            'name' => 'follower',
            'label' => 'Follower',
            'description' => 'Follower',
            'level' => 25
        ));
    
        Group::create(array(
            'name' => 'member',
            'label' => 'Member',
            'description' => 'Member',
            'level' => 50
        ));
    
        Group::create(array(
            'name' => 'owner',
            'label' => 'Owner',
            'description' => 'Owner',
            'level' => 75
        ));
 
         Group::create(array(
            'name' => 'trusted',
            'label' => 'Trusted',
            'description' => 'Trusted',
            'level' => 90
        ));
 
        $adminGroup = Group::create(array(
            'name' => 'admin',
            'label' => 'Admin',
            'description' => 'Admin',
            'level' => 100
        ));

        $adminGroup->assignPermission('show_activity');    
        $adminGroup->assignPermission('show_admin');

        Group::create(array(
            'name' => 'super_admin',
            'label' => 'Super Admin',
            'description' => 'Super admin',
            'level' => 999
        ));
    
        Group::create(array(
            'name' => 'blocked',
            'label' => 'Blocked',
            'description' => 'Blocked',
            'level' => 0
        ));   

        Group::create(array(
            'name' => 'inactive',
            'label' => 'Inactive',
            'description' => 'Inactive',
            'level' => 0
        ));   


        // to add - create a user in each group to test with?
        $user = User::find(1);
        $user->assignGroup('admin');
	}
}
