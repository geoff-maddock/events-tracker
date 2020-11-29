<?php

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class UserSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('roles')->delete();
    
        // add a default admin user
        User::create(array(
            'name' => 'Default Admin',
            'email' => 'admin@yourdomain.com',
            'password' => 'addencodedpassword',
            'remember_token' => '',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'email_verified_at' => Carbon::now(),
            'user_status_id' => 2 // may need to change this into entity?
        ));

        // To Do: Add default groups or permissions for admin
    }
}