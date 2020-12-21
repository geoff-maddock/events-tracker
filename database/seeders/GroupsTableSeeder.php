<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Group;
use App\Models\Permission;
use App\Models\User;

class GroupsTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('groups')->delete();

        \DB::table('groups')->insert([
            0 => [
                'id' => 1,
                'name' => 'admin',
                'label' => 'Admin',
                'level' => 100,
                'created_at' => '2017-05-19 01:57:45',
                'updated_at' => '2017-05-19 01:57:45',
                'description' => '',
            ],
            1 => [
                'id' => 2,
                'name' => 'super_admin',
                'label' => 'Super Admin',
                'level' => 999,
                'created_at' => '2017-06-20 12:53:25',
                'updated_at' => '2017-06-20 12:53:25',
                'description' => 'Super admin',
            ],
        ]);

        // get the group
        $adminGroup = Group::where('name', 'admin')->first();

        // use this method to assign permissions to the group
        $adminGroup->assignPermission('show_activity');
        $adminGroup->assignPermission('show_admin');

        // find admin user and assign admin group
        $user = User::where('username', 'admin')->first();
        $user->assignGroup('admin');
    }
}
