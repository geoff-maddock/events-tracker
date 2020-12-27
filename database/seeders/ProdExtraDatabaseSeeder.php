<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class ProdExtraDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        // use this class to initialize extra data for prod events tracker

        // BARE requirements
        $this->call(EntityTypesTableSeeder::class);
        $this->command->info('Entity types table seeded.');

        $this->call(EntityStatusesTableSeeder::class);
        $this->command->info('Entity status table seeded.');

        $this->call(VisibilitiesTableSeeder::class);
        $this->command->info('Visibilities table seeded.');

        $this->call(EventTypesTableSeeder::class);
        $this->command->info('Event types table seeded.');

        $this->call(EventStatusesTableSeeder::class);
        $this->command->info('Event statuses table seeded.');

        $this->call(TagTypesTableSeeder::class);
        $this->command->info('Tag types table seeded.');

        $this->call(TagsTableSeeder::class);
        $this->command->info('Tags table seeded.');

        $this->call(LocationTypesTableSeeder::class);
        $this->command->info('Location Types table seeded.');

        $this->call(RolesTableSeeder::class);
        $this->command->info('Roles table seeded.');

        $this->call(OccurrenceTypesTableSeeder::class);
        $this->command->info('Occurrence types table seeded.');

        $this->call(OccurrenceDaysTableSeeder::class);
        $this->command->info('Occurrence days table seeded.');

        $this->call(OccurrenceWeeksTableSeeder::class);
        $this->command->info('Occurrence weeks table seeded.');

        $this->call(ContentTypesTableSeeder::class);
        $this->command->info('Content types table seeded.');

        $this->call(AccessTypesTableSeeder::class);
        $this->command->info('Access types table seeded.');

        $this->call(UsersTableSeeder::class);
        $this->command->info('Users table seeded.');

        $this->call(PermissionsTableSeeder::class);
        $this->command->info('Permissions table seeded.');

        $this->call(GroupsTableSeeder::class);
        $this->command->info('Groups table seeded.');

        $this->call(GroupPermissionTableSeeder::class);
        $this->command->info('Group permission table seeded.');

        $this->call(ResponseTypesTableSeeder::class);
        $this->command->info('Response type table seeded.');

        $this->call(ReviewTypesTableSeeder::class);
        $this->command->info('Review type table seeded.');

        $this->call(UserStatusesTableSeeder::class);
        $this->command->info('User statuses table seeded.');

        // EXTRA data
    }
}
