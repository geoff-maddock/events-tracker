<?php

namespace App\Http\Response\ResultSet;

use Illuminate\Database\Eloquent\Builder;

/**
 * Contains the result of a list and describes the criteria used.
 */
class ListResultSet
{
    private string $sort;

    private array $defaultFilters;

    private array $filters;

    private array $fixedFilters;

    private array $parentFilters;

    private bool $isEmptyFilter;

    private ?int $limit;

    private Builder $listResult;

    private int $page;

    private string $sortDirection;

    private int $totalItems;

    public function setSort(string $sort): void
    {
        $this->sort = $sort;
    }

    public function setSortDirection(string $sortDirection): void
    {
        $this->sortDirection = $sortDirection;
    }

    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    public function setDefaultFilters(array $defaultFilters): void
    {
        $this->defaultFilters = $defaultFilters;
    }

    public function setParentFilters(array $parentFilters): void
    {
        $this->parentFilters = $parentFilters;
    }

    public function setFixedFilters(array $fixedFilters): void
    {
        $this->fixedFilters = $fixedFilters;
    }

    public function setLimit(?int $limit): void
    {
        $this->limit = $limit;
    }

    public function setList(Builder $listResult): void
    {
        $this->listResult = $listResult;
    }

    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    public function setTotalItems(int $totalItems): void
    {
        $this->totalItems = $totalItems;
    }

    public function setIsEmptyFilter(bool $isEmptyFilter): void
    {
        $this->isEmptyFilter = $isEmptyFilter;
    }

    public function getList(): Builder
    {
        return $this->listResult;
    }

    public function getLimit(): ?int
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

    public function getSort(): string
    {
        return $this->sort;
    }

    public function getSortDirection(): string
    {
        return $this->sortDirection;
    }

    public function getIsEmptyFilter(): bool
    {
        return $this->isEmptyFilter;
    }
}
