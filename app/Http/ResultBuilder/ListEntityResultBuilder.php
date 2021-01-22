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
    private $queryBuilder;

    private $filter;

    private $paginator;

    private $filterOptions = [];

    private $listQueryParameters;

    private $defaultFilters = [];

    private $fixedFilters = [];

    private $parentFilter = [];

    private $defaultSort;

    private $multiSort;

    private $appliedSortField;

    private $appliedSortDirection;

    private $userFilters = [];

    private $isEmptyFilter = false;

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

    public function setFilterOptions(array $filterOptions): ListEntityResultBuilder
    {
        $this->filterOptions = $filterOptions;

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

    private function addFiltering()
    {
        // Apply parent filter
        if (!empty($this->parentFilter)) {
            $this->queryBuilder = $this->filter->apply($this->queryBuilder, $this->parentFilter);
        }

        // Apply fixed filters
        if (!empty($this->fixedFilters)) {
            $this->queryBuilder = $this->filter->apply($this->queryBuilder, $this->fixedFilters);
        }
        // Apply user form filters
        $this->userFilters = $this->listQueryParameters->getFilters();
        $this->isEmptyFilter = $this->listQueryParameters->getIsEmptyFilter();

        if (!empty($this->userFilters)) {
            $this->queryBuilder = $this->filter->apply($this->queryBuilder, $this->userFilters);
        } elseif (!empty($this->defaultFilters) && !$this->isEmptyFilter) {
            $this->userFilters = $this->defaultFilters;
            $this->queryBuilder = $this->filter->apply($this->queryBuilder, $this->userFilters);
        }
    }

    private function getQueryResult()
    {
        $this->addFiltering();
        $this->addSort();

        // adds multi-sort options after the selected sort
        $this->addMultiSort();

        return $this->queryBuilder;
        // // refactor this as it doesn't use the paginator
        // return $this->paginator->paginate(
        //     $this->queryBuilder,
        //     $this->listQueryParameters->getPage(),
        //     $this->listQueryParameters->getLimit(),
        //     [
        //         'defaultSortFieldName' => $this->appliedSortField,
        //         'defaultSortDirection' => $this->appliedSortDirection,
        //         'wrap-queries' => true,
        //     ]
        // );
    }

    private function countQueryResult()
    {
        $this->addFiltering();
        $this->addSort();

        return $this->queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * Add the specified multi-sort order to the query builder but do not override selected sort.
     */
    private function addMultiSort()
    {
        if (!empty($this->multiSort)) {
            foreach ($this->multiSort as $sortOpts) {
                // only add if the applied sort does not contradict
                if ($this->appliedSortField !== $sortOpts[0]) {
                    $this->queryBuilder->addOrderBy($sortOpts[0], $sortOpts[1]);
                }
            }
        }
    }

    private function addSort()
    {
        $this->appliedSortField = $this->listQueryParameters->getSortFieldName();
        $this->appliedSortDirection = $this->listQueryParameters->getSortDirection();

        // Set the default sort if no sort is provided from listQueryParameters
        if (is_null($this->appliedSortDirection) && is_null($this->appliedSortField) && 1 === count($this->defaultSort)) {
            $this->appliedSortField = array_keys($this->defaultSort)[0];
            $this->appliedSortDirection = $this->defaultSort[$this->appliedSortField];
        }
        $this->queryBuilder->orderBy($this->appliedSortField, $this->appliedSortDirection);
    }

    public function setMultiSort($multiSort)
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

        $listResult->setSort($this->appliedSortField);
        $listResult->setSortDirection($this->appliedSortDirection);
        $listResult->setFilters($this->userFilters);
        $listResult->setDefaultFilters($this->defaultFilters);
        $listResult->setParentFilters($this->parentFilter);
        $listResult->setFixedFilters($this->fixedFilters);
        $listResult->setIsEmptyFilter($this->isEmptyFilter);
        $listResult->setLimit($this->listQueryParameters->getLimit());
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
