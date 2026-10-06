<?php

namespace App\Http\ResultBuilder;

use App\Filters\QueryFilter;
use App\Http\Requests\ListQueryParameters;
use App\Http\Response\ResultSet\ListResultSet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Class ListEntityResultBuilder.
 */
class ListEntityResultBuilder implements ListResultBuilderInterface
{
    private Builder $queryBuilder;

    private QueryFilter $filter;

    private ListQueryParameters $listQueryParameters;

    private array $defaultFilters = [];

    private array $fixedFilters = [];

    private array $parentFilter = [];

    private array $defaultSort;

    private ?int $defaultLimit = null;

    private array $multiSort;

    private array $allowedSortFields = [];

    /** @var array<string, string> friendly sort names => the field they sort by */
    private array $sortAliases = [];

    /** @var array<string, array<int, string>> column listings, cached per table for the request */
    private static array $tableColumns = [];

    private ?string $appliedSortField;

    private ?string $appliedSortDirection;

    private array $userFilters = [];

    private bool $isEmptyFilter = false;

    public function __construct(ListQueryParameters $listQueryParameters)
    {
        $this->listQueryParameters = $listQueryParameters;
    }

    public function setQueryBuilder(Builder $queryBuilder): ListEntityResultBuilder
    {
        $this->queryBuilder = $queryBuilder;

        return $this;
    }

    public function setParentFilter(array $parentFilter): ListEntityResultBuilder
    {
        $this->parentFilter = $parentFilter;

        return $this;
    }

    public function setFilter(QueryFilter $filterClass): ListEntityResultBuilder
    {
        $this->filter = $filterClass;

        return $this;
    }

    public function setDefaultFilters(array $defaultFilters): ListEntityResultBuilder
    {
        $this->defaultFilters = $defaultFilters;

        return $this;
    }

    public function setFixedFilters(array $fixedFilters): ListEntityResultBuilder
    {
        $this->fixedFilters = $fixedFilters;

        return $this;
    }

    public function setDefaultSort(array $defaultSort): ListEntityResultBuilder
    {
        $this->defaultSort = $defaultSort;

        return $this;
    }

    /**
     * Restrict which sort fields may reach orderBy(). A syntactically valid
     * identifier (e.g. "start_at") can still be a column that doesn't exist on
     * the current table, which crashes the query (EVENTREPO-WC). When a
     * non-empty allowlist is set, any field outside it falls back to the
     * default sort. Pass the keys of the controller's sortOptions.
     *
     * @param array<int, string> $allowedSortFields
     */
    public function setAllowedSortFields(array $allowedSortFields): ListEntityResultBuilder
    {
        $this->allowedSortFields = $allowedSortFields;

        return $this;
    }

    /**
     * Friendly sort names a list accepts, mapped to the field they sort by,
     * e.g. ['popularity' => 'popularity_score'].
     *
     * @param array<string, string> $sortAliases
     */
    public function setSortAliases(array $sortAliases): ListEntityResultBuilder
    {
        $this->sortAliases = $sortAliases;

        return $this;
    }

    public function setDefaultLimit(int $defaultLimit): ListEntityResultBuilder
    {
        $this->defaultLimit = $defaultLimit;

        return $this;
    }

    private function addFiltering(): void
    {
        // Apply parent filter - should apply and not display, cannot be overriden
        if (!empty($this->parentFilter)) {
            $this->queryBuilder = $this->filter->applyFilters($this->queryBuilder, $this->parentFilter);
        }

        // Apply user form filters
        $this->userFilters = $this->filter->normalizeFilters($this->listQueryParameters->getFilters());
        $this->isEmptyFilter = $this->listQueryParameters->getIsEmptyFilter();

        // Apply fixed filters - should display in filters and can be overridden
        if (!empty($this->fixedFilters)) {
            $this->queryBuilder = $this->filter->applyFilters($this->queryBuilder, $this->fixedFilters);
        }

        if (!empty($this->userFilters)) {
            $this->queryBuilder = $this->filter->applyFilters($this->queryBuilder, $this->userFilters);
        } elseif (!empty($this->defaultFilters) && !$this->isEmptyFilter) {
            $this->userFilters = $this->defaultFilters;
            $this->queryBuilder = $this->filter->applyFilters($this->queryBuilder, $this->userFilters);
        }
    }

    private function getQueryResult(): Builder
    {
        $this->addFiltering();
        $this->addSort();

        // adds multi-sort options after the selected sort
        $this->addMultiSort();

        // DEBUG uncomment to see the raw SQL
        // dump($this->queryBuilder->toSql());

        return $this->queryBuilder;
    }

    private function countQueryResult(): mixed
    {
        $this->addFiltering();
        $this->addSort();

        return $this->queryBuilder->getQuery()->count();
    }

    /**
     * Add the specified multi-sort order to the query builder but do not override selected sort.
     */
    private function addMultiSort(): void
    {
        if (!empty($this->multiSort)) {
            foreach ($this->multiSort as $sortOpts) {
                // only add if the applied sort does not contradict
                if ($this->appliedSortField !== $sortOpts[0]) {
                    $this->queryBuilder->orderBy($sortOpts[0], $sortOpts[1]);
                }
            }
        }
    }

    private function addSort(): void
    {
        $this->appliedSortField = $this->listQueryParameters->getSortFieldName();
        $this->appliedSortDirection = $this->listQueryParameters->getSortDirection();

        if (is_string($this->appliedSortField) && isset($this->sortAliases[$this->appliedSortField])) {
            $this->appliedSortField = $this->sortAliases[$this->appliedSortField];
        }

        // Set the default sort if no sort is provided from listQueryParameters
        if (is_null($this->appliedSortDirection) && is_null($this->appliedSortField) && 1 === count($this->defaultSort)) {
            $this->appliedSortField = array_keys($this->defaultSort)[0];
            $this->appliedSortDirection = $this->defaultSort[$this->appliedSortField];
        }

        // Guard against malformed / injected sort input reaching orderBy (e.g. a
        // stray quote -> SQLSTATE error). Fall back to the default sort column and
        // normalize the direction to a known-safe value.
        if (!$this->isValidSortField($this->appliedSortField)) {
            $this->appliedSortField = 1 === count($this->defaultSort)
                ? array_keys($this->defaultSort)[0]
                : null;
        }
        $this->appliedSortDirection = strtolower((string) $this->appliedSortDirection) === 'asc' ? 'asc' : 'desc';

        if (is_null($this->appliedSortField)) {
            return;
        }

        // If sorting by a relationship count column (e.g. follows_count), add withCount
        // A relation count sort (e.g. follows_count) needs its withCount, unless
        // the query already selects that count. Only a declared relation gets
        // one: withCount() calls the named method.
        if (str_ends_with($this->appliedSortField, '_count')
            && !str_contains($this->appliedSortField, '.')
            && !in_array($this->appliedSortField, $this->selectedAliases(), true)) {
            $relationship = substr($this->appliedSortField, 0, -strlen('_count'));

            if ($this->isRelation($this->queryBuilder->getModel(), $relationship)) {
                $this->queryBuilder->withCount($relationship);
            }
        }

        $this->queryBuilder->orderBy($this->appliedSortField, $this->appliedSortDirection);
    }

    /**
     * A sort field is only safe to pass to orderBy() if it's a plain SQL
     * identifier ("column" or "table.column") — no quotes, spaces, etc.
     */
    private function isValidSortField(?string $field): bool
    {
        if (!is_string($field)
            || preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)?$/', $field) !== 1) {
            return false;
        }

        // When an allowlist is configured, the field must be one of the columns
        // the caller actually exposes for sorting.
        if (!empty($this->allowedSortFields)) {
            return in_array($field, $this->allowedSortFields, true);
        }

        // Otherwise it must be something this query can actually order by, or a
        // valid-looking but unknown name (a scanner probe, a stale session sort,
        // "popularity" for "popularity_score") reaches orderBy() and 500s.
        return $this->isKnownSortField($field);
    }

    /**
     * A non-hidden column of the model's table or of a table the query joins, a
     * computed column the query selects, or "<relation>_count" for a relation
     * the model declares (addSort() adds the withCount).
     */
    private function isKnownSortField(string $field): bool
    {
        $model = $this->queryBuilder->getModel();

        if (!str_contains($field, '.')) {
            if (in_array($field, $this->selectedAliases(), true)) {
                return true;
            }

            if (str_ends_with($field, '_count') && $this->isRelation($model, substr($field, 0, -strlen('_count')))) {
                return true;
            }

            return $this->isVisibleColumn($model, $model->getTable(), $field);
        }

        [$table, $column] = explode('.', $field, 2);

        if ($table === $model->getTable()) {
            return $this->isVisibleColumn($model, $table, $column);
        }

        return in_array($table, $this->joinedTables(), true) && $this->hasColumn($table, $column);
    }

    private function isVisibleColumn(Model $model, string $table, string $column): bool
    {
        // never order by what the model hides (password, tokens, ...): the order leaks it
        return !in_array($column, $model->getHidden(), true) && $this->hasColumn($table, $column);
    }

    private function hasColumn(string $table, string $column): bool
    {
        $connection = $this->queryBuilder->getModel()->getConnectionName();
        $key = ($connection ?? 'default').':'.$table;

        if (!isset(self::$tableColumns[$key])) {
            self::$tableColumns[$key] = Schema::connection($connection)->getColumnListing($table);
        }

        return in_array($column, self::$tableColumns[$key], true);
    }

    /**
     * Whether the model declares $name as a relation, judged by the method's
     * declared return type: the method is never called, since the name comes
     * from the request.
     */
    private function isRelation(Model $model, string $name): bool
    {
        if ($name === '' || !method_exists($model, $name)) {
            return false;
        }

        $type = (new ReflectionMethod($model, $name))->getReturnType();

        return $type instanceof ReflectionNamedType
            && !$type->isBuiltin()
            && is_a($type->getName(), Relation::class, true);
    }

    /**
     * Plain tables the query joins (not subqueries, which have no schema to check).
     *
     * @return array<int, string>
     */
    private function joinedTables(): array
    {
        $tables = [];

        foreach ($this->queryBuilder->getQuery()->joins ?? [] as $join) {
            if ($join instanceof JoinClause && is_string($join->table) && preg_match('/^\w+$/', $join->table) === 1) {
                $tables[] = $join->table;
            }
        }

        return $tables;
    }

    /**
     * Names the query selects "as" something, e.g. popularity_score or follows_count.
     *
     * @return array<int, string>
     */
    private function selectedAliases(): array
    {
        $aliases = [];
        $grammar = $this->queryBuilder->getQuery()->getGrammar();

        foreach ($this->queryBuilder->getQuery()->columns ?? [] as $column) {
            $sql = $column instanceof Expression ? (string) $column->getValue($grammar) : (is_string($column) ? $column : '');

            if (preg_match('/\bas\s+`?(\w+)`?\s*$/i', $sql, $match) === 1) {
                $aliases[] = $match[1];
            }
        }

        return $aliases;
    }

    public function setMultiSort(array $multiSort): void
    {
        $this->multiSort = $multiSort;
    }

    /**
     * Builds the list result set for a paginated list of an entity with sorting, filters, limit.
     */
    public function listResultSetFactory(): ListResultSet
    {
        $listResult = new ListResultSet();

        // getQueryResult() must be called first otherwise sort isn't set correctly
        $listResult->setList($this->getQueryResult());

        // appliedSortField can be null when no valid sort resolves and the builder has
        // no single default column; setSort() requires a string (EVENTREPO-TB).
        $listResult->setSort($this->appliedSortField ?? '');
        $listResult->setSortDirection($this->appliedSortDirection);
        $listResult->setFilters($this->userFilters);
        $listResult->setDefaultFilters($this->defaultFilters);
        $listResult->setParentFilters($this->parentFilter);
        $listResult->setFixedFilters($this->fixedFilters);
        $listResult->setIsEmptyFilter($this->isEmptyFilter);
        $listResult->setLimit($this->listQueryParameters->getLimit($this->defaultLimit));
        $listResult->setPage($this->listQueryParameters->getPage());

        return $listResult;
    }

    /**
     * Returns a count value for the current query result.
     */
    public function countResults(): int
    {
        return $this->countQueryResult();
    }
}
