<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, array<int, string>>
     */
    private array $indexes = [
        'events'   => ['name', 'short', 'description'],
        'entities' => ['name', 'short', 'description'],
        'series'   => ['name', 'short', 'description'],
        'tags'     => ['name', 'description'],
        'threads'  => ['name', 'description', 'body'],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $existing = array_filter($columns, fn ($c) => Schema::hasColumn($table, $c));
            if (count($existing) === 0) {
                continue;
            }
            $indexName = $table . '_search_fulltext';
            if ($this->indexExists($table, $indexName)) {
                continue;
            }
            $cols = implode(',', array_map(fn ($c) => "`$c`", $existing));
            DB::statement("ALTER TABLE `$table` ADD FULLTEXT `$indexName` ($cols)");
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $_columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $indexName = $table . '_search_fulltext';
            if ($this->indexExists($table, $indexName)) {
                DB::statement("ALTER TABLE `$table` DROP INDEX `$indexName`");
            }
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return count(DB::select("SHOW INDEX FROM `$table` WHERE Key_name = ?", [$index])) > 0;
    }
};
