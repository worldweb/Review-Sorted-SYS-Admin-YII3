<?php

declare(strict_types=1);

namespace App\Domain\LocationGroup;

use App\Security\TenantContextInterface;
use App\Security\TenantContextProviderInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;

/**
 * Repository implementation for Location Group domain.
 * Strictly enforces tenant context isolation and safe prepared queries.
 */
final class LocationGroupRepository implements LocationGroupRepositoryInterface
{
    public function __construct(
        private ConnectionInterface $db,
        private ?TenantContextProviderInterface $tenantContextProvider = null,
    ) {
    }

    public function findById(int $id, ?TenantContextInterface $context = null): ?LocationGroup
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        if ($ctx !== null && $ctx->isGuest()) {
            return null;
        }

        $query = (new Query($this->db))
            ->select([
                'lg.*',
                'account_business_name' => 'a.business_name',
                'destination_count' => new Expression('(SELECT COUNT(*) FROM {{%location_group_service_config}} lgsc WHERE lgsc.group_id = lg.id)'),
            ])
            ->from(['lg' => '{{%location_group}}'])
            ->leftJoin(['a' => '{{%account}}'], 'a.id = lg.account_id')
            ->where(['lg.id' => $id]);

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $accessible = $ctx->getAccessibleAccountIds();
            if (empty($accessible)) {
                return null;
            }
            $query->andWhere(['in', 'lg.account_id', $accessible]);
        }

        /** @var array<string, mixed>|false $row */
        $row = $query->one();
        if ($row === null || $row === false) {
            return null;
        }

        $group = LocationGroup::fromDatabaseRow($row);
        $destinations = $this->findDestinationsByGroupId($id);

        return $group->withDestinations($destinations);
    }

    public function findAll(
        ?TenantContextInterface $context = null,
        ?int $accountId = null,
        int $page = 1,
        int $pageSize = 20,
    ): array {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        if ($ctx !== null && $ctx->isGuest()) {
            return [];
        }

        $query = (new Query($this->db))
            ->select([
                'lg.*',
                'account_business_name' => 'a.business_name',
                'destination_count' => new Expression('(SELECT COUNT(*) FROM {{%location_group_service_config}} lgsc WHERE lgsc.group_id = lg.id)'),
            ])
            ->from(['lg' => '{{%location_group}}'])
            ->leftJoin(['a' => '{{%account}}'], 'a.id = lg.account_id');

        if ($accountId !== null && $accountId > 0) {
            $query->andWhere(['lg.account_id' => $accountId]);
        }

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $accessible = $ctx->getAccessibleAccountIds();
            if (empty($accessible)) {
                return [];
            }
            $query->andWhere(['in', 'lg.account_id', $accessible]);
        }

        $limit = max(1, min(100, $pageSize));
        $offset = max(0, ($page - 1) * $limit);

        $rows = $query
            ->orderBy(['lg.id' => SORT_DESC])
            ->limit($limit)
            ->offset($offset)
            ->all();

        $groups = [];
        foreach ($rows as $row) {
            /** @var array<string, mixed> $row */
            $groups[] = LocationGroup::fromDatabaseRow($row);
        }

        return $groups;
    }

    public function countAll(?TenantContextInterface $context = null, ?int $accountId = null): int
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        if ($ctx !== null && $ctx->isGuest()) {
            return 0;
        }

        $query = (new Query($this->db))
            ->from(['lg' => '{{%location_group}}']);

        if ($accountId !== null && $accountId > 0) {
            $query->andWhere(['lg.account_id' => $accountId]);
        }

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $accessible = $ctx->getAccessibleAccountIds();
            if (empty($accessible)) {
                return 0;
            }
            $query->andWhere(['in', 'lg.account_id', $accessible]);
        }

        return (int) $query->count();
    }

    public function findDestinationsByGroupId(int $groupId): array
    {
        $rows = (new Query($this->db))
            ->select([
                'lgsc_id' => 'lgsc.id',
                'group_id' => 'lgsc.group_id',
                'service_config_id' => 'lgsc.service_config_id',
                'sub_service_id' => 'lgsc.sub_service_id',
                'sc_label' => 'sc.label',
                'sc_page_name' => 'sc.page_name',
                'sc_service_id' => 'sc.service_id',
                'sc_account_id' => 'sc.account_id',
                'sc_linked' => 'sc.linked',
                'account_name' => 'a.business_name',
                'sub_name' => 'sub.name',
                'sub_type' => 'sub.type',
            ])
            ->from(['lgsc' => '{{%location_group_service_config}}'])
            ->leftJoin(['sc' => '{{%service_config}}'], 'sc.id = lgsc.service_config_id')
            ->leftJoin(['sub' => '{{%sub_service}}'], 'sub.id = lgsc.sub_service_id')
            ->leftJoin(['a' => '{{%account}}'], 'a.id = sc.account_id')
            ->where(['lgsc.group_id' => $groupId])
            ->all();

        $destinations = [];
        foreach ($rows as $r) {
            $configId = (int) $r['service_config_id'];
            $subId = isset($r['sub_service_id']) && (int) $r['sub_service_id'] > 0 ? (int) $r['sub_service_id'] : null;
            $serviceId = (int) ($r['sc_service_id'] ?? 0);
            $accountId = (int) ($r['sc_account_id'] ?? 0);
            $accountName = isset($r['account_name']) ? (string) $r['account_name'] : null;
            $pageName = isset($r['sc_page_name']) ? (string) $r['sc_page_name'] : null;
            $isLinked = (int) ($r['sc_linked'] ?? 0) === 1;

            if ($subId !== null && (string) ($r['sub_type'] ?? '') === 'insta') {
                $platform = ConnectedDestination::PLATFORM_INSTAGRAM;
                $label = '@' . ltrim((string) ($r['sub_name'] ?? "Instagram #{$subId}"), '@');
                $key = "{$configId}:{$subId}";
            } elseif ($serviceId === 1) {
                $platform = ConnectedDestination::PLATFORM_GOOGLE;
                $label = !empty($r['sc_label'])
                    ? (string) $r['sc_label']
                    : (!empty($r['sc_page_name']) ? (string) $r['sc_page_name'] : "Google Location #{$configId}");
                $key = "{$configId}:0";
            } elseif ($serviceId === 2) {
                $platform = ConnectedDestination::PLATFORM_FACEBOOK;
                $label = !empty($r['sc_label'])
                    ? (string) $r['sc_label']
                    : (!empty($r['sc_page_name']) ? (string) $r['sc_page_name'] : "Facebook Page #{$configId}");
                $key = "{$configId}:0";
            } else {
                $platform = 'other';
                $label = !empty($r['sc_label']) ? (string) $r['sc_label'] : "Destination #{$configId}";
                $key = "{$configId}:" . ($subId ?? 0);
            }

            $destinations[] = new ConnectedDestination(
                key: $key,
                configId: $configId,
                subId: $subId,
                label: $label,
                platform: $platform,
                accountId: $accountId,
                accountName: $accountName,
                pageName: $pageName,
                isLinked: $isLinked,
            );
        }

        return $destinations;
    }

    public function findGroupDestinationKeys(int $groupId): array
    {
        $rows = (new Query($this->db))
            ->select(['service_config_id', 'sub_service_id'])
            ->from('{{%location_group_service_config}}')
            ->where(['group_id' => $groupId])
            ->all();

        $keys = [];
        foreach ($rows as $r) {
            $configId = (int) $r['service_config_id'];
            $subId = isset($r['sub_service_id']) && (int) $r['sub_service_id'] > 0 ? (int) $r['sub_service_id'] : 0;
            $keys[] = "{$configId}:{$subId}";
        }

        return $keys;
    }

    public function findAvailableDestinationsForAccount(int $accountId): array
    {
        if ($accountId <= 0) {
            return [
                'google' => [],
                'facebook' => [],
                'instagram' => [],
            ];
        }

        // 1. Google Business Profile destinations (service_id = 1, linked = 1, page_token not empty)
        $googleRows = (new Query($this->db))
            ->select([
                'sc.id',
                'sc.label',
                'sc.page_name',
                'sc.account_id',
                'account_name' => 'a.business_name',
            ])
            ->from(['sc' => '{{%service_config}}'])
            ->leftJoin(['a' => '{{%account}}'], 'a.id = sc.account_id')
            ->where([
                'sc.account_id' => $accountId,
                'sc.service_id' => 1,
                'sc.linked' => 1,
            ])
            ->andWhere(['not', ['sc.page_token' => null]])
            ->andWhere(['not', ['sc.page_token' => '']])
            ->orderBy(['sc.label' => SORT_ASC, 'sc.page_name' => SORT_ASC])
            ->all();

        $google = [];
        foreach ($googleRows as $r) {
            $id = (int) $r['id'];
            $label = !empty($r['label'])
                ? (string) $r['label']
                : (!empty($r['page_name']) ? (string) $r['page_name'] : "Google Location #{$id}");

            $google[] = new ConnectedDestination(
                key: "{$id}:0",
                configId: $id,
                subId: null,
                label: $label,
                platform: ConnectedDestination::PLATFORM_GOOGLE,
                accountId: (int) $r['account_id'],
                accountName: isset($r['account_name']) ? (string) $r['account_name'] : null,
                pageName: isset($r['page_name']) ? (string) $r['page_name'] : null,
                isLinked: true,
            );
        }

        // 2. Facebook Page destinations (service_id = 2, linked = 1, page_token not empty)
        $facebookRows = (new Query($this->db))
            ->select([
                'sc.id',
                'sc.label',
                'sc.page_name',
                'sc.account_id',
                'account_name' => 'a.business_name',
            ])
            ->from(['sc' => '{{%service_config}}'])
            ->leftJoin(['a' => '{{%account}}'], 'a.id = sc.account_id')
            ->where([
                'sc.account_id' => $accountId,
                'sc.service_id' => 2,
                'sc.linked' => 1,
            ])
            ->andWhere(['not', ['sc.page_token' => null]])
            ->andWhere(['not', ['sc.page_token' => '']])
            ->orderBy(['sc.label' => SORT_ASC, 'sc.page_name' => SORT_ASC])
            ->all();

        $facebook = [];
        foreach ($facebookRows as $r) {
            $id = (int) $r['id'];
            $label = !empty($r['label'])
                ? (string) $r['label']
                : (!empty($r['page_name']) ? (string) $r['page_name'] : "Facebook Page #{$id}");

            $facebook[] = new ConnectedDestination(
                key: "{$id}:0",
                configId: $id,
                subId: null,
                label: $label,
                platform: ConnectedDestination::PLATFORM_FACEBOOK,
                accountId: (int) $r['account_id'],
                accountName: isset($r['account_name']) ? (string) $r['account_name'] : null,
                pageName: isset($r['page_name']) ? (string) $r['page_name'] : null,
                isLinked: true,
            );
        }

        // 3. Instagram Business destinations (sub_service.type = 'insta', service_config.linked = 1)
        $instagramRows = (new Query($this->db))
            ->select([
                'sub.id',
                'sub.service_config_id',
                'sub.name',
                'sub.account_id',
                'sc.page_name',
                'account_name' => 'a.business_name',
            ])
            ->from(['sub' => '{{%sub_service}}'])
            ->innerJoin(['sc' => '{{%service_config}}'], 'sc.id = sub.service_config_id')
            ->leftJoin(['a' => '{{%account}}'], 'a.id = sub.account_id')
            ->where([
                'sub.account_id' => $accountId,
                'sub.type' => 'insta',
                'sc.linked' => 1,
            ])
            ->andWhere(['not', ['sc.page_token' => null]])
            ->andWhere(['not', ['sc.page_token' => '']])
            ->orderBy(['sub.name' => SORT_ASC])
            ->all();

        $instagram = [];
        foreach ($instagramRows as $r) {
            $configId = (int) $r['service_config_id'];
            $subId = (int) $r['id'];
            $label = '@' . ltrim((string) ($r['name'] ?? "Instagram #{$subId}"), '@');

            $instagram[] = new ConnectedDestination(
                key: "{$configId}:{$subId}",
                configId: $configId,
                subId: $subId,
                label: $label,
                platform: ConnectedDestination::PLATFORM_INSTAGRAM,
                accountId: (int) $r['account_id'],
                accountName: isset($r['account_name']) ? (string) $r['account_name'] : null,
                pageName: isset($r['page_name']) ? (string) $r['page_name'] : null,
                isLinked: true,
            );
        }

        return [
            'google' => $google,
            'facebook' => $facebook,
            'instagram' => $instagram,
        ];
    }

    public function checkGroupAccess(int $groupId, TenantContextInterface $context): bool
    {
        if ($context->isGuest()) {
            return false;
        }

        if ($context->isUnrestricted()) {
            return true;
        }

        $accessible = $context->getAccessibleAccountIds();
        if (empty($accessible)) {
            return false;
        }

        $row = (new Query($this->db))
            ->select(['account_id'])
            ->from('{{%location_group}}')
            ->where(['id' => $groupId])
            ->one();

        if ($row === null || $row === false) {
            return false;
        }

        return in_array((int) $row['account_id'], $accessible, true);
    }

    public function save(LocationGroup $group, array $destinationKeys = []): int
    {
        $transaction = $this->db->beginTransaction();
        try {
            $now = time();
            $params = [
                ':accountId' => $group->getAccountId(),
                ':name' => $group->getName(),
                ':description' => $group->getDescription(),
                ':status' => $group->getStatus(),
                ':updatedAt' => $now,
            ];

            if ($group->getId() > 0) {
                $groupId = $group->getId();
                $this->db->createCommand(
                    'UPDATE {{%location_group}} SET account_id = :accountId, name = :name, description = :description, status = :status, updated_at = :updatedAt WHERE id = :id',
                    array_merge($params, [':id' => $groupId])
                )->execute();
            } else {
                $this->db->createCommand(
                    'INSERT INTO {{%location_group}} (account_id, name, description, status, created_at, updated_at) VALUES (:accountId, :name, :description, :status, :createdAt, :updatedAt)',
                    array_merge($params, [':createdAt' => $now])
                )->execute();
                $groupId = (int) $this->db->getLastInsertID();
            }

            // Synchronize member junctions
            $this->db->createCommand(
                'DELETE FROM {{%location_group_service_config}} WHERE group_id = :groupId',
                [':groupId' => $groupId]
            )->execute();

            foreach ($destinationKeys as $keyStr) {
                $parts = explode(':', trim($keyStr));
                if (count($parts) >= 2 && (int) $parts[0] > 0) {
                    $configId = (int) $parts[0];
                    $subId = (int) $parts[1] > 0 ? (int) $parts[1] : null;

                    $this->db->createCommand(
                        'INSERT INTO {{%location_group_service_config}} (group_id, service_config_id, sub_service_id, created_at) VALUES (:groupId, :configId, :subId, :createdAt)',
                        [
                            ':groupId' => $groupId,
                            ':configId' => $configId,
                            ':subId' => $subId,
                            ':createdAt' => $now,
                        ]
                    )->execute();
                }
            }

            $transaction->commit();
            return $groupId;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public function delete(int $groupId): bool
    {
        $transaction = $this->db->beginTransaction();
        try {
            $this->db->createCommand(
                'DELETE FROM {{%location_group_service_config}} WHERE group_id = :groupId',
                [':groupId' => $groupId]
            )->execute();

            $this->db->createCommand(
                'DELETE FROM {{%location_group_account}} WHERE group_id = :groupId',
                [':groupId' => $groupId]
            )->execute();

            $this->db->createCommand(
                'DELETE FROM {{%location_group}} WHERE id = :groupId',
                [':groupId' => $groupId]
            )->execute();

            $transaction->commit();
            return true;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }
}
