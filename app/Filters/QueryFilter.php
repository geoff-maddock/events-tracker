<?php

namespace App\Filters;

use App\Services\FilterQueryParser;
use App\Services\FilterQueryApplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class QueryFilter
{
    protected Request $request;

    protected Builder $builder;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        foreach ($this->filters() as $name => $value) {
            if (method_exists($this, $name)) {
                call_user_func_array([$this, $name], [$value]);
            }
        }

        return $this->builder;
    }

    public function applyFilters(Builder $builder, array $filters): Builder
    {
        $this->builder = $builder;

        foreach ($filters as $name => $value) {
            if (method_exists($this, $name)) {
                call_user_func_array([$this, $name], [$value]);
            }
        }

        return $this->builder;
    }

    /**
     * Apply advanced filter query string to the builder.
     *
     * @param Builder $builder The query builder instance
     * @param string $filterQuery The advanced filter query string
     * @return Builder The modified query builder
     */
    public function applyAdvancedFilter(Builder $builder, string $filterQuery): Builder
    {
        $parser = new FilterQueryParser();
        $applier = new FilterQueryApplier();
        
        try {
            $parsedFilter = $parser->parse($filterQuery);
            return $applier->apply($builder, $parsedFilter);
        } catch (\InvalidArgumentException $e) {
            // Log the error and return builder unchanged
            \Log::warning('Invalid filter query: ' . $e->getMessage(), [
                'query' => $filterQuery
            ]);
            return $builder;
        }
    }

    public function filters(): array
    {
        return $this->request->all();
    }
}
