# Advanced Filter System - Developer Guide

## Overview

The advanced filter system allows API users to construct complex queries using a SQL-like syntax. This document explains the implementation details for developers working on or extending the system.

## Architecture

### Components

1. **FilterQueryParser** (`app/Services/FilterQueryParser.php`)
   - Parses filter query strings into an abstract syntax tree (AST)
   - Handles tokenization, operator parsing, and nested conditions
   - Returns structured arrays representing the query

2. **FilterQueryApplier** (`app/Services/FilterQueryApplier.php`)
   - Applies parsed filter structures to Eloquent Query Builder
   - Translates custom operators to SQL operators
   - Handles nested AND/OR logic with proper query builder scoping

3. **ListRequest** (`app/Http/Requests/ListRequest.php`)
   - Detects filter format (string vs array)
   - Provides methods to check format and retrieve filter data

4. **ListQueryParameters** (`app/Http/Requests/ListQueryParameters.php`)
   - Coordinates between ListRequest and filter application
   - Determines whether to use advanced or legacy filtering

5. **QueryFilter** (`app/Filters/QueryFilter.php`)
   - Abstract base class for all filter classes
   - Contains the `applyAdvancedFilter()` method
   - Handles error logging for malformed queries

6. **ListEntityResultBuilder** (`app/Http/ResultBuilder/ListEntityResultBuilder.php`)
   - Integrates advanced filtering into the query building pipeline
   - Applies advanced filters before legacy filters

## Data Flow

```
HTTP Request
    ↓
ListRequest::getFilters()
    ↓
ListQueryParameters::hasAdvancedFilters()
    ↓
[Advanced Path]                    [Legacy Path]
    ↓                                  ↓
FilterQueryParser::parse()         Filter methods
    ↓                                  ↓
FilterQueryApplier::apply()        QueryFilter::applyFilters()
    ↓                                  ↓
Eloquent Query Builder ←──────────────┘
    ↓
SQL Query
    ↓
Results
```

## Filter AST Structure

The parser converts filter strings into structured arrays:

### Simple Condition
```php
[
    'type' => 'condition',
    'column' => 'events.name',
    'operator' => 'EQ',
    'value' => 'Rock Concert'
]
```

### Logical Group
```php
[
    'type' => 'AND',  // or 'OR'
    'conditions' => [
        [
            'type' => 'condition',
            'column' => 'events.name',
            'operator' => 'LIKE',
            'value' => '%Rock%'
        ],
        [
            'type' => 'condition',
            'column' => 'events.min_age',
            'operator' => 'GTE',
            'value' => 21
        ]
    ]
]
```

### Nested Groups
```php
[
    'type' => 'AND',
    'conditions' => [
        [
            'type' => 'OR',
            'conditions' => [
                [...],  // condition 1
                [...]   // condition 2
            ]
        ],
        [
            'type' => 'condition',
            'column' => '...',
            'operator' => '...',
            'value' => '...'
        ]
    ]
]
```

## Extending the System

### Adding New Operators

1. Add operator to `FilterQueryParser::OPERATORS`:
```php
private const OPERATORS = ['EQ', 'NEQ', 'GT', 'GTE', 'LT', 'LTE', 'LIKE', 'IN', 'NOT IN', 'BETWEEN'];
```

2. Add parsing logic in `parseCondition()`:
```php
if ($operator === 'BETWEEN') {
    if (preg_match('/^(.+?)\s+BETWEEN\s+(.+?)\s+AND\s+(.+?)$/i', $condition, $matches)) {
        $column = trim($matches[1]);
        $value1 = $this->parseValue(trim($matches[2]));
        $value2 = $this->parseValue(trim($matches[3]));
        
        return [
            'type' => 'condition',
            'column' => $column,
            'operator' => 'BETWEEN',
            'value' => [$value1, $value2]
        ];
    }
}
```

3. Add application logic in `FilterQueryApplier::applyCondition()`:
```php
case 'BETWEEN':
    if (!is_array($value) || count($value) !== 2) {
        throw new InvalidArgumentException('BETWEEN operator requires an array of 2 values');
    }
    return $builder->whereBetween($column, $value);
```

### Adding Custom Column Handlers

For complex column handling (e.g., relationship filtering), extend `FilterQueryApplier`:

```php
protected function applyCondition(Builder $builder, array $condition): Builder
{
    $column = $condition['column'];
    
    // Handle relationship filters
    if (str_contains($column, '.')) {
        [$relation, $field] = explode('.', $column, 2);
        
        return $builder->whereHas($relation, function ($query) use ($field, $condition) {
            $newCondition = $condition;
            $newCondition['column'] = $field;
            $this->applyCondition($query, $newCondition);
        });
    }
    
    // ... rest of method
}
```

### Custom Filter Classes

Create filter classes extending `QueryFilter` for specific models:

```php
class MyCustomFilters extends QueryFilter
{
    // Legacy method-based filters (still work)
    public function name(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('name', 'like', '%'.$value.'%');
        }
        return $this->builder;
    }
    
    // Advanced filters work automatically through parent class
}
```

## Testing

### Unit Tests

Test the parser in isolation:

```php
public function testParseComplexQuery()
{
    $parser = new FilterQueryParser();
    $result = $parser->parse('(col1 EQ val1 OR col2 EQ val2) AND col3 GT 5');
    
    $this->assertEquals('AND', $result['type']);
    $this->assertCount(2, $result['conditions']);
}
```

### Feature Tests

Test end-to-end API functionality:

```php
public function testAdvancedFilteringApi()
{
    $event = Event::factory()->create(['name' => 'Test Event']);
    
    $response = $this->getJson('/api/events?filters=events.name EQ "Test Event"');
    
    $response->assertStatus(200)
        ->assertJsonFragment(['name' => 'Test Event']);
}
```

## Error Handling

The system handles errors gracefully:

1. **Invalid Syntax**: `InvalidArgumentException` thrown by parser
2. **Error Logging**: Caught in `QueryFilter::applyAdvancedFilter()` and logged
3. **Fallback**: On error, returns unfiltered query (all results)

Example log entry:
```
[timestamp] local.WARNING: Invalid filter query: Invalid condition: unknown operator
{'query': 'name XYZ value'}
```

## Performance Considerations

1. **Query Complexity**: Deeply nested conditions create complex SQL. Monitor query performance.

2. **Index Usage**: Ensure filtered columns have appropriate database indexes:
```sql
CREATE INDEX idx_events_name ON events(name);
CREATE INDEX idx_events_min_age ON events(min_age);
```

3. **LIKE Queries**: Leading wildcards (`%value`) prevent index usage. Consider full-text search for better performance.

4. **Relationship Filters**: Use eager loading when filtering by relationships:
```php
$query->with(['venue', 'eventType']);
```

## Security Considerations

1. **SQL Injection**: The system uses Eloquent's parameter binding, which prevents SQL injection.

2. **Column Whitelisting**: Consider implementing column whitelisting for production:
```php
private const ALLOWED_COLUMNS = [
    'events.name',
    'events.min_age',
    'events.door_price',
    // ...
];

private function validateColumn(string $column): void
{
    if (!in_array($column, self::ALLOWED_COLUMNS)) {
        throw new InvalidArgumentException("Column not allowed: {$column}");
    }
}
```

3. **Query Complexity**: Implement limits on query complexity to prevent DoS:
```php
private function validateComplexity(array $parsed, int $depth = 0): void
{
    if ($depth > 5) {
        throw new InvalidArgumentException('Query too complex (max depth: 5)');
    }
    
    if ($parsed['type'] !== 'condition') {
        foreach ($parsed['conditions'] as $condition) {
            $this->validateComplexity($condition, $depth + 1);
        }
    }
}
```

## Backward Compatibility

The legacy array-based filter format is fully supported:

```php
// Legacy format (still works)
GET /api/events?filters[name]=Concert&filters[min_age]=18

// New format
GET /api/events?filters=events.name LIKE %Concert% AND events.min_age GTE 18
```

Both formats can be used simultaneously in different requests, but not in the same request.

## Debugging

Enable query logging to see generated SQL:

```php
DB::enableQueryLog();
// ... make request ...
dd(DB::getQueryLog());
```

Add debug output in `FilterQueryApplier`:

```php
public function apply(Builder $builder, array $parsedFilter): Builder
{
    \Log::debug('Applying filter', [
        'parsed' => $parsedFilter,
        'sql_before' => $builder->toSql()
    ]);
    
    $result = $this->applyFilter($builder, $parsedFilter);
    
    \Log::debug('Filter applied', ['sql_after' => $result->toSql()]);
    
    return $result;
}
```

## Future Enhancements

Potential improvements for future versions:

1. **Operator Aliases**: Support `=` as alias for `EQ`, `!=` for `NEQ`, etc.
2. **Case Insensitivity**: Add `ILIKE` operator for case-insensitive matching
3. **Date Functions**: Support `DATE(column)`, `YEAR(column)`, etc.
4. **Aggregations**: Support `COUNT`, `SUM`, etc. in filters
5. **Subqueries**: Support nested SELECT queries
6. **Query Validation**: Pre-validate queries before execution
7. **Query Caching**: Cache parsed queries for repeated use
8. **Query Builder UI**: Provide a visual query builder in the frontend

## Contributing

When contributing to this system:

1. Add unit tests for any new operators or features
2. Add feature tests for API endpoints
3. Update documentation (this file and `api-advanced-filters.md`)
4. Ensure backward compatibility
5. Consider performance implications
6. Follow existing code style and patterns
