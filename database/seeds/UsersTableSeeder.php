<?php

use App\Models\User;
use App\Models\UserStatus;
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
        User::create([
            'name' => 'Default Admin',
            'email' => 'admin@yourdomain.com',
            'password' => Hash::make('encodedpassword'),
            'remember_token' => Str::random(10),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'email_verified_at' => Carbon::now(),
            'user_status_id' => UserStatus::ACTIVE
        ]);

        // To Do: Add default groups or permissions for admin
    }
}
