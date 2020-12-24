<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\User;
use App\Models\UserStatus;
use Hash;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Str;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('users')->delete();

        // add a default admin user
        $user = User::create([
            'name' => 'Default Admin',
            'email' => 'admin@yourdomain.com',
            'password' => Hash::make('encodedpassword'),
            'remember_token' => Str::random(10),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'email_verified_at' => Carbon::now(),
            'user_status_id' => UserStatus::ACTIVE
        ]);

        Profile::create([
            'user_id' => $user->id,
            'first_name' => 'Default',
            'last_name' => 'Admin',
            'bio' => 'Default Admin',
            'default_theme' => 'dark-theme'
        ]);

        // To Do: Add default groups or permissions for admin
    }
}
