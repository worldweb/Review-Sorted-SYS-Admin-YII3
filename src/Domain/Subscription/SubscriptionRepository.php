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

    public function findActiveSubscriptionByUserId(int $userId): ?Subscription
    {
        $sql = "SELECT * FROM {{%subscriptions}} WHERE user_id = :uid AND status = 'active' AND (plan_period_end > NOW() OR plan_period_end IS NULL) ORDER BY plan_period_end DESC, id DESC LIMIT 1";
        $row = $this->db->createCommand($sql, [':uid' => $userId])->queryOne();
        if ($row === null || $row === false) {
            return null;
        }

        return Subscription::fromDatabaseRow($row);
    }

    public function findSubscriptionByStripeId(string $stripeSubscriptionId): ?Subscription
    {
        $sql = "SELECT * FROM {{%subscriptions}} WHERE stripe_subscription_id = :sid ORDER BY id DESC LIMIT 1";
        $row = $this->db->createCommand($sql, [':sid' => $stripeSubscriptionId])->queryOne();
        if ($row === null || $row === false) {
            return null;
        }

        return Subscription::fromDatabaseRow($row);
    }

    public function findLatestSubscriptionByUserId(int $userId): ?Subscription
    {
        $sql = "SELECT * FROM {{%subscriptions}} WHERE user_id = :uid ORDER BY id DESC LIMIT 1";
        $row = $this->db->createCommand($sql, [':uid' => $userId])->queryOne();
        if ($row === null || $row === false) {
            return null;
        }

        return Subscription::fromDatabaseRow($row);
    }

    public function insertSubscription(array $data): int
    {
        $now = time();
        $sql = "INSERT INTO {{%subscriptions}} ("
             . "user_id, plan_id, stripe_customer_id, stripe_plan_id, stripe_subscription_id, "
             . "default_payment_method, default_source, paid_amount, paid_amount_currency, "
             . "plan_interval, plan_interval_count, customer_name, customer_email, "
             . "plan_period_start, plan_period_end, stripe_status, status, created_at, updated_at, expire_at"
             . ") VALUES ("
             . ":user_id, :plan_id, :stripe_customer_id, :stripe_plan_id, :stripe_subscription_id, "
             . ":default_payment_method, :default_source, :paid_amount, :paid_amount_currency, "
             . ":plan_interval, :plan_interval_count, :customer_name, :customer_email, "
             . ":plan_period_start, :plan_period_end, :stripe_status, :status, :created_at, :updated_at, :expire_at"
             . ")";

        $this->db->createCommand($sql, [
            ':user_id' => $data['user_id'] ?? 0,
            ':plan_id' => $data['plan_id'] ?? null,
            ':stripe_customer_id' => $data['stripe_customer_id'] ?? null,
            ':stripe_plan_id' => $data['stripe_plan_id'] ?? null,
            ':stripe_subscription_id' => $data['stripe_subscription_id'] ?? null,
            ':default_payment_method' => $data['default_payment_method'] ?? null,
            ':default_source' => $data['default_source'] ?? null,
            ':paid_amount' => isset($data['paid_amount']) ? (string) $data['paid_amount'] : '0.00',
            ':paid_amount_currency' => $data['paid_amount_currency'] ?? 'usd',
            ':plan_interval' => $data['plan_interval'] ?? 'month',
            ':plan_interval_count' => isset($data['plan_interval_count']) ? (string) $data['plan_interval_count'] : '1',
            ':customer_name' => $data['customer_name'] ?? null,
            ':customer_email' => $data['customer_email'] ?? null,
            ':plan_period_start' => $data['plan_period_start'] ?? null,
            ':plan_period_end' => $data['plan_period_end'] ?? null,
            ':stripe_status' => $data['stripe_status'] ?? 'succeeded',
            ':status' => $data['status'] ?? 'active',
            ':created_at' => $now,
            ':updated_at' => $now,
            ':expire_at' => $data['expire_at'] ?? null,
        ])->execute();

        return (int) $this->db->getLastInsertID();
    }

    public function updateSubscription(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id, ':upd' => time()];
        $allowed = [
            'plan_id', 'stripe_customer_id', 'stripe_plan_id', 'stripe_subscription_id',
            'default_payment_method', 'default_source', 'paid_amount', 'paid_amount_currency',
            'plan_interval', 'plan_interval_count', 'customer_name', 'customer_email',
            'plan_period_start', 'plan_period_end', 'stripe_status', 'status', 'expire_at',
        ];

        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $fields[] = "`$key` = :$key";
                $params[":$key"] = $data[$key];
            }
        }

        $fields[] = "`updated_at` = :upd";
        $sql = "UPDATE {{%subscriptions}} SET " . implode(', ', $fields) . " WHERE id = :id";

        $affected = $this->db->createCommand($sql, $params)->execute();
        return $affected >= 0;
    }

    public function cancelSubscription(int $id, ?string $expireAt = null): bool
    {
        $now = time();
        $sql = "UPDATE {{%subscriptions}} SET `status` = 'inactive', `expire_at` = :exp, `updated_at` = :upd WHERE id = :id";
        $affected = $this->db->createCommand($sql, [
            ':id' => $id,
            ':exp' => $expireAt,
            ':upd' => $now,
        ])->execute();

        return $affected >= 0;
    }

    public function syncAccountInvoice(array $data, ?int $accountId = null, ?int $userId = null): bool
    {
        $invoiceId = (string) ($data['id'] ?? $data['stripe_invoice_id'] ?? '');
        if ($invoiceId === '') {
            return false;
        }

        $subId = (string) ($data['subscription'] ?? $data['stripe_subscription_id'] ?? '');
        $cusId = (string) ($data['customer'] ?? $data['stripe_customer_id'] ?? '');

        // Resolve account and user if not passed
        if ($accountId === null || $userId === null) {
            $subRecord = null;
            if ($subId !== '') {
                $subRecord = $this->findSubscriptionByStripeId($subId);
            }
            if ($subRecord !== null) {
                $userId = $userId ?? $subRecord->getUserId();
                $userRow = $this->db->createCommand('SELECT account_id FROM {{%user}} WHERE id = :uid', [':uid' => $userId])->queryOne();
                if ($userRow && !empty($userRow['account_id'])) {
                    $accountId = $accountId ?? (int) $userRow['account_id'];
                }
            }
        }

        if ($accountId === null || $accountId <= 0) {
            return false;
        }

        $invoiceNumber = (string) ($data['number'] ?? $data['invoice_number'] ?? $invoiceId);
        $amountPaidCents = isset($data['amount_paid']) ? (int) $data['amount_paid'] : (isset($data['total']) ? (int) $data['total'] : 0);
        $amountPaid = round($amountPaidCents / 100, 2);
        $currency = strtolower((string) ($data['currency'] ?? 'usd'));
        $status = strtolower((string) ($data['status'] ?? 'paid'));
        $billingReason = isset($data['billing_reason']) ? (string) $data['billing_reason'] : null;
        $pdfUrl = isset($data['invoice_pdf']) ? (string) $data['invoice_pdf'] : (isset($data['invoice_pdf_url']) ? (string) $data['invoice_pdf_url'] : null);
        $hostedUrl = isset($data['hosted_invoice_url']) ? (string) $data['hosted_invoice_url'] : null;
        $periodStart = isset($data['period_start']) ? (int) $data['period_start'] : null;
        $periodEnd = isset($data['period_end']) ? (int) $data['period_end'] : null;
        $paidAt = isset($data['status_transitions']['paid_at']) ? (int) $data['status_transitions']['paid_at'] : time();

        $existing = $this->findInvoiceByStripeInvoiceId($invoiceId);
        $now = time();

        if ($existing === null) {
            $sql = "INSERT INTO {{%account_invoice}} ("
                 . "account_id, user_id, stripe_customer_id, stripe_subscription_id, stripe_invoice_id, "
                 . "invoice_number, amount_paid, amount_cents, currency, status, billing_reason, "
                 . "invoice_pdf_url, hosted_invoice_url, period_start, period_end, paid_at, created_at, updated_at"
                 . ") VALUES ("
                 . ":acc, :uid, :cus, :sub, :inv, :num, :amt, :cents, :cur, :stat, :reason, :pdf, :host, :p_start, :p_end, :paid_at, :created, :updated"
                 . ")";

            $this->db->createCommand($sql, [
                ':acc' => $accountId,
                ':uid' => $userId ?? 0,
                ':cus' => $cusId !== '' ? $cusId : null,
                ':sub' => $subId !== '' ? $subId : null,
                ':inv' => $invoiceId,
                ':num' => $invoiceNumber,
                ':amt' => $amountPaid,
                ':cents' => $amountPaidCents,
                ':cur' => $currency,
                ':stat' => $status,
                ':reason' => $billingReason,
                ':pdf' => $pdfUrl,
                ':host' => $hostedUrl,
                ':p_start' => $periodStart,
                ':p_end' => $periodEnd,
                ':paid_at' => $paidAt,
                ':created' => $now,
                ':updated' => $now,
            ])->execute();
        } else {
            $sql = "UPDATE {{%account_invoice}} SET "
                 . "status = :stat, amount_paid = :amt, amount_cents = :cents, "
                 . "invoice_pdf_url = COALESCE(:pdf, invoice_pdf_url), "
                 . "hosted_invoice_url = COALESCE(:host, hosted_invoice_url), "
                 . "updated_at = :updated "
                 . "WHERE stripe_invoice_id = :inv";

            $this->db->createCommand($sql, [
                ':stat' => $status,
                ':amt' => $amountPaid,
                ':cents' => $amountPaidCents,
                ':pdf' => $pdfUrl,
                ':host' => $hostedUrl,
                ':updated' => $now,
                ':inv' => $invoiceId,
            ])->execute();
        }

        return true;
    }

    public function findInvoiceByStripeInvoiceId(string $stripeInvoiceId): ?AccountInvoice
    {
        $sql = "SELECT * FROM {{%account_invoice}} WHERE stripe_invoice_id = :id LIMIT 1";
        $row = $this->db->createCommand($sql, [':id' => $stripeInvoiceId])->queryOne();
        if ($row === null || $row === false) {
            return null;
        }

        return AccountInvoice::fromDatabaseRow($row);
    }

    public function findPlanByPrice(float $price): ?Plan
    {
        $sql = "SELECT * FROM {{%plans}} WHERE CAST(plan_price AS DECIMAL(10,2)) = :price AND status = '1' LIMIT 1";
        $row = $this->db->createCommand($sql, [':price' => $price])->queryOne();
        if ($row === null || $row === false) {
            return null;
        }

        return Plan::fromDatabaseRow($row);
    }

    public function findDistinctSubscribedUserIds(): array
    {
        $sql = "SELECT DISTINCT user_id FROM {{%subscriptions}} WHERE user_id > 0";
        $rows = $this->db->createCommand($sql)->queryColumn();
        return array_map('intval', $rows);
    }
}
