<?php

declare(strict_types=1);

namespace App\Domain\Chat;

use App\Security\TenantContextInterface;
use App\Security\TenantContextProviderInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * ChatRepository.
 * Manages Chat threads and ChatMessages using prepared parameterized SQL.
 * Supports multi-tenant isolation and webhook deduplication.
 */
final class ChatRepository implements ChatRepositoryInterface
{
    public function __construct(
        private ConnectionInterface $db,
        private ?TenantContextProviderInterface $tenantContextProvider = null,
    ) {
    }

    public function findChatByServiceConfigAndCustomer(
        int $serviceConfigId,
        string $customerId,
        ?TenantContextInterface $context = null
    ): ?Chat {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        $sql = 'SELECT * FROM {{%chat}} WHERE service_config_id = :service_config_id AND customer_id = :customer_id';
        $params = [
            ':service_config_id' => $serviceConfigId,
            ':customer_id' => $customerId,
        ];

        if ($ctx !== null && !$ctx->isUnrestricted() && !$ctx->isGuest()) {
            $accessible = $ctx->getAccessibleAccountIds();
            if (empty($accessible)) {
                return null;
            }
            $placeholders = [];
            foreach ($accessible as $idx => $accId) {
                $key = ':acc_' . $idx;
                $placeholders[] = $key;
                $params[$key] = $accId;
            }
            $sql .= ' AND account_id IN (' . implode(',', $placeholders) . ')';
        }

        $sql .= ' LIMIT 1';

        $row = $this->db->createCommand($sql, $params)->queryOne();
        if ($row === null || $row === false) {
            return null;
        }

        return Chat::fromDatabaseRow($row);
    }

    public function findChatById(int $id, ?TenantContextInterface $context = null): ?Chat
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        $sql = 'SELECT * FROM {{%chat}} WHERE id = :id';
        $params = [':id' => $id];

        if ($ctx !== null && !$ctx->isUnrestricted() && !$ctx->isGuest()) {
            $accessible = $ctx->getAccessibleAccountIds();
            if (empty($accessible)) {
                return null;
            }
            $placeholders = [];
            foreach ($accessible as $idx => $accId) {
                $key = ':acc_' . $idx;
                $placeholders[] = $key;
                $params[$key] = $accId;
            }
            $sql .= ' AND account_id IN (' . implode(',', $placeholders) . ')';
        }

        $row = $this->db->createCommand($sql, $params)->queryOne();
        if ($row === null || $row === false) {
            return null;
        }

        return Chat::fromDatabaseRow($row);
    }

    public function createChat(
        int $accountId,
        int $serviceConfigId,
        string $customerId,
        string $customerName,
        string $startDate
    ): int {
        $now = time();
        $sql = 'INSERT INTO {{%chat}} (account_id, service_config_id, customer_id, customer_name, start_date, `read`, created_at, updated_at) '
             . 'VALUES (:account_id, :service_config_id, :customer_id, :customer_name, :start_date, :read, :created_at, :updated_at)';

        $this->db->createCommand($sql, [
            ':account_id' => $accountId,
            ':service_config_id' => $serviceConfigId,
            ':customer_id' => $customerId,
            ':customer_name' => $customerName,
            ':start_date' => $startDate,
            ':read' => Chat::STATUS_NOT_READ,
            ':created_at' => $now,
            ':updated_at' => $now,
        ])->execute();

        return (int) $this->db->getLastInsertID();
    }

    public function markChatUnread(int $chatId): bool
    {
        $now = time();
        $sql = 'UPDATE {{%chat}} SET `read` = :read, updated_at = :updated_at WHERE id = :id';

        $affected = $this->db->createCommand($sql, [
            ':read' => Chat::STATUS_NOT_READ,
            ':updated_at' => $now,
            ':id' => $chatId,
        ])->execute();

        return $affected > 0;
    }

    public function markChatRead(int $chatId): bool
    {
        $now = time();
        $sql = 'UPDATE {{%chat}} SET `read` = :read, updated_at = :updated_at WHERE id = :id';

        $affected = $this->db->createCommand($sql, [
            ':read' => Chat::STATUS_READ,
            ':updated_at' => $now,
            ':id' => $chatId,
        ])->execute();

        return $affected > 0;
    }

    public function isChatMessageExists(string $objId): bool
    {
        if (trim($objId) === '') {
            return false;
        }

        $sql = 'SELECT COUNT(*) FROM {{%chat_message}} WHERE obj_id = :obj_id';
        $count = (int) $this->db->createCommand($sql, [':obj_id' => $objId])->queryScalar();

        return $count > 0;
    }

    public function insertChatMessage(
        int $accountId,
        int $chatId,
        int $serviceConfigId,
        ?string $objId,
        ?string $customerId,
        ?string $customerName,
        string $text,
        ?string $extra,
        string $date,
        string $type = ChatMessage::TYPE_RECEIVED,
        ?int $userId = null
    ): int {
        $now = time();
        $sql = 'INSERT INTO {{%chat_message}} (account_id, user_id, service_config_id, chat_id, type, obj_id, customer_id, customer_name, chat_text, chat_extra, chat_date, created_at, updated_at) '
             . 'VALUES (:account_id, :user_id, :service_config_id, :chat_id, :type, :obj_id, :customer_id, :customer_name, :chat_text, :chat_extra, :chat_date, :created_at, :updated_at)';

        $this->db->createCommand($sql, [
            ':account_id' => $accountId,
            ':user_id' => $userId,
            ':service_config_id' => $serviceConfigId,
            ':chat_id' => $chatId,
            ':type' => $type,
            ':obj_id' => $objId,
            ':customer_id' => $customerId,
            ':customer_name' => $customerName,
            ':chat_text' => $text,
            ':chat_extra' => $extra,
            ':chat_date' => $date,
            ':created_at' => $now,
            ':updated_at' => $now,
        ])->execute();

        return (int) $this->db->getLastInsertID();
    }

    public function findMessagesByChatId(int $chatId, ?TenantContextInterface $context = null): array
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        $sql = 'SELECT * FROM {{%chat_message}} WHERE chat_id = :chat_id';
        $params = [':chat_id' => $chatId];

        if ($ctx !== null && !$ctx->isUnrestricted() && !$ctx->isGuest()) {
            $accessible = $ctx->getAccessibleAccountIds();
            if (empty($accessible)) {
                return [];
            }
            $placeholders = [];
            foreach ($accessible as $idx => $accId) {
                $key = ':acc_' . $idx;
                $placeholders[] = $key;
                $params[$key] = $accId;
            }
            $sql .= ' AND account_id IN (' . implode(',', $placeholders) . ')';
        }

        $sql .= ' ORDER BY chat_date ASC, id ASC';

        $rows = $this->db->createCommand($sql, $params)->queryAll();
        $messages = [];
        foreach ($rows as $row) {
            $messages[] = ChatMessage::fromDatabaseRow($row);
        }

        return $messages;
    }
}

