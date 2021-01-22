<?php

namespace App\Http\Response\ResultSet;

/**
 * Contains the result of a list and describes the criteria used.
 */
class ListResultSet
{
    private $sort;

    private $defaultFilters;

    private $filters;

    private $fixedFilters;

    private $parentFilters;

    private $isEmptyFilter;

    private $limit;

    private $listResult;

    private $page;

    private $sortDirection;

    private $totalItems;

    public function setSort($sort): void
    {
        $this->sort = $sort;
    }

    public function setSortDirection($sortDirection): void
    {
        $this->sortDirection = $sortDirection;
    }

    public function setFilters($filters): void
    {
        $this->filters = $filters;
    }

    public function setDefaultFilters($defaultFilters): void
    {
        $this->defaultFilters = $defaultFilters;
    }

    public function setParentFilters($parentFilters): void
    {
        $this->parentFilters = $parentFilters;
    }

    public function setFixedFilters($fixedFilters): void
    {
        $this->fixedFilters = $fixedFilters;
    }

    public function setLimit($limit): void
    {
        $this->limit = $limit;
    }

    public function setList($listResult): void
    {
        $this->listResult = $listResult;
    }

    public function setPage($page): void
    {
        $this->page = $page;
    }

    public function setTotalItems($totalItems): void
    {
        $this->totalItems = $totalItems;
    }

    public function setIsEmptyFilter(bool $isEmptyFilter)
    {
        $this->isEmptyFilter = $isEmptyFilter;
    }

    public function getList()
    {
        return $this->listResult;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getDefaultFilters(): array
    {
        return $this->defaultFilters;
    }

    public function getFixedFilters(): array
    {
        return $this->fixedFilters;
    }

    public function getParentFilters(): array
    {
        return $this->parentFilters;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    public function getSort()
    {
        return $this->sort;
    }

    public function getSortDirection()
    {
        return $this->sortDirection;
    }

    public function getIsEmptyFilter()
    {
        return $this->isEmptyFilter;
    }
}
