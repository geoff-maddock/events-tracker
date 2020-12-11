<?php

use Illuminate\Database\Seeder;

class SeriesTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('series')->delete();

        // create 10 random series
        factory(App\Series::class, 10)->create();
    }
}
