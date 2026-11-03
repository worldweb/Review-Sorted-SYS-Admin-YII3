<?php

declare(strict_types=1);

namespace App\Domain\User;

/**
 * Filter DTO for user searches.
 * Encapsulates search keywords, pagination, and sorting parameters matching Yii 2 UserSearch.
 */
final class UserSearchFilter
{
    public function __construct(
        public readonly string $search = '',
        public readonly ?int $status = null,
        public readonly ?string $role = null,
        public readonly ?int $accountId = null,
        public readonly int $page = 1,
        public readonly int $pageSize = 20,
        public readonly string $sortField = 'id',
        public readonly string $sortOrder = 'ASC',
    ) {
    }

    public static function fromQueryParams(array $params, int $defaultPageSize = 20): self
    {
        $search = isset($params['q']) && is_string($params['q']) ? trim($params['q']) : '';
        if ($search === '' && isset($params['name']) && is_string($params['name'])) {
            $search = trim($params['name']);
        }
        if ($search === '' && isset($params['email']) && is_string($params['email'])) {
            $search = trim($params['email']);
        }

        $status = null;
        if (isset($params['status']) && $params['status'] !== '') {
            $status = (int) $params['status'];
        }

        $role = null;
        if (isset($params['role']) && is_string($params['role']) && $params['role'] !== '') {
            $role = trim($params['role']);
        }

        $accountId = null;
        if (isset($params['account_id']) && $params['account_id'] !== '') {
            $accountId = (int) $params['account_id'];
        }

        $page = isset($params['page']) ? max(1, (int) $params['page']) : 1;
        $pageSize = isset($params['per-page']) ? max(1, min(100, (int) $params['per-page'])) : $defaultPageSize;

        $sort = isset($params['sort']) && is_string($params['sort']) ? trim($params['sort']) : 'id';
        $sortOrder = 'ASC';
        if (str_starts_with($sort, '-')) {
            $sortOrder = 'DESC';
            $sortField = substr($sort, 1);
        } else {
            $sortField = $sort;
        }

        $allowedSorts = ['id', 'name', 'email', 'status', 'created_at'];
        if (!in_array($sortField, $allowedSorts, true)) {
            $sortField = 'id';
        }

        return new self(
            search: $search,
            status: $status,
            role: $role,
            accountId: $accountId,
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
