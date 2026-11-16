<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Notification;

use App\Domain\Notification\Notification;
use App\Domain\Notification\NotificationRepositoryInterface;
use App\Domain\User\User;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * Database implementation of NotificationRepositoryInterface.
 * Manages notification lifecycle, tenant boundary isolation, and platform-wide administrator aggregation.
 */
final class NotificationRepository implements NotificationRepositoryInterface
{
    public function __construct(
        private ConnectionInterface $db,
    ) {
    }

    public function create(
        int $userId,
        int $accountId,
        string $type,
        string $title,
        ?string $message = null,
        ?string $actionUrl = null,
        int $createdAt = 0
    ): int {
        $now = $createdAt > 0 ? $createdAt : time();

        $this->db->createCommand()
            ->insert('{{%user_notification}}', [
                'user_id' => $userId,
                'account_id' => $accountId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'action_url' => $actionUrl,
                'is_read' => 0,
                'read_at' => null,
                'created_at' => $now,
            ])
            ->execute();

        return (int) $this->db->getLastInsertID();
    }

    public function findLatestForUser(User $user, int $limit = 10): array
    {
        $userId = $user->getId();
        if ($userId <= 0) {
            return [];
        }

        $isPlatformAdmin = $user->isSuperAdmin() || $user->isAdmin();

        $rows = (new Query($this->db))
            ->select([
                'n.id',
                'n.user_id',
                'n.account_id',
                'n.type',
                'n.title',
                'n.message',
                'n.action_url',
                'n.is_read',
                'n.read_at',
                'n.created_at',
                'account_name' => 'a.business_name',
            ])
            ->from(['n' => '{{%user_notification}}'])
            ->leftJoin(['a' => '{{%account}}'], 'a.id = n.account_id')
            ->where(['n.user_id' => $userId])
            ->orderBy(['n.created_at' => SORT_DESC, 'n.id' => SORT_DESC])
            ->limit($limit)
            ->all();

        $notifications = [];
        foreach ($rows as $row) {
            $accountName = isset($row['account_name']) && is_string($row['account_name']) ? $row['account_name'] : null;
            $title = (string) ($row['title'] ?? '');

            // For platform admins, prepend workspace name if available and not already included
            if ($isPlatformAdmin && $accountName !== null && $accountName !== '' && !str_starts_with($title, '[')) {
                $title = '[' . $accountName . '] ' . $title;
            }

            $resolvedUrl = $this->resolveActionUrl($row);

            $notifications[] = new Notification(
                (int) $row['id'],
                (int) $row['user_id'],
                (int) ($row['account_id'] ?? 0),
                (string) ($row['type'] ?? 'info'),
                $title,
                $row['message'] !== null ? (string) $row['message'] : null,
                $resolvedUrl,
                (bool) ($row['is_read'] ?? 0),
                isset($row['read_at']) && is_numeric($row['read_at']) ? (int) $row['read_at'] : null,
                (int) ($row['created_at'] ?? time()),
                $accountName
            );
        }

        return $notifications;
    }

    public function countUnreadForUser(User $user): int
    {
        $userId = $user->getId();
        if ($userId <= 0) {
            return 0;
        }

        return (int) (new Query($this->db))
            ->from('{{%user_notification}}')
            ->where(['user_id' => $userId, 'is_read' => 0])
            ->count();
    }

    public function markAsRead(int $id, int $userId): bool
    {
        if ($id <= 0 || $userId <= 0) {
            return false;
        }

        $now = time();

        $affected = $this->db->createCommand()
            ->update('{{%user_notification}}', ['is_read' => 1, 'read_at' => $now], [
                'id' => $id,
                'user_id' => $userId,
                'is_read' => 0,
            ])
            ->execute();

        if ($affected > 0) {
            return true;
        }

        return (new Query($this->db))
            ->from('{{%user_notification}}')
            ->where(['id' => $id, 'user_id' => $userId])
            ->exists();
    }

    public function markAllAsRead(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $now = time();

        return $this->db->createCommand()
            ->update('{{%user_notification}}', ['is_read' => 1, 'read_at' => $now], [
                'user_id' => $userId,
                'is_read' => 0,
            ])
            ->execute();
    }

    public function existsSimilar(int $accountId, string $type, string $title, int $windowSeconds = 300): bool
    {
        $since = time() - $windowSeconds;

        return (new Query($this->db))
            ->from('{{%user_notification}}')
            ->where(['account_id' => $accountId, 'type' => $type, 'title' => $title])
            ->andWhere(['>=', 'created_at', $since])
            ->exists();
    }

    public function findHistoryForUser(
        User $user,
        ?string $status = null,
        ?string $type = null,
        int $page = 1,
        int $pageSize = 20
    ): array {
        $userId = $user->getId();
        if ($userId <= 0) {
            return [
                'items' => [],
                'total' => 0,
                'page' => 1,
                'pageSize' => $pageSize,
                'totalPages' => 1,
            ];
        }

        $isPlatformAdmin = $user->isSuperAdmin() || $user->isAdmin();
        $page = max(1, $page);
        $pageSize = max(1, min(100, $pageSize));
        $offset = ($page - 1) * $pageSize;

        // Build type condition
        $typeCondition = null;
        if ($type !== null && $type !== '' && $type !== 'all') {
            if ($type === 'review' || $type === 'new_review') {
                $typeCondition = ['in', 'n.type', ['review', 'new_review']];
            } else {
                $typeCondition = ['n.type' => $type];
            }
        }

        $query = (new Query($this->db))
            ->from(['n' => '{{%user_notification}}'])
            ->where(['n.user_id' => $userId]);

        if ($status === 'unread') {
            $query->andWhere(['n.is_read' => 0]);
        } elseif ($status === 'read') {
            $query->andWhere(['n.is_read' => 1]);
        }

        if ($typeCondition !== null) {
            $query->andWhere($typeCondition);
        }

        $total = (int) $query->count();
        $totalPages = $total > 0 ? (int) ceil($total / $pageSize) : 1;

        $rows = $query
            ->select([
                'n.id',
                'n.user_id',
                'n.account_id',
                'n.type',
                'n.title',
                'n.message',
                'n.action_url',
                'n.is_read',
                'n.read_at',
                'n.created_at',
                'account_name' => 'a.business_name',
            ])
            ->leftJoin(['a' => '{{%account}}'], 'a.id = n.account_id')
            ->orderBy(['n.created_at' => SORT_DESC, 'n.id' => SORT_DESC])
            ->offset($offset)
            ->limit($pageSize)
            ->all();

        $notifications = [];
        foreach ($rows as $row) {
            $accountName = isset($row['account_name']) && is_string($row['account_name']) ? $row['account_name'] : null;
            $title = (string) ($row['title'] ?? '');

            if ($isPlatformAdmin && $accountName !== null && $accountName !== '' && !str_starts_with($title, '[')) {
                $title = '[' . $accountName . '] ' . $title;
            }

            $resolvedUrl = $this->resolveActionUrl($row);

            $notifications[] = new Notification(
                (int) $row['id'],
                (int) $row['user_id'],
                (int) ($row['account_id'] ?? 0),
                (string) ($row['type'] ?? 'info'),
                $title,
                $row['message'] !== null ? (string) $row['message'] : null,
                $resolvedUrl,
                (bool) ($row['is_read'] ?? 0),
                isset($row['read_at']) && is_numeric($row['read_at']) ? (int) $row['read_at'] : null,
                (int) ($row['created_at'] ?? time()),
                $accountName
            );
        }

        return [
            'items' => $notifications,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => $totalPages,
        ];
    }

    /**
     * Resolves the primary role for a user deterministically from auth_assignment.
     */
    public function resolveUserPrimaryRole(int $userId): ?string
    {
        if ($userId <= 0) {
            return null;
        }

        $roles = (new Query($this->db))
            ->select(['item_name'])
            ->from('{{%auth_assignment}}')
            ->where(['user_id' => $userId])
            ->column();

        if (empty($roles)) {
            return null;
        }

        if (in_array(User::ROLE_SUPER, $roles, true)) {
            return User::ROLE_SUPER;
        }
        if (in_array(User::ROLE_ADMIN, $roles, true)) {
            return User::ROLE_ADMIN;
        }
        if (in_array(User::ROLE_ACCOUNT_ADMIN, $roles, true)) {
            return User::ROLE_ACCOUNT_ADMIN;
        }
        if (in_array(User::ROLE_WORKER, $roles, true)) {
            return User::ROLE_WORKER;
        }

        return (string) ($roles[0] ?? '');
    }

    /**
     * Resolves and normalizes notification destination action URLs.
     * Links generic review notifications to their specific review details view where possible.
     */
    private function resolveActionUrl(array $row): ?string
    {
        $actionUrl = isset($row['action_url']) && is_string($row['action_url']) ? trim($row['action_url']) : '';
        $type = (string) ($row['type'] ?? '');
        $message = (string) ($row['message'] ?? '');
        $accountId = (int) ($row['account_id'] ?? 0);

        // If actionUrl is empty or generic '/reviews', attempt to link to specific review detail
        if (($actionUrl === '' || $actionUrl === '/reviews' || $actionUrl === '/sys-yii3/reviews') && ($type === 'review' || $type === 'new_review')) {
            if ($accountId > 0 && preg_match('/received from (.*?):/i', $message, $m)) {
                $customerName = trim($m[1]);
                $reviewId = (new Query($this->db))
                    ->select(['id'])
                    ->from('{{%review}}')
                    ->where(['account_id' => $accountId, 'customer_name' => $customerName])
                    ->orderBy(['id' => SORT_DESC])
                    ->limit(1)
                    ->scalar();

                if ($reviewId) {
                    $actionUrl = '/reviews/view?id=' . (int) $reviewId;
                }
            }
            if ($actionUrl === '') {
                $actionUrl = '/reviews';
            }
        }

        if ($actionUrl === '' || $actionUrl === '#') {
            return null;
        }

        return \App\Environment::url($actionUrl);
    }
}

