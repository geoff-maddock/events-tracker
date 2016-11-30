<?php // database/seeds/OccurrenceTypeTableSeeder.php

use Illuminate\Database\Seeder;
use App\OccurrenceType;

class OccurrenceTypeTableSeeder extends Seeder {

    public function run()
    {
        DB::table('occurrence_types')->delete();
    
        OccurrenceType::create(array(
            'name' => 'No Schedule',

        ));
    
       OccurrenceType::create(array(
            'name' => 'Weekly',

        ));

       OccurrenceType::create(array(
            'name' => 'Biweekly',

        ));

       OccurrenceType::create(array(
            'name' => 'Monthly',

        ));

       OccurrenceType::create(array(
            'name' => 'Bimonthly',

        ));

       OccurrenceType::create(array(
            'name' => 'Yearly',

        ));

    }    

}
