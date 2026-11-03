<?php

declare(strict_types=1);

namespace App\Domain\Email;

use App\Security\TenantContextInterface;
use App\Security\TenantContextProviderInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * Repository implementation for Email domain.
 * Strictly enforces tenant context isolation and safe prepared queries.
 */
final class EmailRepository implements EmailRepositoryInterface
{
    public function __construct(
        private ConnectionInterface $db,
        private ?TenantContextProviderInterface $tenantContextProvider = null,
    ) {
    }

    public function findTemplateById(int $id, ?TenantContextInterface $context = null): ?EmailTemplate
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        if ($ctx !== null && $ctx->isGuest()) {
            return null;
        }

        $query = (new Query($this->db))
            ->from('{{%email_template}}')
            ->where(['id' => $id]);

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $accessible = $ctx->getAccessibleAccountIds();
            if (empty($accessible)) {
                return null;
            }
            $query->andWhere(['or', ['in', 'account_id', $accessible], ['account_id' => null]]);
        }

        /** @var array<string, mixed>|false $row */
        $row = $query->one();
        if ($row === null || $row === false) {
            return null;
        }

        return EmailTemplate::fromDatabaseRow($row);
    }

    public function findTemplateBySlug(string $slug, ?int $accountId = null): ?EmailTemplate
    {
        $query = (new Query($this->db))
            ->from('{{%email_template}}')
            ->where(['slug' => $slug, 'status' => EmailTemplate::STATUS_ACTIVE]);

        if ($accountId !== null && $accountId > 0) {
            $query->andWhere(['or', ['account_id' => $accountId], ['account_id' => null]])
                ->orderBy(['account_id' => SORT_DESC]); // Prioritize account-specific template over null
        } else {
            $query->andWhere(['account_id' => null]);
        }

        /** @var array<string, mixed>|false $row */
        $row = $query->one();
        if ($row === null || $row === false) {
            return null;
        }

        return EmailTemplate::fromDatabaseRow($row);
    }

    public function findTemplatesByAccount(?TenantContextInterface $context = null, ?int $accountId = null): array
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        if ($ctx !== null && $ctx->isGuest()) {
            return [];
        }

        $query = (new Query($this->db))
            ->from('{{%email_template}}')
            ->where(['status' => EmailTemplate::STATUS_ACTIVE]);

        if ($accountId !== null && $accountId > 0) {
            $query->andWhere(['account_id' => $accountId]);
        } elseif ($ctx !== null && !$ctx->isUnrestricted()) {
            $accessible = $ctx->getAccessibleAccountIds();
            if (empty($accessible)) {
                return [];
            }
            $query->andWhere(['in', 'account_id', $accessible]);
        }

        $rows = $query->orderBy(['protected' => SORT_DESC, 'name' => SORT_ASC])->all();

        $templates = [];
        foreach ($rows as $row) {
            /** @var array<string, mixed> $row */
            $templates[] = EmailTemplate::fromDatabaseRow($row);
        }

        return $templates;
    }

    public function findContactById(int $id, ?TenantContextInterface $context = null): ?EmailAddress
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        if ($ctx !== null && $ctx->isGuest()) {
            return null;
        }

        $query = (new Query($this->db))
            ->from('{{%email_addresses}}')
            ->where(['id' => $id]);

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $accessible = $ctx->getAccessibleAccountIds();
            if (empty($accessible)) {
                return null;
            }
            $query->andWhere(['in', 'account_id', $accessible]);
        }

        /** @var array<string, mixed>|false $row */
        $row = $query->one();
        if ($row === null || $row === false) {
            return null;
        }

        return EmailAddress::fromDatabaseRow($row);
    }

    public function findContactsByAccount(
        ?TenantContextInterface $context = null,
        ?int $accountId = null,
        int $page = 1,
        int $pageSize = 50,
    ): array {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        if ($ctx !== null && $ctx->isGuest()) {
            return [];
        }

        $query = (new Query($this->db))
            ->from('{{%email_addresses}}');

        if ($accountId !== null && $accountId > 0) {
            $query->andWhere(['account_id' => $accountId]);
        } elseif ($ctx !== null && !$ctx->isUnrestricted()) {
            $accessible = $ctx->getAccessibleAccountIds();
            if (empty($accessible)) {
                return [];
            }
            $query->andWhere(['in', 'account_id', $accessible]);
        }

        $limit = max(1, min(200, $pageSize));
        $offset = max(0, ($page - 1) * $limit);

        $rows = $query
            ->orderBy(['id' => SORT_DESC])
            ->limit($limit)
            ->offset($offset)
            ->all();

        $contacts = [];
        foreach ($rows as $row) {
            /** @var array<string, mixed> $row */
            $contacts[] = EmailAddress::fromDatabaseRow($row);
        }

        return $contacts;
    }

    public function countContactsByAccount(?TenantContextInterface $context = null, ?int $accountId = null): int
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        if ($ctx !== null && $ctx->isGuest()) {
            return 0;
        }

        $query = (new Query($this->db))
            ->from('{{%email_addresses}}');

        if ($accountId !== null && $accountId > 0) {
            $query->andWhere(['account_id' => $accountId]);
        } elseif ($ctx !== null && !$ctx->isUnrestricted()) {
            $accessible = $ctx->getAccessibleAccountIds();
            if (empty($accessible)) {
                return 0;
            }
            $query->andWhere(['in', 'account_id', $accessible]);
        }

        return (int) $query->count();
    }

    public function findQueueById(int $id, ?TenantContextInterface $context = null): ?EmailQueue
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        if ($ctx !== null && $ctx->isGuest()) {
            return null;
        }

        $query = (new Query($this->db))
            ->select([
                'eq.*',
                'template_name' => 'et.name',
            ])
            ->from(['eq' => '{{%email_queue}}'])
            ->leftJoin(['et' => '{{%email_template}}'], 'et.id = eq.email_template_id')
            ->where(['eq.id' => $id]);

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $accessible = $ctx->getAccessibleAccountIds();
            if (empty($accessible)) {
                return null;
            }
            $query->andWhere(['in', 'eq.account_id', $accessible]);
        }

        /** @var array<string, mixed>|false $row */
        $row = $query->one();
        if ($row === null || $row === false) {
            return null;
        }

        return EmailQueue::fromDatabaseRow($row);
    }

    public function findQueueByAccount(
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
                'eq.*',
                'template_name' => 'et.name',
            ])
            ->from(['eq' => '{{%email_queue}}'])
            ->leftJoin(['et' => '{{%email_template}}'], 'et.id = eq.email_template_id');

        if ($accountId !== null && $accountId > 0) {
            $query->andWhere(['eq.account_id' => $accountId]);
        } elseif ($ctx !== null && !$ctx->isUnrestricted()) {
            $accessible = $ctx->getAccessibleAccountIds();
            if (empty($accessible)) {
                return [];
            }
            $query->andWhere(['in', 'eq.account_id', $accessible]);
        }

        $limit = max(1, min(100, $pageSize));
        $offset = max(0, ($page - 1) * $limit);

        $rows = $query
            ->orderBy(['eq.id' => SORT_DESC])
            ->limit($limit)
            ->offset($offset)
            ->all();

        $queue = [];
        foreach ($rows as $row) {
            /** @var array<string, mixed> $row */
            $queue[] = EmailQueue::fromDatabaseRow($row);
        }

        return $queue;
    }

    public function countQueueByAccount(?TenantContextInterface $context = null, ?int $accountId = null): int
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        if ($ctx !== null && $ctx->isGuest()) {
            return 0;
        }

        $query = (new Query($this->db))
            ->from('{{%email_queue}}');

        if ($accountId !== null && $accountId > 0) {
            $query->andWhere(['account_id' => $accountId]);
        } elseif ($ctx !== null && !$ctx->isUnrestricted()) {
            $accessible = $ctx->getAccessibleAccountIds();
            if (empty($accessible)) {
                return 0;
            }
            $query->andWhere(['in', 'account_id', $accessible]);
        }

        return (int) $query->count();
    }

    public function findPendingQueue(int $batchSize = 100): array
    {
        $now = date('Y-m-d H:i:s');
        $rows = (new Query($this->db))
            ->from('{{%email_queue}}')
            ->where(['status' => EmailQueue::STATUS_QUEUED])
            ->andWhere(['or', ['send_at' => null], ['<=', 'send_at', $now]])
            ->orderBy(['id' => SORT_ASC])
            ->limit(max(1, min(500, $batchSize)))
            ->all();

        $queue = [];
        foreach ($rows as $row) {
            /** @var array<string, mixed> $row */
            $queue[] = EmailQueue::fromDatabaseRow($row);
        }

        return $queue;
    }

    public function checkAccountAccess(int $targetAccountId, TenantContextInterface $context): bool
    {
        if ($context->isGuest()) {
            return false;
        }

        if ($context->isUnrestricted()) {
            return true;
        }

        return in_array($targetAccountId, $context->getAccessibleAccountIds(), true);
    }

    // ==========================================
    // Prepared persistence methods for write activation
    // ==========================================

    public function saveTemplate(EmailTemplate $template): int
    {
        $now = time();
        $params = [
            ':accountId' => $template->getAccountId(),
            ':slug' => $template->getSlug(),
            ':name' => $template->getName(),
            ':text' => $template->getText(),
            ':protected' => $template->getProtected(),
            ':status' => $template->getStatus(),
            ':updatedAt' => $now,
        ];

        if ($template->getId() > 0) {
            $this->db->createCommand(
                'UPDATE {{%email_template}} SET account_id = :accountId, slug = :slug, name = :name, text = :text, protected = :protected, status = :status, updated_at = :updatedAt WHERE id = :id',
                array_merge($params, [':id' => $template->getId()])
            )->execute();
            return $template->getId();
        }

        $this->db->createCommand(
            'INSERT INTO {{%email_template}} (account_id, slug, name, text, protected, status, created_at, updated_at) VALUES (:accountId, :slug, :name, :text, :protected, :status, :createdAt, :updatedAt)',
            array_merge($params, [':createdAt' => $now])
        )->execute();

        return (int) $this->db->getLastInsertID();
    }

    public function deleteTemplate(int $id): bool
    {
        $rows = $this->db->createCommand(
            'DELETE FROM {{%email_template}} WHERE id = :id AND protected = 0',
            [':id' => $id]
        )->execute();

        return $rows > 0;
    }

    public function saveContact(EmailAddress $contact): int
    {
        $now = time();
        $params = [
            ':accountId' => $contact->getAccountId(),
            ':name' => $contact->getName(),
            ':email' => $contact->getEmail(),
            ':type' => $contact->getType(),
            ':status' => $contact->getStatus(),
            ':updatedAt' => $now,
        ];

        if ($contact->getId() > 0) {
            $this->db->createCommand(
                'UPDATE {{%email_addresses}} SET account_id = :accountId, name = :name, email = :email, type = :type, status = :status, updated_at = :updatedAt WHERE id = :id',
                array_merge($params, [':id' => $contact->getId()])
            )->execute();
            return $contact->getId();
        }

        $this->db->createCommand(
            'INSERT INTO {{%email_addresses}} (account_id, name, email, type, status, created_at, updated_at) VALUES (:accountId, :name, :email, :type, :status, :createdAt, :updatedAt)',
            array_merge($params, [':createdAt' => $now])
        )->execute();

        return (int) $this->db->getLastInsertID();
    }

    public function saveContactsBatch(array $rows, int $accountId): int
    {
        if (empty($rows)) {
            return 0;
        }

        $now = time();
        $count = 0;
        $transaction = $this->db->beginTransaction();
        try {
            foreach ($rows as $r) {
                $this->db->createCommand(
                    'INSERT INTO {{%email_addresses}} (account_id, name, email, type, status, created_at, updated_at) VALUES (:accountId, :name, :email, :type, :status, :createdAt, :updatedAt)',
                    [
                        ':accountId' => $accountId,
                        ':name' => (string) ($r['name'] ?? ''),
                        ':email' => (string) ($r['email'] ?? ''),
                        ':type' => in_array((string) ($r['type'] ?? ''), ['to', 'cc', 'bcc'], true) ? (string) $r['type'] : 'to',
                        ':status' => EmailAddress::STATUS_ACTIVE,
                        ':createdAt' => $now,
                        ':updatedAt' => $now,
                    ]
                )->execute();
                $count++;
            }
            $transaction->commit();
            return $count;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public function deleteContact(int $id): bool
    {
        return $this->db->createCommand(
            'DELETE FROM {{%email_addresses}} WHERE id = :id',
            [':id' => $id]
        )->execute() > 0;
    }

    public function deleteContactsBatch(array $ids, int $accountId): int
    {
        if (empty($ids)) {
            return 0;
        }

        return $this->db->createCommand()
            ->delete('{{%email_addresses}}', ['and', ['in', 'id', $ids], ['account_id' => $accountId]])
            ->execute();
    }

    public function enqueueEmail(EmailQueue $email): int
    {
        $now = time();
        $this->db->createCommand(
            'INSERT INTO {{%email_queue}} (account_id, email_template_id, ref, name, email, bcc, subject, body, status, send_at, created_at, updated_at) VALUES (:accountId, :tplId, :ref, :name, :email, :bcc, :subject, :body, :status, :sendAt, :createdAt, :updatedAt)',
            [
                ':accountId' => $email->getAccountId(),
                ':tplId' => $email->getEmailTemplateId(),
                ':ref' => $email->getRef(),
                ':name' => $email->getName(),
                ':email' => $email->getEmail(),
                ':bcc' => $email->getBcc(),
                ':subject' => $email->getSubject(),
                ':body' => $email->getBody(),
                ':status' => EmailQueue::STATUS_QUEUED,
                ':sendAt' => $email->getSendAt(),
                ':createdAt' => $now,
                ':updatedAt' => $now,
            ]
        )->execute();

        return (int) $this->db->getLastInsertID();
    }

    public function claimQueueJob(int $id, string $lockTime): bool
    {
        $affected = $this->db->createCommand(
            'UPDATE {{%email_queue}} SET status = :proc, locked_at = :lockTime WHERE id = :id AND status = :queued',
            [
                ':proc' => EmailQueue::STATUS_PROCESSING,
                ':lockTime' => $lockTime,
                ':id' => $id,
                ':queued' => EmailQueue::STATUS_QUEUED,
            ]
        )->execute();

        return $affected > 0;
    }

    public function markQueueSent(int $id, ?string $externalId = null): bool
    {
        $now = date('Y-m-d H:i:s');
        return $this->db->createCommand(
            'UPDATE {{%email_queue}} SET status = :sent, sent_at = :now, external_id = :extId, locked_at = NULL WHERE id = :id',
            [
                ':sent' => EmailQueue::STATUS_SENT,
                ':now' => $now,
                ':extId' => $externalId,
                ':id' => $id,
            ]
        )->execute() > 0;
    }

    public function markQueueFailed(int $id): bool
    {
        return $this->db->createCommand(
            'UPDATE {{%email_queue}} SET status = :failed, locked_at = NULL WHERE id = :id',
            [
                ':failed' => EmailQueue::STATUS_FAILED,
                ':id' => $id,
            ]
        )->execute() > 0;
    }

    public function markQueueDelivered(int $id): bool
    {
        return $this->db->createCommand(
            'UPDATE {{%email_queue}} SET status = :delivered WHERE id = :id',
            [
                ':delivered' => EmailQueue::STATUS_DELIVERED,
                ':id' => $id,
            ]
        )->execute() > 0;
    }

    public function recoverStaleQueueJobs(int $timeoutMinutes = 15): int
    {
        $staleThreshold = date('Y-m-d H:i:s', time() - ($timeoutMinutes * 60));
        return $this->db->createCommand(
            'UPDATE {{%email_queue}} SET status = :queued, locked_at = NULL WHERE status = :proc AND locked_at < :staleThreshold',
            [
                ':queued' => EmailQueue::STATUS_QUEUED,
                ':proc' => EmailQueue::STATUS_PROCESSING,
                ':staleThreshold' => $staleThreshold,
            ]
        )->execute();
    }
}
