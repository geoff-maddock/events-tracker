<?php // database/seeds/PermissionTableSeeder.php

use Illuminate\Database\Seeder;
use App\Permission;

class PermissionTableSeeder extends Seeder {

    public function run()
    {
        DB::table('permissions')->delete();
    
        Permission::create(array(
            'name' => 'show_user',
            'label' => 'Show User',
            'description' => 'Show User',
            'level' => 1
        ));
    
    
        Permission::create(array(
            'name' => 'edit_user',
            'label' => 'Edit User',
            'description' => 'Edit User',
            'level' => 10
        ));

        Permission::create(array(
            'name' => 'show_event',
            'label' => 'Show Event',
            'description' => 'Show Event',
            'level' => 1
        ));
    
    
        Permission::create(array(
            'name' => 'edit_event',
            'label' => 'Edit Event',
            'description' => 'Edit Event',
            'level' => 10
        ));

        Permission::create(array(
            'name' => 'show_tag',
            'label' => 'Show Tag',
            'description' => 'Show Tag',
            'level' => 1
        ));
    
    
        Permission::create(array(
            'name' => 'edit_tag',
            'label' => 'Edit Tag',
            'description' => 'Edit Tag',
            'level' => 10
        ));

        Permission::create(array(
            'name' => 'show_series',
            'label' => 'Show Series',
            'description' => 'Show Series',
            'level' => 1
        ));
    
    
        Permission::create(array(
            'name' => 'edit_series',
            'label' => 'Edit Series',
            'description' => 'Edit Series',
            'level' => 10
        ));


        Permission::create(array(
            'name' => 'show_entity',
            'label' => 'Show Entity',
            'description' => 'Show Entity',
            'level' => 1
        ));
    
    
        Permission::create(array(
            'name' => 'edit_entity',
            'label' => 'Edit Entity',
            'description' => 'Edit Entity',
            'level' => 10
        ));

        Permission::create(array(
            'name' => 'show_forum',
            'label' => 'Show Forum',
            'description' => 'Show Forum',
            'level' => 1
        ));
    
    
        Permission::create(array(
            'name' => 'edit_forum',
            'label' => 'Edit Forum',
            'description' => 'Edit Forum',
            'level' => 10
        ));

        Permission::create(array(
            'name' => 'show_permission',
            'label' => 'Show Permission',
            'description' => 'Show Permission',
            'level' => 1
        ));
    
    
        Permission::create(array(
            'name' => 'edit_permission',
            'label' => 'Edit Permission',
            'description' => 'Edit Permission',
            'level' => 10
        ));

        Permission::create(array(
            'name' => 'show_group',
            'label' => 'Show Group',
            'description' => 'Show Group',
            'level' => 1
        ));
    
    
        Permission::create(array(
            'name' => 'edit_group',
            'label' => 'Edit Group',
            'description' => 'Edit Group',
            'level' => 10
        ));

        Permission::create(array(
            'name' => 'show_activity',
            'label' => 'Show Activity',
            'description' => 'Show Activity',
            'level' => 1
        ));
    
    
        Permission::create(array(
            'name' => 'show_admin',
            'label' => 'Show Admin',
            'description' => 'Show Admin',
            'level' => 100
        ));

        Permission::create(array(
            'name' => 'trust_post',
            'label' => 'Trust Post',
            'description' => 'Trust Post',
            'level' => 90
        ));
    
    
        Permission::create(array(
            'name' => 'trust_thread',
            'label' => 'Trust Thread',
            'description' => 'Trust Thread',
            'level' => 90
        ));

        Permission::create(array(
            'name' => 'grant_access',
            'label' => 'Grant Access',
            'description' => 'Grant Access',
            'level' => 999
        ));
    
    
        Permission::create(array(
            'name' => 'grant_event_ownership',
            'label' => 'Grant Event Ownership',
            'description' => 'Grant Event Ownership',
            'level' => 100
        ));

        Permission::create(array(
            'name' => 'grant_entity_ownership',
            'label' => 'Grant Entity Ownership',
            'description' => 'Grant Entity Ownership',
            'level' => 100
        ));
    
    
        Permission::create(array(
            'name' => 'grant_series_ownership',
            'label' => 'Grant Series Ownership',
            'description' => 'Grant Series Ownership',
            'level' => 100
        ));

    }    

}
