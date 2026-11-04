<?php

declare(strict_types=1);

namespace App\Domain\SocialConnection;

use App\Security\TenantContextInterface;
use App\Security\TenantContextProviderInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

final class SocialConnectionRepository implements SocialConnectionRepositoryInterface
{
    public function __construct(
        private ConnectionInterface $db,
        private ?TenantContextProviderInterface $tenantContextProvider = null,
    ) {
    }

    public function findById(int $id, ?TenantContextInterface $context = null): ?ServiceConfig
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        if ($ctx !== null && $ctx->isGuest()) {
            return null;
        }

        $query = (new Query($this->db))
            ->select(['sc.*', 'account_business_name' => 'a.business_name'])
            ->from(['sc' => '{{%service_config}}'])
            ->leftJoin(['a' => '{{%account}}'], 'a.id = sc.account_id')
            ->where(['sc.id' => $id]);

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $accessible = $ctx->getAccessibleAccountIds();
            if (empty($accessible)) {
                return null;
            }
            $query->andWhere(['in', 'sc.account_id', $accessible]);
        }

        /** @var array<string, mixed>|false $row */
        $row = $query->one();
        if ($row === null || $row === false) {
            return null;
        }

        $config = ServiceConfig::fromDatabaseRow($row);
        $subs = $this->findSubServicesForConfig($id);

        return $config->withSubServices($subs);
    }

    public function findFacebookConfigs(?TenantContextInterface $context = null, int $limit = 50, int $offset = 0): array
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        $query = (new Query($this->db))
            ->select(['sc.*', 'account_business_name' => 'a.business_name'])
            ->from(['sc' => '{{%service_config}}'])
            ->leftJoin(['a' => '{{%account}}'], 'a.id = sc.account_id')
            ->where(['sc.service_id' => ServiceConfig::SERVICE_FACEBOOK, 'sc.linked' => ServiceConfig::LINKED_YES])
            ->orderBy(['sc.id' => SORT_DESC])
            ->limit($limit)
            ->offset($offset);

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $accessible = $ctx->getAccessibleAccountIds();
            if (empty($accessible)) {
                return [];
            }
            $query->andWhere(['in', 'sc.account_id', $accessible]);
        }

        $rows = $query->all();
        $configs = [];
        foreach ($rows as $row) {
            $c = ServiceConfig::fromDatabaseRow($row);
            $subs = $this->findSubServicesForConfig($c->getId());
            $configs[] = $c->withSubServices($subs);
        }

        return $configs;
    }

    public function countFacebookConfigs(?TenantContextInterface $context = null): int
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        $query = (new Query($this->db))
            ->from('{{%service_config}}')
            ->where(['service_id' => ServiceConfig::SERVICE_FACEBOOK, 'linked' => ServiceConfig::LINKED_YES]);

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $accessible = $ctx->getAccessibleAccountIds();
            if (empty($accessible)) {
                return 0;
            }
            $query->andWhere(['in', 'account_id', $accessible]);
        }

        return (int) $query->count();
    }

    public function findGoogleConfigs(?TenantContextInterface $context = null, int $limit = 50, int $offset = 0): array
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        $query = (new Query($this->db))
            ->select(['sc.*', 'account_business_name' => 'a.business_name'])
            ->from(['sc' => '{{%service_config}}'])
            ->leftJoin(['a' => '{{%account}}'], 'a.id = sc.account_id')
            ->where(['sc.service_id' => ServiceConfig::SERVICE_GOOGLE, 'sc.linked' => ServiceConfig::LINKED_YES])
            ->orderBy(['sc.id' => SORT_DESC])
            ->limit($limit)
            ->offset($offset);

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $accessible = $ctx->getAccessibleAccountIds();
            if (empty($accessible)) {
                return [];
            }
            $query->andWhere(['in', 'sc.account_id', $accessible]);
        }

        $rows = $query->all();
        $configs = [];
        foreach ($rows as $row) {
            $configs[] = ServiceConfig::fromDatabaseRow($row);
        }

        return $configs;
    }

    public function countGoogleConfigs(?TenantContextInterface $context = null): int
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        $query = (new Query($this->db))
            ->from(['sc' => '{{%service_config}}'])
            ->where(['sc.service_id' => ServiceConfig::SERVICE_GOOGLE, 'sc.linked' => ServiceConfig::LINKED_YES]);

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $accessible = $ctx->getAccessibleAccountIds();
            if (empty($accessible)) {
                return 0;
            }
            $query->andWhere(['in', 'sc.account_id', $accessible]);
        }

        return (int) $query->count();
    }

    public function findSubServiceById(int $id, ?TenantContextInterface $context = null): ?SubService
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        if ($ctx !== null && $ctx->isGuest()) {
            return null;
        }

        $query = (new Query($this->db))
            ->select(['sub.*'])
            ->from(['sub' => '{{%sub_service}}'])
            ->innerJoin(['sc' => '{{%service_config}}'], 'sc.id = sub.service_config_id')
            ->where(['sub.id' => $id]);

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $accessible = $ctx->getAccessibleAccountIds();
            if (empty($accessible)) {
                return null;
            }
            $query->andWhere(['in', 'sub.account_id', $accessible]);
            $query->andWhere(['in', 'sc.account_id', $accessible]);
        }

        /** @var array<string, mixed>|false $row */
        $row = $query->one();
        if ($row === null || $row === false) {
            return null;
        }

        return SubService::fromDatabaseRow($row);
    }

    public function findSubServicesForConfig(int $serviceConfigId): array
    {
        $rows = (new Query($this->db))
            ->from('{{%sub_service}}')
            ->where(['service_config_id' => $serviceConfigId])
            ->all();

        $subs = [];
        foreach ($rows as $row) {
            $subs[] = SubService::fromDatabaseRow($row);
        }

        return $subs;
    }

    public function findAllLinkedForAccount(int $accountId): array
    {
        $rows = (new Query($this->db))
            ->from('{{%service_config}}')
            ->where(['account_id' => $accountId, 'linked' => ServiceConfig::LINKED_YES])
            ->orderBy(['service_id' => SORT_ASC, 'label' => SORT_ASC])
            ->all();

        $configs = [];
        foreach ($rows as $row) {
            $c = ServiceConfig::fromDatabaseRow($row);
            $subs = $this->findSubServicesForConfig($c->getId());
            $configs[] = $c->withSubServices($subs);
        }

        return $configs;
    }

    public function updateConfig(int $id, array $data, ?TenantContextInterface $context = null): bool
    {
        $existing = $this->findById($id, $context);
        if ($existing === null) {
            return false;
        }

        $updateFields = [
            'updated_at' => time(),
        ];
        if (isset($data['label'])) {
            $updateFields['label'] = (string) $data['label'];
        }
        if (isset($data['email_low_review'])) {
            $updateFields['email_low_review'] = (string) $data['email_low_review'];
        }

        try {
            $this->db->createCommand()
                ->update('{{%service_config}}', $updateFields, ['id' => $id])
                ->execute();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function unlinkConfig(int $id, ?TenantContextInterface $context = null): bool
    {
        $existing = $this->findById($id, $context);
        if ($existing === null) {
            return false;
        }

        try {
            $this->db->createCommand()
                ->update('{{%service_config}}', ['linked' => ServiceConfig::LINKED_NO, 'updated_at' => time()], ['id' => $id])
                ->execute();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function deleteConfig(int $id, ?TenantContextInterface $context = null): bool
    {
        $existing = $this->findById($id, $context);
        if ($existing === null) {
            return false;
        }

        try {
            $this->db->createCommand()
                ->delete('{{%service_config}}', ['id' => $id])
                ->execute();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function findApisByService(int $serviceId): array
    {
        $rows = (new Query($this->db))
            ->from('{{%api}}')
            ->where(['service_id' => $serviceId])
            ->orderBy(['id' => SORT_DESC])
            ->all();

        $apis = [];
        foreach ($rows as $row) {
            $apis[] = Api::fromDatabaseRow($row);
        }
        return $apis;
    }

    public function findApiById(int $id): ?Api
    {
        $row = (new Query($this->db))
            ->from('{{%api}}')
            ->where(['id' => $id])
            ->one();

        if ($row === null || $row === false) {
            return null;
        }

        return Api::fromDatabaseRow($row);
    }

    public function saveApi(array $data, ?int $id = null): ?int
    {
        $now = time();
        $fields = [
            'service_id' => (int) ($data['service_id'] ?? 0),
            'label' => (string) ($data['label'] ?? ''),
            'updated_at' => $now,
        ];
        if (array_key_exists('api_key', $data)) {
            $fields['api_key'] = $data['api_key'];
        }
        if (array_key_exists('client_id', $data)) {
            $fields['client_id'] = $data['client_id'];
        }
        if (array_key_exists('token', $data)) {
            $fields['token'] = $data['token'];
        }
        if (array_key_exists('client_json', $data)) {
            $fields['client_json'] = $data['client_json'];
        }

        try {
            if ($id !== null && $id > 0) {
                $this->db->createCommand()
                    ->update('{{%api}}', $fields, ['id' => $id])
                    ->execute();
                return $id;
            } else {
                $fields['created_at'] = $now;
                $this->db->createCommand()
                    ->insert('{{%api}}', $fields)
                    ->execute();
                return (int) $this->db->getLastInsertId();
            }
        } catch (\Throwable) {
            return null;
        }
    }

    public function deleteApi(int $id): bool
    {
        try {
            $this->db->createCommand()
                ->delete('{{%api}}', ['id' => $id])
                ->execute();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function countAssignedPages(int $apiId): int
    {
        try {
            return (int) (new Query($this->db))
                ->from('{{%service_config}}')
                ->where(['api_id' => $apiId])
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
