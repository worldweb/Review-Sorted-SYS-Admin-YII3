<?php

declare(strict_types=1);

namespace App\Domain\QrCode;

use App\Security\TenantContextInterface;
use App\Security\TenantContextProviderInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;

/**
 * QR Code Scan Analytics Repository.
 * Safe, parameterized implementation of QrScanRepositoryInterface.
 * Strictly enforces TenantContext isolation across all scan queries by joining QR ownership.
 */
final class QrScanRepository implements QrScanRepositoryInterface
{
    public function __construct(
        private ConnectionInterface $db,
        private ?TenantContextProviderInterface $tenantContextProvider = null,
    ) {
    }

    public function getTotalScans(int $qrCodeId, ?TenantContextInterface $context = null): int
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        if ($ctx !== null && $ctx->isGuest()) {
            return 0;
        }

        $query = (new Query($this->db))
            ->from(['qs' => '{{%qrcode_scan}}'])
            ->innerJoin(['q' => '{{%qrcode}}'], 'q.id = qs.qrcode_id')
            ->leftJoin(['u' => '{{%user}}'], 'u.id = q.user_id')
            ->where(['qs.qrcode_id' => $qrCodeId])
            ->andWhere(['>=', 'qs.created_at', new Expression('q.created_at')]);

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $targetAccountId = $ctx->getPrimaryAccountId();
            if ($targetAccountId === null || $targetAccountId <= 0) {
                return 0;
            }
            $query->andWhere(['u.account_id' => $targetAccountId]);
        }

        return (int) $query->count('*');
    }

    public function getTodayScans(
        int $qrCodeId,
        ?\DateTimeZone $timeZone = null,
        ?TenantContextInterface $context = null
    ): int {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        if ($ctx !== null && $ctx->isGuest()) {
            return 0;
        }

        $tz = $timeZone ?? new \DateTimeZone(date_default_timezone_get());
        $now = new \DateTimeImmutable('now', $tz);
        $todayStart = $now->setTime(0, 0, 0)->getTimestamp();
        $tomorrowStart = $now->setTime(0, 0, 0)->modify('+1 day')->getTimestamp();

        $query = (new Query($this->db))
            ->from(['qs' => '{{%qrcode_scan}}'])
            ->innerJoin(['q' => '{{%qrcode}}'], 'q.id = qs.qrcode_id')
            ->leftJoin(['u' => '{{%user}}'], 'u.id = q.user_id')
            ->where(['qs.qrcode_id' => $qrCodeId])
            ->andWhere(['>=', 'qs.created_at', new Expression('q.created_at')])
            ->andWhere(['>=', 'qs.created_at', $todayStart])
            ->andWhere(['<', 'qs.created_at', $tomorrowStart]);

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $targetAccountId = $ctx->getPrimaryAccountId();
            if ($targetAccountId === null || $targetAccountId <= 0) {
                return 0;
            }
            $query->andWhere(['u.account_id' => $targetAccountId]);
        }

        return (int) $query->count('*');
    }

    public function getLastScanAt(int $qrCodeId, ?TenantContextInterface $context = null): ?int
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        if ($ctx !== null && $ctx->isGuest()) {
            return null;
        }

        $query = (new Query($this->db))
            ->select([
                'last_scan' => new Expression('MAX(COALESCE(qs.updated_at, qs.created_at))'),
            ])
            ->from(['qs' => '{{%qrcode_scan}}'])
            ->innerJoin(['q' => '{{%qrcode}}'], 'q.id = qs.qrcode_id')
            ->leftJoin(['u' => '{{%user}}'], 'u.id = q.user_id')
            ->where(['qs.qrcode_id' => $qrCodeId])
            ->andWhere(['>=', 'qs.created_at', new Expression('q.created_at')]);

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $targetAccountId = $ctx->getPrimaryAccountId();
            if ($targetAccountId === null || $targetAccountId <= 0) {
                return null;
            }
            $query->andWhere(['u.account_id' => $targetAccountId]);
        }

        $val = $query->scalar();
        if ($val === null || $val === false || $val === '') {
            return null;
        }

        return (int) $val;
    }

    public function getAnalyticsSummary(
        int $qrCodeId,
        ?\DateTimeZone $timeZone = null,
        ?TenantContextInterface $context = null
    ): array {
        return [
            'totalScans' => $this->getTotalScans($qrCodeId, $context),
            'todayScans' => $this->getTodayScans($qrCodeId, $timeZone, $context),
            'lastScanAt' => $this->getLastScanAt($qrCodeId, $context),
        ];
    }

    public function getScansByQrCodeId(
        int $qrCodeId,
        int $page = 1,
        int $pageSize = 20,
        ?TenantContextInterface $context = null
    ): array {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        if ($ctx !== null && $ctx->isGuest()) {
            return ['items' => [], 'total' => 0];
        }

        $query = (new Query($this->db))
            ->from(['qs' => '{{%qrcode_scan}}'])
            ->innerJoin(['q' => '{{%qrcode}}'], 'q.id = qs.qrcode_id')
            ->leftJoin(['u' => '{{%user}}'], 'u.id = q.user_id')
            ->where(['qs.qrcode_id' => $qrCodeId])
            ->andWhere(['>=', 'qs.created_at', new Expression('q.created_at')]);

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $targetAccountId = $ctx->getPrimaryAccountId();
            if ($targetAccountId === null || $targetAccountId <= 0) {
                return ['items' => [], 'total' => 0];
            }
            $query->andWhere(['u.account_id' => $targetAccountId]);
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
                'qs.*',
                'qrcode_name' => 'q.name',
                'qrcode_url' => 'q.url',
            ])
            ->orderBy([new Expression('COALESCE(qs.updated_at, qs.created_at) DESC')])
            ->limit($effectivePageSize)
            ->offset($offset)
            ->all();

        $items = array_map(static fn (array $row) => QrScan::fromDatabaseRow($row), $rows);

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    public function recordScan(int $qrCodeId, string $userIp): bool
    {
        try {
            $now = time();
            $this->db->createCommand()->insert('{{%qrcode_scan}}', [
                'qrcode_id' => $qrCodeId,
                'user_ip' => $userIp,
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
