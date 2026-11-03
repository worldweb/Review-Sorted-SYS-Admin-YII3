<?php

declare(strict_types=1);

namespace App\Domain\QrCode;

use App\Security\TenantContextInterface;
use App\Security\TenantContextProviderInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;

/**
 * QR Code Repository.
 * Safe, parameterized, read-only implementation of QrCodeRepositoryInterface.
 * Strictly enforces TenantContext isolation across all QR code queries.
 */
final class QrCodeRepository implements QrCodeRepositoryInterface
{
    public function __construct(
        private ConnectionInterface $db,
        private ?TenantContextProviderInterface $tenantContextProvider = null,
    ) {
    }

    public function findById(int $id, ?TenantContextInterface $context = null): ?QrCode
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        if ($ctx !== null && $ctx->isGuest()) {
            return null;
        }

        $query = (new Query($this->db))
            ->select([
                'q.*',
                'account_id' => 'u.account_id',
                'creator_name' => 'u.name',
                'creator_username' => 'u.name',
                'creator_email' => 'u.email',
                'scan_count' => new Expression('(SELECT COUNT(*) FROM {{%qrcode_scan}} qs WHERE qs.qrcode_id = q.id)'),
            ])
            ->from(['q' => '{{%qrcode}}'])
            ->leftJoin(['u' => '{{%user}}'], 'u.id = q.user_id')
            ->where(['q.id' => $id]);

        // Tenant Boundary: Regular users or scoped admins must match account_id
        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $targetAccountId = $ctx->getPrimaryAccountId();
            if ($targetAccountId === null || $targetAccountId <= 0) {
                return null;
            }
            $query->andWhere(['u.account_id' => $targetAccountId]);
        }

        $row = $query->one();
        if ($row === null || $row === false) {
            return null;
        }

        // Secondary defense-in-depth verification on account accessibility
        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $rowAccountId = isset($row['account_id']) && $row['account_id'] !== null ? (int) $row['account_id'] : null;
            if ($rowAccountId === null || !$ctx->canAccessAccount($rowAccountId)) {
                return null;
            }
        }

        return QrCode::fromDatabaseRow($row);
    }

    public function search(
        ?string $search = null,
        int $page = 1,
        int $pageSize = 20,
        ?TenantContextInterface $context = null
    ): array {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        if ($ctx !== null && $ctx->isGuest()) {
            return ['items' => [], 'total' => 0];
        }

        $query = (new Query($this->db))
            ->from(['q' => '{{%qrcode}}'])
            ->leftJoin(['u' => '{{%user}}'], 'u.id = q.user_id');

        // Tenant isolation
        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $targetAccountId = $ctx->getPrimaryAccountId();
            if ($targetAccountId === null || $targetAccountId <= 0) {
                return ['items' => [], 'total' => 0];
            }
            $query->andWhere(['u.account_id' => $targetAccountId]);
        }

        // Search filter on QR name
        if ($search !== null && trim($search) !== '') {
            $query->andWhere(['like', 'q.name', trim($search)]);
        }

        $total = (int) $query->count('*');
        if ($total === 0) {
            return ['items' => [], 'total' => 0];
        }

        $effectivePage = max(1, $page);
        $effectivePageSize = max(1, min(100, $pageSize));
        $offset = ($effectivePage - 1) * $effectivePageSize;

        $rows = $query
            ->select([
                'q.*',
                'account_id' => 'u.account_id',
                'creator_name' => 'u.name',
                'creator_username' => 'u.name',
                'creator_email' => 'u.email',
                'scan_count' => new Expression('(SELECT COUNT(*) FROM {{%qrcode_scan}} qs WHERE qs.qrcode_id = q.id)'),
            ])
            ->orderBy(['q.id' => SORT_DESC])
            ->limit($effectivePageSize)
            ->offset($offset)
            ->all();

        $items = array_map(static fn (array $row) => QrCode::fromDatabaseRow($row), $rows);

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    public function count(?string $search = null, ?TenantContextInterface $context = null): int
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        if ($ctx !== null && $ctx->isGuest()) {
            return 0;
        }

        $query = (new Query($this->db))
            ->from(['q' => '{{%qrcode}}'])
            ->leftJoin(['u' => '{{%user}}'], 'u.id = q.user_id');

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $targetAccountId = $ctx->getPrimaryAccountId();
            if ($targetAccountId === null || $targetAccountId <= 0) {
                return 0;
            }
            $query->andWhere(['u.account_id' => $targetAccountId]);
        }

        if ($search !== null && trim($search) !== '') {
            $query->andWhere(['like', 'q.name', trim($search)]);
        }

        return (int) $query->count('*');
    }

    public function findByAccountId(int $accountId, ?TenantContextInterface $context = null): array
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        if ($ctx !== null) {
            if ($ctx->isGuest() || !$ctx->canAccessAccount($accountId)) {
                return [];
            }
        }

        $query = (new Query($this->db))
            ->select([
                'q.*',
                'account_id' => 'u.account_id',
                'creator_name' => 'u.name',
                'creator_username' => 'u.name',
                'creator_email' => 'u.email',
                'scan_count' => new Expression('(SELECT COUNT(*) FROM {{%qrcode_scan}} qs WHERE qs.qrcode_id = q.id)'),
            ])
            ->from(['q' => '{{%qrcode}}'])
            ->leftJoin(['u' => '{{%user}}'], 'u.id = q.user_id')
            ->where(['u.account_id' => $accountId])
            ->orderBy(['q.id' => SORT_DESC]);

        $rows = $query->all();

        return array_map(static fn (array $row) => QrCode::fromDatabaseRow($row), $rows);
    }
}
