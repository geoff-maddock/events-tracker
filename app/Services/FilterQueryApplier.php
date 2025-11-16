<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

/**
 * Applies parsed filter queries to Eloquent Query Builder.
 */
class FilterQueryApplier
{
    /**
     * Apply parsed filter structure to query builder.
     *
     * @param Builder $builder The query builder instance
     * @param array $parsedFilter The parsed filter structure
     * @return Builder The modified query builder
     */
    public function apply(Builder $builder, array $parsedFilter): Builder
    {
        if (empty($parsedFilter)) {
            return $builder;
        }
        
        return $this->applyFilter($builder, $parsedFilter);
    }
    
    /**
     * Apply a filter (condition or group) to the builder.
     */
    private function applyFilter(Builder $builder, array $filter): Builder
    {
        if (!isset($filter['type'])) {
            throw new InvalidArgumentException('Filter must have a type');
        }
        
        if ($filter['type'] === 'condition') {
            return $this->applyCondition($builder, $filter);
        }
        
        // It's a logical group (AND/OR)
        return $this->applyLogicalGroup($builder, $filter);
    }
    
    /**
     * Apply a single condition to the builder.
     */
    private function applyCondition(Builder $builder, array $condition): Builder
    {
        $column = $condition['column'];
        $operator = $condition['operator'];
        $value = $condition['value'];
        
        // Map custom operators to SQL operators
        switch ($operator) {
            case 'EQ':
                return $builder->where($column, '=', $value);
                
            case 'NEQ':
                return $builder->where($column, '!=', $value);
                
            case 'GT':
                return $builder->where($column, '>', $value);
                
            case 'GTE':
                return $builder->where($column, '>=', $value);
                
            case 'LT':
                return $builder->where($column, '<', $value);
                
            case 'LTE':
                return $builder->where($column, '<=', $value);
                
            case 'LIKE':
                return $builder->where($column, 'LIKE', $value);
                
            case 'IN':
                if (!is_array($value)) {
                    throw new InvalidArgumentException('IN operator requires an array of values');
                }
                return $builder->whereIn($column, $value);
                
            case 'NOT IN':
                if (!is_array($value)) {
                    throw new InvalidArgumentException('NOT IN operator requires an array of values');
                }
                return $builder->whereNotIn($column, $value);
                
            default:
                throw new InvalidArgumentException("Unsupported operator: {$operator}");
        }
    }
    
    /**
     * Apply a logical group (AND/OR) to the builder.
     */
    private function applyLogicalGroup(Builder $builder, array $group): Builder
    {
        $type = $group['type'];
        $conditions = $group['conditions'] ?? [];
        
        if (empty($conditions)) {
            return $builder;
        }
        
        if ($type === 'AND') {
            // Apply each condition with AND logic
            foreach ($conditions as $condition) {
                $builder->where(function ($query) use ($condition) {
                    $this->applyFilter($query, $condition);
                });
            }
        } elseif ($type === 'OR') {
            // Apply conditions with OR logic
            $builder->where(function ($query) use ($conditions) {
                foreach ($conditions as $i => $condition) {
                    if ($i === 0) {
                        $this->applyFilter($query, $condition);
                    } else {
                        $query->orWhere(function ($subQuery) use ($condition) {
                            $this->applyFilter($subQuery, $condition);
                        });
                    }
                }
            });
        } else {
            throw new InvalidArgumentException("Unsupported logical operator: {$type}");
        }
        
        return $builder;
    }
}
