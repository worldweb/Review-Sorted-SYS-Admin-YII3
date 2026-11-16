<?php

declare(strict_types=1);

namespace App\Domain\Notification;

use App\Domain\User\User;

/**
 * Interface for Notification data access and lifecycle operations.
 */
interface NotificationRepositoryInterface
{
    /**
     * Creates a new notification record.
     */
    public function create(
        int $userId,
        int $accountId,
        string $type,
        string $title,
        ?string $message = null,
        ?string $actionUrl = null,
        int $createdAt = 0
    ): int;

    /**
     * Retrieves the latest notifications for the given user respecting their role visibility.
     * Platform administrators (super/admin) receive platform-wide notifications with deduplication.
     *
     * @return Notification[]
     */
    public function findLatestForUser(User $user, int $limit = 10): array;

    /**
     * Counts unread notifications for the given user.
     */
    public function countUnreadForUser(User $user): int;

    /**
     * Marks a single notification as read if owned by the user.
     */
    public function markAsRead(int $id, int $userId): bool;

    /**
     * Marks all notifications as read for the user.
     *
     * @return int Number of updated records.
     */
    public function markAllAsRead(int $userId): int;

    /**
     * Checks if a similar notification already exists within a given time window (deduplication).
     */
    public function existsSimilar(int $accountId, string $type, string $title, int $windowSeconds = 300): bool;

    /**
     * Finds paginated notification history for the given user with optional status and type filters.
     * Platform administrators receive deduplicated platform-wide notifications with business name.
     *
     * @return array{items: Notification[], total: int, page: int, pageSize: int, totalPages: int}
     */
    public function findHistoryForUser(
        User $user,
        ?string $status = null,
        ?string $type = null,
        int $page = 1,
        int $pageSize = 20
    ): array;
}

