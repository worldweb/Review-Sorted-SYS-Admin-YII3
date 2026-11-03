<?php

declare(strict_types=1);

namespace App\Domain\Account;

/**
 * Filter DTO for Account/Workspace searches.
 * Encapsulates search keywords, pagination, and sorting parameters matching Yii 2 AccountSearch.
 */
final class AccountSearchFilter
{
    public function __construct(
        public readonly string $search = '',
        public readonly ?int $status = null,
        public readonly int $page = 1,
        public readonly int $pageSize = 20,
        public readonly string $sortField = 'id',
        public readonly string $sortOrder = 'DESC',
    ) {
    }

    public static function fromQueryParams(array $params, int $defaultPageSize = 20): self
    {
        $search = isset($params['q']) && is_string($params['q']) ? trim($params['q']) : '';
        if ($search === '' && isset($params['business_name']) && is_string($params['business_name'])) {
            $search = trim($params['business_name']);
        }
        if ($search === '' && isset($params['email']) && is_string($params['email'])) {
            $search = trim($params['email']);
        }

        $status = null;
        if (isset($params['status']) && $params['status'] !== '') {
            $status = (int) $params['status'];
        }

        $page = isset($params['page']) ? max(1, (int) $params['page']) : 1;
        $pageSize = isset($params['per-page']) ? max(1, min(100, (int) $params['per-page'])) : $defaultPageSize;

        $sort = isset($params['sort']) && is_string($params['sort']) ? trim($params['sort']) : '-id';
        $sortOrder = 'ASC';
        if (str_starts_with($sort, '-')) {
            $sortOrder = 'DESC';
            $sortField = substr($sort, 1);
        } else {
            $sortField = $sort;
        }

        $allowedSorts = ['id', 'business_name', 'email', 'status', 'created_at', 'max_users'];
        if (!in_array($sortField, $allowedSorts, true)) {
            $sortField = 'id';
        }

        return new self(
            search: $search,
            status: $status,
            page: $page,
            pageSize: $pageSize,
            sortField: $sortField,
            sortOrder: $sortOrder,
        );
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->pageSize;
    }
}
