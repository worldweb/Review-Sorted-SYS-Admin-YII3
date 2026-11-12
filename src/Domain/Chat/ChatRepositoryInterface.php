<?php

declare(strict_types=1);

namespace App\Domain\Chat;

use App\Security\TenantContextInterface;

/**
 * Chat Repository Interface.
 * Defines operations for managing conversation threads and messages originating
 * from Facebook Messenger and Instagram Direct.
 */
interface ChatRepositoryInterface
{
    /**
     * Find a chat thread by service config ID and external customer ID.
     */
    public function findChatByServiceConfigAndCustomer(
        int $serviceConfigId,
        string $customerId,
        ?TenantContextInterface $context = null
    ): ?Chat;

    /**
     * Find a chat thread by its internal ID.
     */
    public function findChatById(int $id, ?TenantContextInterface $context = null): ?Chat;

    /**
     * Create a new chat thread.
     */
    public function createChat(
        int $accountId,
        int $serviceConfigId,
        string $customerId,
        string $customerName,
        string $startDate
    ): int;

    /**
     * Mark an existing chat thread as unread (read = 0) and update timestamp.
     */
    public function markChatUnread(int $chatId): bool;

    /**
     * Mark an existing chat thread as read (read = 1) and update timestamp.
     */
    public function markChatRead(int $chatId): bool;

    /**
     * Check if a message with the given external object ID (e.g. Meta mid) already exists.
     * Used for webhook deduplication.
     */
    public function isChatMessageExists(string $objId): bool;

    /**
     * Insert a message record into chat_message.
     */
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
    ): int;

    /**
     * Find all messages in a given chat thread.
     *
     * @return ChatMessage[]
     */
    public function findMessagesByChatId(int $chatId, ?TenantContextInterface $context = null): array;
}

