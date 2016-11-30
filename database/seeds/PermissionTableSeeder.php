<?php // database/seeds/PermissionTableSeeder.php

use Illuminate\Database\Seeder;
use App\Permission;

class PermissionTableSeeder extends Seeder {

    public function run()
    {
        DB::table('permissions')->delete();
    
        Permission::create(array(
            'name' => 'Super Admin',
            'slug' => 'superadmin',
            'description' => 'Access to all objects, and grant access to add permissions'
        ));
    
    
        Permission::create(array(
            'name' => 'Admin',
            'slug' => 'admin',
            'description' => 'All access to the related object and grant access to add permissions'
        ));
    
        Permission::create(array(
            'name' => 'Owner',
            'slug' => 'owner',
            'description' => 'All access to the related object, owner designation'
        ));
    
        Permission::create(array(
            'name' => 'Member',
            'slug' => 'member',
            'description' => 'Read access to all related objects, public and private'
        ));
  
        Permission::create(array(
            'name' => 'Follower',
            'slug' => 'follower',
            'description' => 'Read access to all public related objects'
        ));  

    }    

}
