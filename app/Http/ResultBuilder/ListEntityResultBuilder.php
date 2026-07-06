<?php

namespace App\Http\ResultBuilder;

use App\Filters\QueryFilter;
use App\Http\Requests\ListQueryParameters;
use App\Http\Response\ResultSet\ListResultSet;
use Illuminate\Database\Eloquent\Builder;

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
        $this->userFilters = $this->listQueryParameters->getFilters();
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
        if (str_ends_with($this->appliedSortField, '_count')) {
            $relationship = str_replace('_count', '', $this->appliedSortField);
            $this->queryBuilder->withCount($relationship);
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
        // the caller actually exposes for sorting; otherwise a valid-looking but
        // non-existent column (e.g. a stale session sort) would reach orderBy().
        if (!empty($this->allowedSortFields)) {
            return in_array($field, $this->allowedSortFields, true);
        }

        return true;
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
