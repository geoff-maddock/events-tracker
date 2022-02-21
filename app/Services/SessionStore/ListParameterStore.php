<?php

namespace App\Services\SessionStore;

/**
 * Interface ListParamStore.
 */
interface ListParameterStore
{
    public function setFilters(?array $filters): ListParameterStore;

    public function setSortFieldName(?string $sort): ListParameterStore;

    public function setSortDirection(?string $sortDirection): ListParameterStore;

    public function setLimit(?int $limit): ListParameterStore;

    /**
     * @return mixed
     */
    public function setIsEmptyFilter(bool $isEmpty);

    /**
     * @return mixed
     */
    public function getFilters();

    /**
     * @return mixed
     */
    public function getSortFieldName();

    /**
     * @return mixed
     */
    public function getSortDirection();

    /**
     * @return mixed
     */
    public function getLimit();

    /**
     * @return mixed
     */
    public function getIsEmptyFilter();

    /**
     * @return mixed
     */
    public function save();
}
