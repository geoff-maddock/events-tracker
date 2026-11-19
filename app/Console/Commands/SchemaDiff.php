<?php

namespace App\Console\Commands;

use App\Services\Database\SchemaSnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Compares this database with what a fresh install builds.
 *
 * Columns have reached production without a migration before, which only
 * shows up later as "Unknown column" on a new install or a rebuilt server.
 * Read-only for the database being checked: the fresh copy is built in a
 * scratch database beside it and dropped afterwards.
 */
class SchemaDiff extends Command
{
    protected $signature = 'db:schema-diff
                            {--database= : The database to check, when it is not this connection\'s own (same server)}
                            {--against= : An existing freshly migrated database to compare with, instead of building one}';

    protected $description = 'Compare a database\'s tables, columns, indexes and foreign keys with a fresh migrate';

    public function handle(): int
    {
        $connection = DB::connection();

        if ($connection->getDriverName() !== 'mysql') {
            $this->error('db:schema-diff only supports MySQL.');

            return self::FAILURE;
        }

        $database = $this->option('database') ?: $connection->getDatabaseName();
        $fresh = $this->option('against');
        $scratch = null;

        try {
            if ($fresh === null) {
                $fresh = $scratch = $this->buildFreshDatabase();
            }

            $expected = SchemaSnapshot::capture($connection, $fresh);
            $actual = SchemaSnapshot::capture($connection, $database);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } finally {
            if ($scratch !== null) {
                $connection->statement('drop database if exists `'.$scratch.'`');
            }
        }

        if ($expected === [] || $actual === []) {
            $this->error('No tables found in '.($expected === [] ? $fresh : $database).'. Check the name and that this user can read it.');

            return self::FAILURE;
        }

        $differences = SchemaSnapshot::diff($expected, $actual, 'a fresh migrate', $database);

        if ($differences === []) {
            $this->info("{$database} matches a fresh migrate: ".count($actual).' tables.');

            return self::SUCCESS;
        }

        foreach ($differences as $table => $lines) {
            $this->line("<comment>{$table}</comment>");
            foreach ($lines as $line) {
                $this->line('  '.$line);
            }
        }

        $this->newLine();
        $this->error(count($differences)." table(s) in {$database} differ from a fresh migrate.");

        return self::FAILURE;
    }

    /**
     * The dump `migrate` loads for a connection, as MigrateCommand resolves it.
     */
    private function schemaDumpPath(string $connectionName): ?string
    {
        foreach (['dump', 'sql'] as $extension) {
            if (file_exists($path = database_path("schema/{$connectionName}-schema.{$extension}"))) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Run the schema dump and every migration into a new, empty database on
     * the same server and return its name.
     */
    private function buildFreshDatabase(): string
    {
        $connection = DB::connection();
        $name = $connection->getDatabaseName().'_schema_check';

        if ($connection->select('select 1 from information_schema.SCHEMATA where SCHEMA_NAME = ?', [$name]) !== []) {
            // never drop a database this run didn't create
            throw new \RuntimeException("The scratch database {$name} already exists. Drop it, or pass --against={$name} to compare with it as it is.");
        }

        $this->line("Building a fresh copy in {$name} ...");
        $connection->statement('create database `'.$name.'`');

        config(['database.connections.schema_check' => array_merge(
            config('database.connections.'.$connection->getName()),
            ['database' => $name]
        )]);

        try {
            // Laravel looks the dump up by connection name, so name it outright:
            // without it the scratch build would run the migrations alone.
            $exit = Artisan::call('migrate', array_filter([
                '--database' => 'schema_check',
                '--force' => true,
                '--schema-path' => $this->schemaDumpPath($connection->getName()),
            ]));
        } catch (Throwable $e) {
            $connection->statement('drop database if exists `'.$name.'`');

            throw $e;
        } finally {
            DB::purge('schema_check');
        }

        if ($exit !== 0) {
            $connection->statement('drop database if exists `'.$name.'`');

            throw new \RuntimeException('migrate failed in the scratch database: '.trim(Artisan::output()));
        }

        return $name;
    }
}
