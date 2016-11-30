<?php // database/seeds/VisibilityTableSeeder.php

use Illuminate\Database\Seeder;
use App\Visibility;

class VisibilityTableSeeder extends Seeder {

    public function run()
    {
        DB::table('visibilities')->delete();
    
        Visibility::create(array(
            'name' => 'Proposal',

        ));
    
        Visibility::create(array(
            'name' => 'Private',

        ));

        Visibility::create(array(
            'name' => 'Public',

        ));
    

    }    

}
