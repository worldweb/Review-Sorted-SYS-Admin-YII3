<?php

declare(strict_types=1);

namespace App\Domain\Subscription;

use App\Security\TenantContextInterface;
use App\Security\TenantContextProviderInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

final class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function __construct(
        private ConnectionInterface $db,
        private ?TenantContextProviderInterface $tenantContextProvider = null,
    ) {
    }

    public function findAllPlans(): array
    {
        $rows = (new Query($this->db))
            ->from('{{%plans}}')
            ->orderBy(['id' => SORT_ASC])
            ->all();

        $plans = [];
        foreach ($rows as $row) {
            $plans[] = Plan::fromDatabaseRow($row);
        }

        return $plans;
    }

    public function findPlanById(int $id): ?Plan
    {
        $row = (new Query($this->db))
            ->from('{{%plans}}')
            ->where(['id' => $id])
            ->one();

        if ($row === null || $row === false) {
            return null;
        }

        return Plan::fromDatabaseRow($row);
    }

    public function createPlan(array $data): ?int
    {
        try {
            $now = time();
            $this->db->createCommand()->insert('{{%plans}}', [
                'plan_name' => $data['plan_name'],
                'plan_price' => $data['plan_price'],
                'plan_limit' => $data['plan_limit'],
                'plan_features' => $data['plan_features'],
                'status' => $data['status'] ?? '1',
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();

            return (int) $this->db->getLastInsertID('{{%plans}}');
        } catch (\Throwable) {
            return null;
        }
    }

    public function updatePlan(int $id, array $data): bool
    {
        try {
            $this->db->createCommand()->update('{{%plans}}', [
                'plan_name' => $data['plan_name'],
                'plan_price' => $data['plan_price'],
                'plan_limit' => $data['plan_limit'],
                'plan_features' => $data['plan_features'],
                'status' => $data['status'] ?? '1',
                'updated_at' => time(),
            ], ['id' => $id])->execute();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function deletePlan(int $id): bool
    {
        try {
            $this->db->createCommand()->delete('{{%plans}}', ['id' => $id])->execute();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function findAllSubscriptions(?TenantContextInterface $context = null, int $limit = 50, int $offset = 0): array
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        $query = (new Query($this->db))
            ->from('{{%subscriptions}}')
            ->orderBy(['id' => SORT_DESC])
            ->limit($limit)
            ->offset($offset);

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $query->andWhere(['user_id' => $ctx->getUserId()]);
        }

        $rows = $query->all();
        $subs = [];
        foreach ($rows as $row) {
            $subs[] = Subscription::fromDatabaseRow($row);
        }

        return $subs;
    }

    public function countSubscriptions(?TenantContextInterface $context = null): int
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        $query = (new Query($this->db))
            ->from('{{%subscriptions}}');

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $query->andWhere(['user_id' => $ctx->getUserId()]);
        }

        return (int) $query->count();
    }

    public function findInvoicesForAccount(int $accountId, int $limit = 20): array
    {
        $rows = (new Query($this->db))
            ->from('{{%account_invoice}}')
            ->where(['account_id' => $accountId])
            ->orderBy(['id' => SORT_DESC])
            ->limit($limit)
            ->all();

        $invoices = [];
        foreach ($rows as $row) {
            $invoices[] = AccountInvoice::fromDatabaseRow($row);
        }

        return $invoices;
    }
}
