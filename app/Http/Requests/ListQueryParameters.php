<?php

namespace App\Http\Requests;

use App\Services\SessionStore\ListParameterStore;

/**
 * Class ListQueryParameters.
 */
class ListQueryParameters
{
    /**
     * @var ListRequest
     */
    private $listRequest;

    private ListParameterStore $listParamStore;

    private ?string $defaultSortField = null;

    private array $defaultFilters = [];

    private ?string $defaultSortDirection = null;

    private ?int $defaultLimit = 25;

    private ?int $defaultPage = 1;

    /**
     * ListQueryParameters constructor.
     */
    public function __construct(ListRequest $listRequest, ListParameterStore $listParamStore)
    {
        $this->listRequest = $listRequest;
        $this->listParamStore = $listParamStore;
    }

    public function getSortFieldName(): ?string
    {
        $sortFieldName = $this->defaultSortField;
        if ($this->listRequest->getSortFieldName()) {
            $sortFieldName = $this->listRequest->getSortFieldName();
        } elseif ($this->listParamStore->getSortFieldName()) {
            $sortFieldName = $this->listParamStore->getSortFieldName();
        }
        $this->listParamStore->setSortFieldName($sortFieldName);

        return $sortFieldName;
    }

    public function getFilters(): array
    {
        $filters = $this->defaultFilters;

        if (!is_null($this->listRequest->getFilters())) {
            $filters = $this->stripEmptyFields($this->listRequest->getFilters());
            $filters = $this->stripIsEmptyField($filters);
        } elseif (!is_null($this->listParamStore->getFilters())) {
            $filters = $this->listParamStore->getFilters();
        }
        $this->listParamStore->setFilters($filters);

        return $filters;
    }

    public function getSortDirection(): ?string
    {
        $sortDirection = $this->defaultSortDirection;
        if ($this->listRequest->getSortDirection()) {
            $sortDirection = $this->listRequest->getSortDirection();
        } elseif ($this->listParamStore->getSortDirection()) {
            $sortDirection = $this->listParamStore->getSortDirection();
        }
        $this->listParamStore->setSortDirection($sortDirection);

        return $sortDirection;
    }

    public function getLimit(): int
    {
        $limit = $this->defaultLimit;
        if ($this->listRequest->getLimit()) {
            $limit = $this->listRequest->getLimit();
        } elseif ($this->listParamStore->getLimit()) {
            $limit = $this->listParamStore->getLimit();
        }
        $this->listParamStore->setLimit($limit);

        return $limit;
    }

    public function getPage(): int
    {
        if ($this->listRequest->getPage()) {
            return $this->listRequest->getPage();
        }

        return $this->defaultPage;
    }

    public function getId(): ?int
    {
        $params = $this->getRouteParams();

        if (isset($params['id'])) {
            return $params['id'];
        }

        return null;
    }

    public function getIsEmptyFilter(): bool
    {
        $isEmpty = false;
        if ($this->listRequest->getIsEmptyFilter()) {
            $isEmpty = true;
        } elseif ($this->listParamStore->getIsEmptyFilter()) {
            $isEmpty = true;
        }
        $this->listParamStore->setIsEmptyFilter($isEmpty);

        return $isEmpty;
    }

    public function getRoute(): ?string
    {
        return $this->listRequest->getRoute();
    }

    public function getRouteParams(): ?array
    {
        return $this->listRequest->getRouteParams();
    }

    private function stripEmptyFields(array $requestFilters): array
    {
        $result = [];
        foreach ($requestFilters as $filterField => $filterValue) {
            if (is_array($filterValue)) {
                $arrayFilterResult = [];
                foreach ($filterValue as $key => $value) {
                    if ('' !== $value) {
                        $arrayFilterResult[$key] = $value;
                    }
                }
                if (count($arrayFilterResult) > 0) {
                    $result[$filterField] = $arrayFilterResult;
                }
            }
            // Must compare with an empty string. empty() breaks booleans
            elseif ('' !== $filterValue) {
                $result[$filterField] = $filterValue;
            }
        }

        return $result;
    }

    private function stripIsEmptyField($requestFilters): array
    {
        if (array_key_exists(ListRequest::IS_EMPTY_FIELD_NAME, $requestFilters)) {
            unset($requestFilters[ListRequest::IS_EMPTY_FIELD_NAME]);
        }

        return $requestFilters;
    }
}
