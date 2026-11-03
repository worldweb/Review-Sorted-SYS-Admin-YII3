<?php

declare(strict_types=1);

namespace App\Domain\Chat;

use App\Security\TenantContextInterface;

/**
 * Interface for Chat Repository.
 */
interface ChatRepositoryInterface
{
    /**
     * Find a single thread by ID with all messages.
     */
    public function findById(int $id, ?TenantContextInterface $context = null): ?Chat;

    /**
     * Find all chat threads for the current tenant context.
     *
     * @return array<int, Chat>
     */
    public function findAllChats(?TenantContextInterface $context = null, int $limit = 30, int $offset = 0): array;

    /**
     * Count chat threads.
     */
    public function countChats(?TenantContextInterface $context = null): int;

    /**
     * Mark a thread as read.
     */
    public function markAsRead(int $chatId): bool;

    /**
     * Send an outgoing message / reply to a chat thread.
     */
    public function sendReply(int $chatId, int $userId, string $text, ?TenantContextInterface $context = null): bool;
}
