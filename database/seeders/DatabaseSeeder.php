<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        // use this class to initialize all necessary BARE database seeds
        $this->call('EntityTypeTableSeeder');
        $this->command->info('Entity type table seeded.');

        $this->call('EntityStatusTableSeeder');
        $this->command->info('Entity status table seeded.');

        $this->call('VisibilityTableSeeder');
        $this->command->info('Visibility table seeded.');

        $this->call('EventTypeTableSeeder');
        $this->command->info('Event type table seeded.');

        $this->call('EventStatusTableSeeder');
        $this->command->info('Event status table seeded.');

        $this->call('TagTypeTableSeeder');
        $this->command->info('Tag type table seeded.');

        $this->call('TagTableSeeder');
        $this->command->info('Tag table seeded.');

        $this->call('LocationTypeTableSeeder');
        $this->command->info('Location Type table seeded.');

        $this->call('RoleTableSeeder');
        $this->command->info('Role table seeded.');

        $this->call('OccurrenceTypeTableSeeder');
        $this->command->info('Occurrence type table seeded.');

        $this->call('OccurrenceDayTableSeeder');
        $this->command->info('Occurrence day table seeded.');

        $this->call('OccurrenceWeekTableSeeder');
        $this->command->info('Occurrence week table seeded.');

        $this->call('EntityTableSeeder');
        $this->command->info('Entity table seeded.');
        $this->call(ContentTypesTableSeeder::class);
        $this->call(EventStatusesTableSeeder::class);
        $this->call(EventTypesTableSeeder::class);
        $this->call(PermissionsTableSeeder::class);
        $this->call(GroupsTableSeeder::class);
        $this->call(GroupPermissionTableSeeder::class);
        $this->call(LocationTypesTableSeeder::class);
        $this->call(OccurrenceDaysTableSeeder::class);
        $this->call(OccurrenceTypesTableSeeder::class);
        $this->call(OccurrenceWeeksTableSeeder::class);
        $this->call(ResponseTypesTableSeeder::class);
        $this->call(ReviewTypesTableSeeder::class);
        $this->call(RolesTableSeeder::class);
        $this->call(TagTypesTableSeeder::class);
        $this->call(UserStatusesTableSeeder::class);
        $this->call(VisibilitiesTableSeeder::class);
    }
}
