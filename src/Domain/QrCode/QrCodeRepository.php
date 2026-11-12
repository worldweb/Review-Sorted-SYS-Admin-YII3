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
 * Safe, parameterized implementation of QrCodeRepositoryInterface.
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

    public function save(array $data, ?int $id = null, ?TenantContextInterface $context = null): int
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        if ($ctx !== null && $ctx->isGuest()) {
            throw new \RuntimeException('Authentication required to save QR Code.');
        }

        $now = time();

        if ($id !== null && $id > 0) {
            $existing = $this->findById($id, $ctx);
            if ($existing === null) {
                throw new \RuntimeException('The requested QR Code does not exist or access is denied.');
            }

            $allowedFields = [
                'name', 'upload_logo', 'logo_type', 'qrcode', 'url',
                'template_name', 'default_logo_name', 'frame_name', 'total_scan', 'status'
            ];

            $updateSets = [];
            $params = [':id' => $id, ':updated_at' => $now];

            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $paramKey = ':' . $field;
                    $updateSets[] = "`$field` = $paramKey";
                    $params[$paramKey] = $data[$field];
                }
            }

            $updateSets[] = "`updated_at` = :updated_at";

            $sql = 'UPDATE {{%qrcode}} SET ' . implode(', ', $updateSets) . ' WHERE `id` = :id';
            $this->db->createCommand($sql)->bindValues($params)->execute();

            return $id;
        }

        $userId = isset($data['user_id']) ? (string) $data['user_id'] : ($ctx ? (string) $ctx->getUserId() : '');
        if ($userId === '') {
            throw new \RuntimeException('User ID is required to create a QR Code.');
        }

        $insertParams = [
            ':user_id' => $userId,
            ':name' => isset($data['name']) ? (string) $data['name'] : null,
            ':upload_logo' => isset($data['upload_logo']) && $data['upload_logo'] !== '' ? (string) $data['upload_logo'] : null,
            ':logo_type' => isset($data['logo_type']) && $data['logo_type'] !== '' ? (string) $data['logo_type'] : 'default',
            ':qrcode' => isset($data['qrcode']) && $data['qrcode'] !== '' ? (string) $data['qrcode'] : null,
            ':url' => isset($data['url']) && $data['url'] !== '' ? (string) $data['url'] : null,
            ':template_name' => isset($data['template_name']) && $data['template_name'] !== '' ? (string) $data['template_name'] : 'default',
            ':default_logo_name' => isset($data['default_logo_name']) && $data['default_logo_name'] !== '' ? (string) $data['default_logo_name'] : null,
            ':frame_name' => isset($data['frame_name']) && $data['frame_name'] !== '' ? (string) $data['frame_name'] : null,
            ':total_scan' => isset($data['total_scan']) && $data['total_scan'] !== '' ? (string) $data['total_scan'] : '0',
            ':status' => isset($data['status']) && $data['status'] !== '' ? (string) $data['status'] : QrCode::STATUS_ACTIVE,
            ':created_at' => $now,
            ':updated_at' => $now,
        ];

        $sql = 'INSERT INTO {{%qrcode}} (
            `user_id`, `name`, `upload_logo`, `logo_type`, `qrcode`, `url`,
            `template_name`, `default_logo_name`, `frame_name`, `total_scan`,
            `status`, `created_at`, `updated_at`
        ) VALUES (
            :user_id, :name, :upload_logo, :logo_type, :qrcode, :url,
            :template_name, :default_logo_name, :frame_name, :total_scan,
            :status, :created_at, :updated_at
        )';

        $this->db->createCommand($sql)->bindValues($insertParams)->execute();

        return (int) $this->db->getLastInsertID();
    }

    public function delete(int $id, ?TenantContextInterface $context = null): bool
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        if ($ctx !== null && $ctx->isGuest()) {
            return false;
        }

        $existing = $this->findById($id, $ctx);
        if ($existing === null) {
            return false;
        }

        // Cascade delete related scan logs first
        $this->db->createCommand('DELETE FROM {{%qrcode_scan}} WHERE `qrcode_id` = :id')
            ->bindValues([':id' => $id])
            ->execute();

        // Delete QR code entity
        $this->db->createCommand('DELETE FROM {{%qrcode}} WHERE `id` = :id')
            ->bindValues([':id' => $id])
            ->execute();

        return true;
    }
}

