<?php

namespace Database\Seeders;

use App\Models\Thread;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ThreadsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('threads')->delete();

        // create random thread
        Thread::factory()->create();
    }
}
