<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;

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
            $this->applyFilter((string) $name, $value);
        }

        return $this->builder;
    }

    public function applyFilters(Builder $builder, array $filters): Builder
    {
        $this->builder = $builder;

        foreach ($filters as $name => $value) {
            $this->applyFilter((string) $name, $value);
        }

        return $this->builder;
    }

    public function filters(): array
    {
        return $this->request->all();
    }

    /**
     * Bring query-string filter values into the shape the filter methods take.
     *
     * Most filter methods take a string, so a repeated parameter
     * (`filters[id][]=1&filters[id][]=2`) becomes the comma form they already
     * accept, and a nested array is dropped. Methods that take arrays (`tag`,
     * `start_at`, ...) get their values unchanged. Lists run this over the
     * user's filters before showing them, so an array never reaches a view.
     *
     * @param array<array-key, mixed> $filters
     *
     * @return array<array-key, mixed>
     */
    public function normalizeFilters(array $filters): array
    {
        foreach ($filters as $name => $value) {
            $method = $this->filterMethod((string) $name);

            if ($method === null || ! is_array($value) || $this->acceptsArray($method)) {
                continue;
            }

            $joined = $this->joinScalars($value);

            if ($joined === null) {
                unset($filters[$name]);
            } else {
                $filters[$name] = $joined;
            }
        }

        return $filters;
    }

    /**
     * Call the filter method for one `filters[name]=value` pair.
     *
     * The name comes from the query string, so only public methods declared
     * by the concrete filter class count: `filters[apply]` or
     * `filters[__construct]` used to reach this class's own methods and 500.
     */
    private function applyFilter(string $name, mixed $value): void
    {
        $method = $this->filterMethod($name);

        if ($method === null) {
            return;
        }

        if (is_array($value) && ! $this->acceptsArray($method)) {
            $value = $this->joinScalars($value);

            if ($value === null) {
                return;
            }
        }

        $method->invoke($this, $value);
    }

    /**
     * @param array<array-key, mixed> $values
     */
    private function joinScalars(array $values): ?string
    {
        foreach ($values as $value) {
            if (! is_scalar($value) && $value !== null) {
                return null;
            }
        }

        return implode(',', $values);
    }

    private function filterMethod(string $name): ?ReflectionMethod
    {
        if (! method_exists($this, $name)) {
            return null;
        }

        $method = new ReflectionMethod($this, $name);

        if (! $method->isPublic() || $method->isStatic() || $method->getDeclaringClass()->getName() === self::class) {
            return null;
        }

        return $method;
    }

    private function acceptsArray(ReflectionMethod $method): bool
    {
        $type = ($method->getParameters()[0] ?? null)?->getType();

        if ($type === null) {
            return true;
        }

        $types = $type instanceof ReflectionNamedType ? [$type] : ($type instanceof ReflectionUnionType ? $type->getTypes() : []);

        foreach ($types as $t) {
            if ($t instanceof ReflectionNamedType && in_array($t->getName(), ['array', 'mixed', 'iterable'], true)) {
                return true;
            }
        }

        return false;
    }
}
