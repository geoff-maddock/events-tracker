<?php

// database/seeds/VisibilityTableSeeder.php

use Illuminate\Database\Seeder;
use App\Visibility;

class VisibilityTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('visibilities')->delete();

        Visibility::create([
            'name' => 'Proposal',
        ]);

        Visibility::create([
            'name' => 'Private',
        ]);

        Visibility::create([
            'name' => 'Public',
        ]);

        Visibility::create([
            'name' => 'Guarded',
        ]);

        Visibility::create([
            'name' => 'Cancelled',
        ]);
    }
}
