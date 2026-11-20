<?php

namespace App\Services\Database;

use Illuminate\Database\Connection;

/**
 * The structure of one MySQL database, read from information_schema, in a
 * form two databases can be compared in: what a fresh install builds against
 * what production actually has.
 *
 * Index and foreign key names are left out on purpose. A database that has
 * been migrated for years and one built from a dump name them differently
 * while enforcing the same thing, so they are compared by what they cover.
 */
class SchemaSnapshot
{
    /**
     * @return array<string, array{columns: array<string, string>, indexes: list<string>, foreign_keys: list<string>}>
     */
    public static function capture(Connection $connection, string $database): array
    {
        $tables = [];

        $rows = $connection->select(
            "select TABLE_NAME as t from information_schema.TABLES where TABLE_SCHEMA = ? and TABLE_TYPE = 'BASE TABLE'",
            [$database]
        );
        foreach ($rows as $row) {
            $tables[$row->t] = ['columns' => [], 'indexes' => [], 'foreign_keys' => []];
        }

        $rows = $connection->select(
            'select TABLE_NAME as t, COLUMN_NAME as c, COLUMN_TYPE as type, IS_NULLABLE as nullable, COLUMN_DEFAULT as def, EXTRA as extra, COLLATION_NAME as collation
             from information_schema.COLUMNS where TABLE_SCHEMA = ? order by TABLE_NAME, ORDINAL_POSITION',
            [$database]
        );
        foreach ($rows as $row) {
            if (isset($tables[$row->t])) {
                $tables[$row->t]['columns'][$row->c] = self::describeColumn($row);
            }
        }

        $rows = $connection->select(
            "select TABLE_NAME as t, INDEX_NAME as i, max(NON_UNIQUE) as non_unique, max(INDEX_TYPE) as type,
                    group_concat(concat(COLUMN_NAME, if(SUB_PART is null, '', concat('(', SUB_PART, ')'))) order by SEQ_IN_INDEX separator ', ') as cols
             from information_schema.STATISTICS where TABLE_SCHEMA = ? group by TABLE_NAME, INDEX_NAME",
            [$database]
        );
        foreach ($rows as $row) {
            if (isset($tables[$row->t])) {
                $kind = $row->i === 'PRIMARY' ? 'primary key' : ((int) $row->non_unique === 0 ? 'unique' : 'index');
                $type = $row->type === 'BTREE' ? '' : ' '.strtolower($row->type);
                $tables[$row->t]['indexes'][] = "{$kind}{$type} ({$row->cols})";
            }
        }

        $rows = $connection->select(
            "select k.TABLE_NAME as t, k.REFERENCED_TABLE_NAME as ref, r.DELETE_RULE as on_delete, r.UPDATE_RULE as on_update,
                    group_concat(k.COLUMN_NAME order by k.ORDINAL_POSITION separator ', ') as cols,
                    group_concat(k.REFERENCED_COLUMN_NAME order by k.ORDINAL_POSITION separator ', ') as ref_cols
             from information_schema.KEY_COLUMN_USAGE k
             join information_schema.REFERENTIAL_CONSTRAINTS r
               on r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA and r.CONSTRAINT_NAME = k.CONSTRAINT_NAME and r.TABLE_NAME = k.TABLE_NAME
             where k.TABLE_SCHEMA = ? and k.REFERENCED_TABLE_NAME is not null
             group by k.TABLE_NAME, k.CONSTRAINT_NAME, k.REFERENCED_TABLE_NAME, r.DELETE_RULE, r.UPDATE_RULE",
            [$database]
        );
        foreach ($rows as $row) {
            if (isset($tables[$row->t])) {
                $tables[$row->t]['foreign_keys'][] = strtolower(
                    "({$row->cols}) -> {$row->ref} ({$row->ref_cols}) on delete {$row->on_delete}, on update {$row->on_update}"
                );
            }
        }

        foreach ($tables as &$table) {
            sort($table['indexes']);
            sort($table['foreign_keys']);
        }
        ksort($tables);

        return $tables;
    }

    /**
     * What $actual has that $expected does not, and the reverse, as lines of text.
     *
     * @param  array<string, array{columns: array<string, string>, indexes: list<string>, foreign_keys: list<string>}>  $expected
     * @param  array<string, array{columns: array<string, string>, indexes: list<string>, foreign_keys: list<string>}>  $actual
     * @return array<string, list<string>> differences per table, empty when the two match
     */
    public static function diff(array $expected, array $actual, string $expectedLabel = 'expected', string $actualLabel = 'actual'): array
    {
        $differences = [];

        foreach (array_diff_key($expected, $actual) as $name => $table) {
            $differences[$name][] = "table is missing from {$actualLabel}";
        }
        foreach (array_diff_key($actual, $expected) as $name => $table) {
            $differences[$name][] = "table is only in {$actualLabel}";
        }

        foreach (array_intersect_key($expected, $actual) as $name => $table) {
            $lines = [];
            $other = $actual[$name];

            foreach (array_diff_key($table['columns'], $other['columns']) as $column => $definition) {
                $lines[] = "column {$column} is missing from {$actualLabel} ({$definition})";
            }
            foreach (array_diff_key($other['columns'], $table['columns']) as $column => $definition) {
                $lines[] = "column {$column} is only in {$actualLabel} ({$definition})";
            }
            foreach (array_intersect_key($table['columns'], $other['columns']) as $column => $definition) {
                if ($definition !== $other['columns'][$column]) {
                    $lines[] = "column {$column} differs: {$expectedLabel} has {$definition}; {$actualLabel} has {$other['columns'][$column]}";
                }
            }

            foreach (['indexes' => '', 'foreign_keys' => 'foreign key '] as $kind => $prefix) {
                foreach (array_diff($table[$kind], $other[$kind]) as $entry) {
                    $lines[] = "{$prefix}{$entry} is missing from {$actualLabel}";
                }
                foreach (array_diff($other[$kind], $table[$kind]) as $entry) {
                    $lines[] = "{$prefix}{$entry} is only in {$actualLabel}";
                }
            }

            if ($lines !== []) {
                $differences[$name] = $lines;
            }
        }

        ksort($differences);

        return $differences;
    }

    private static function describeColumn(object $row): string
    {
        // MySQL 8 dropped display widths ("int(11)"), but a table created on an
        // older server still reports them. tinyint(1) is kept: it marks a boolean.
        $type = preg_replace_callback(
            '/\b(tinyint|smallint|mediumint|int|bigint)\((\d+)\)/',
            fn (array $m) => $m[1] === 'tinyint' && $m[2] === '1' ? $m[0] : $m[1],
            strtolower($row->type)
        );

        $parts = [$type, $row->nullable === 'YES' ? 'null' : 'not null'];

        // text columns only; the difference that matters is latin1 or utf8mb3
        // where a fresh install has utf8mb4
        if ($row->collation !== null) {
            $parts[] = 'collate '.$row->collation;
        }

        if ($row->def !== null) {
            $parts[] = 'default '.$row->def;
        }
        // "DEFAULT_GENERATED" only says the default is an expression
        $extra = trim(str_ireplace('DEFAULT_GENERATED', '', (string) $row->extra));
        if ($extra !== '') {
            $parts[] = strtolower($extra);
        }

        return implode(' ', $parts);
    }
}
