<?php // database/seeds/EntityTypeTableSeeder.php

use Illuminate\Database\Seeder;
use App\EntityType;

class ResponseTypeTableSeeder extends Seeder {

    public function run()
    {
        // IGNORE, NOT INTERESTED, CANNOT ATTEND, MAY ATTEND, ATTENDING, ATTENDED
        DB::table('response_types')->delete();
    
        ResponseType::create(array(
            'name' => 'Ignore',

        ));
    
        ResponseType::create(array(
            'name' => 'Not Interested',

        ));
    
        ResponseType::create(array(
            'name' => 'Can Not Attend',

        ));
    
        ResponseType::create(array(
            'name' => 'Might Attend',

        ));
    
        ResponseType::create(array(
            'name' => 'Attending',

        ));
    
        ResponseType::create(array(
            'name' => 'Attending',

        ));
    

    }    

}
