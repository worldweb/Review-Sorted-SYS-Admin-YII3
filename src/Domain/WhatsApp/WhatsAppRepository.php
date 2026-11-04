<?php

declare(strict_types=1);

namespace App\Domain\WhatsApp;

use App\Security\TenantContextInterface;
use App\Security\TenantContextProviderInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * Repository implementation for WhatsApp domain.
 * Strictly enforces tenant context isolation and safe prepared queries.
 */
final class WhatsAppRepository implements WhatsAppRepositoryInterface
{
    public function __construct(
        private ConnectionInterface $db,
        private ?TenantContextProviderInterface $tenantContextProvider = null,
    ) {
    }

    public function findTemplateById(int $id, ?TenantContextInterface $context = null): ?WhatsAppTemplate
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        if ($ctx !== null && $ctx->isGuest()) {
            return null;
        }

        $query = (new Query($this->db))
            ->from('{{%whatsapp_template}}')
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

        return WhatsAppTemplate::fromDatabaseRow($row);
    }

    public function findTemplateBySlug(string $slug, ?int $accountId = null): ?WhatsAppTemplate
    {
        $query = (new Query($this->db))
            ->from('{{%whatsapp_template}}')
            ->where(['slug' => $slug, 'status' => WhatsAppTemplate::STATUS_ACTIVE]);

        if ($accountId !== null && $accountId > 0) {
            $query->andWhere(['or', ['account_id' => $accountId], ['account_id' => null]])
                ->orderBy(['account_id' => SORT_DESC]);
        } else {
            $query->andWhere(['account_id' => null]);
        }

        /** @var array<string, mixed>|false $row */
        $row = $query->one();
        if ($row === null || $row === false) {
            return null;
        }

        return WhatsAppTemplate::fromDatabaseRow($row);
    }

    public function findTemplatesByAccount(?TenantContextInterface $context = null, ?int $accountId = null): array
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        if ($ctx !== null && $ctx->isGuest()) {
            return [];
        }

        $query = (new Query($this->db))
            ->from('{{%whatsapp_template}}')
            ->where(['status' => WhatsAppTemplate::STATUS_ACTIVE]);

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
            $templates[] = WhatsAppTemplate::fromDatabaseRow($row);
        }

        return $templates;
    }

    public function findAllTemplates(?TenantContextInterface $context = null): array
    {
        return $this->findTemplatesByAccount($context);
    }

    public function findContactById(int $id, ?TenantContextInterface $context = null): ?WhatsAppAddress
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        if ($ctx !== null && $ctx->isGuest()) {
            return null;
        }

        $query = (new Query($this->db))
            ->from('{{%whatsapp_addresses}}')
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

        return WhatsAppAddress::fromDatabaseRow($row);
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
            ->from('{{%whatsapp_addresses}}');

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
            $contacts[] = WhatsAppAddress::fromDatabaseRow($row);
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
            ->from('{{%whatsapp_addresses}}');

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

    public function findQueueById(int $id, ?TenantContextInterface $context = null): ?WhatsAppQueue
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        if ($ctx !== null && $ctx->isGuest()) {
            return null;
        }

        $query = (new Query($this->db))
            ->from('{{%whatsapp_queue}}')
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

        return WhatsAppQueue::fromDatabaseRow($row);
    }

    public function findHistoryByAccount(
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
            ->from('{{%whatsapp_queue}}');

        if ($accountId !== null && $accountId > 0) {
            $query->andWhere(['account_id' => $accountId]);
        } elseif ($ctx !== null && !$ctx->isUnrestricted()) {
            $accessible = $ctx->getAccessibleAccountIds();
            if (empty($accessible)) {
                return [];
            }
            $query->andWhere(['in', 'account_id', $accessible]);
        }

        $limit = max(1, min(100, $pageSize));
        $offset = max(0, ($page - 1) * $limit);

        $rows = $query
            ->orderBy(['id' => SORT_DESC])
            ->limit($limit)
            ->offset($offset)
            ->all();

        $queue = [];
        foreach ($rows as $row) {
            /** @var array<string, mixed> $row */
            $queue[] = WhatsAppQueue::fromDatabaseRow($row);
        }

        return $queue;
    }

    public function countHistoryByAccount(?TenantContextInterface $context = null, ?int $accountId = null): int
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        if ($ctx !== null && $ctx->isGuest()) {
            return 0;
        }

        $query = (new Query($this->db))
            ->from('{{%whatsapp_queue}}');

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
            ->from('{{%whatsapp_queue}}')
            ->where(['status' => WhatsAppQueue::STATUS_QUEUED])
            ->andWhere(['or', ['send_at' => null], ['<=', 'send_at', $now]])
            ->orderBy(['id' => SORT_ASC])
            ->limit(max(1, min(500, $batchSize)))
            ->all();

        $queue = [];
        foreach ($rows as $row) {
            /** @var array<string, mixed> $row */
            $queue[] = WhatsAppQueue::fromDatabaseRow($row);
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

    public function saveTemplate(WhatsAppTemplate $template): int
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
                'UPDATE {{%whatsapp_template}} SET account_id = :accountId, slug = :slug, name = :name, text = :text, protected = :protected, status = :status, updated_at = :updatedAt WHERE id = :id',
                array_merge($params, [':id' => $template->getId()])
            )->execute();
            return $template->getId();
        }

        $this->db->createCommand(
            'INSERT INTO {{%whatsapp_template}} (account_id, slug, name, text, protected, status, created_at, updated_at) VALUES (:accountId, :slug, :name, :text, :protected, :status, :createdAt, :updatedAt)',
            array_merge($params, [':createdAt' => $now])
        )->execute();

        return (int) $this->db->getLastInsertID();
    }

    public function deleteTemplate(int $id): bool
    {
        $rows = $this->db->createCommand(
            'DELETE FROM {{%whatsapp_template}} WHERE id = :id AND protected = 0',
            [':id' => $id]
        )->execute();

        return $rows > 0;
    }

    public function saveContact(WhatsAppAddress $contact): int
    {
        $now = time();
        $params = [
            ':accountId' => $contact->getAccountId(),
            ':name' => $contact->getName(),
            ':phone' => $contact->getPhone(),
            ':status' => $contact->getStatus(),
            ':updatedAt' => $now,
        ];

        if ($contact->getId() > 0) {
            $this->db->createCommand(
                'UPDATE {{%whatsapp_addresses}} SET account_id = :accountId, name = :name, phone = :phone, status = :status, updated_at = :updatedAt WHERE id = :id',
                array_merge($params, [':id' => $contact->getId()])
            )->execute();
            return $contact->getId();
        }

        $this->db->createCommand(
            'INSERT INTO {{%whatsapp_addresses}} (account_id, name, phone, status, created_at, updated_at) VALUES (:accountId, :name, :phone, :status, :createdAt, :updatedAt)',
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
                    'INSERT INTO {{%whatsapp_addresses}} (account_id, name, phone, status, created_at, updated_at) VALUES (:accountId, :name, :phone, :status, :createdAt, :updatedAt)',
                    [
                        ':accountId' => $accountId,
                        ':name' => (string) ($r['name'] ?? ''),
                        ':phone' => (string) ($r['phone'] ?? ''),
                        ':status' => WhatsAppAddress::STATUS_ACTIVE,
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
            'DELETE FROM {{%whatsapp_addresses}} WHERE id = :id',
            [':id' => $id]
        )->execute() > 0;
    }

    public function deleteContactsBatch(array $ids, int $accountId): int
    {
        if (empty($ids)) {
            return 0;
        }

        return $this->db->createCommand()
            ->delete('{{%whatsapp_addresses}}', ['and', ['in', 'id', $ids], ['account_id' => $accountId]])
            ->execute();
    }

    public function enqueueMessage(WhatsAppQueue $message): int
    {
        $now = time();
        $this->db->createCommand(
            'INSERT INTO {{%whatsapp_queue}} (account_id, phone, message, status, send_at, created_at, updated_at) VALUES (:accountId, :phone, :message, :status, :sendAt, :createdAt, :updatedAt)',
            [
                ':accountId' => $message->getAccountId(),
                ':phone' => $message->getPhone(),
                ':message' => $message->getMessage(),
                ':status' => WhatsAppQueue::STATUS_QUEUED,
                ':sendAt' => $message->getSendAt(),
                ':createdAt' => $now,
                ':updatedAt' => $now,
            ]
        )->execute();

        return (int) $this->db->getLastInsertID();
    }

    public function claimQueueJob(int $id, string $lockTime): bool
    {
        $affected = $this->db->createCommand(
            'UPDATE {{%whatsapp_queue}} SET status = :proc, locked_at = :lockTime WHERE id = :id AND status = :queued',
            [
                ':proc' => WhatsAppQueue::STATUS_PROCESSING,
                ':lockTime' => $lockTime,
                ':id' => $id,
                ':queued' => WhatsAppQueue::STATUS_QUEUED,
            ]
        )->execute();

        return $affected > 0;
    }

    public function markQueueSent(int $id, ?string $providerMessageId = null): bool
    {
        $now = date('Y-m-d H:i:s');
        return $this->db->createCommand(
            'UPDATE {{%whatsapp_queue}} SET status = :sent, sent_at = :now, provider_message_id = :pId, locked_at = NULL WHERE id = :id',
            [
                ':sent' => WhatsAppQueue::STATUS_SENT,
                ':now' => $now,
                ':pId' => $providerMessageId,
                ':id' => $id,
            ]
        )->execute() > 0;
    }

    public function markQueueFailed(int $id): bool
    {
        return $this->db->createCommand(
            'UPDATE {{%whatsapp_queue}} SET status = :failed, locked_at = NULL WHERE id = :id',
            [
                ':failed' => WhatsAppQueue::STATUS_FAILED,
                ':id' => $id,
            ]
        )->execute() > 0;
    }

    public function recoverStaleQueueJobs(int $timeoutMinutes = 15): int
    {
        $staleThreshold = date('Y-m-d H:i:s', time() - ($timeoutMinutes * 60));
        return $this->db->createCommand(
            'UPDATE {{%whatsapp_queue}} SET status = :queued, locked_at = NULL WHERE status = :proc AND locked_at < :staleThreshold',
            [
                ':queued' => WhatsAppQueue::STATUS_QUEUED,
                ':proc' => WhatsAppQueue::STATUS_PROCESSING,
                ':staleThreshold' => $staleThreshold,
            ]
        )->execute();
    }
}
