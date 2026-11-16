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
        $isPlatformAdmin = $user->isSuperAdmin() || $user->isAdmin();
        $isAccountAdmin = $user->isAccountAdmin();

        if ($isPlatformAdmin) {
            // Platform-wide feed for Super Admin and System Admin
            // Deduplicate multi-recipient review notifications by grouping on action_url/account_id/title
            $subQuery = (new Query($this->db))
                ->select([
                    'max_id' => 'MAX(n.id)',
                ])
                ->from(['n' => '{{%user_notification}}'])
                ->groupBy([
                    'n.account_id',
                    'n.type',
                    'n.title',
                    'n.action_url',
                ]);

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
                ->where(['n.id' => $subQuery])
                ->orderBy(['n.created_at' => SORT_DESC, 'n.id' => SORT_DESC])
                ->limit($limit)
                ->all();
        } elseif ($isAccountAdmin) {
            // Account Admin: sees all notifications belonging to their account (deduplicated by event)
            $accountId = $user->getAccountId() ?? 0;
            $subQuery = (new Query($this->db))
                ->select([
                    'max_id' => 'MAX(n.id)',
                ])
                ->from(['n' => '{{%user_notification}}'])
                ->where(['n.account_id' => $accountId])
                ->groupBy([
                    'n.account_id',
                    'n.type',
                    'n.title',
                    'n.action_url',
                ]);

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
                ->where(['n.id' => $subQuery])
                ->orderBy(['n.created_at' => SORT_DESC, 'n.id' => SORT_DESC])
                ->limit($limit)
                ->all();
        } else {
            // Worker: strictly assigned to their own user_id and account_id
            $accountId = $user->getAccountId() ?? 0;
            $query = (new Query($this->db))
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
                ->where(['n.user_id' => $userId]);

            if ($accountId > 0) {
                $query->andWhere(['n.account_id' => $accountId]);
            }

            $rows = $query->orderBy(['n.created_at' => SORT_DESC, 'n.id' => SORT_DESC])
                ->limit($limit)
                ->all();
        }

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
        $isPlatformAdmin = $user->isSuperAdmin() || $user->isAdmin();
        $isAccountAdmin = $user->isAccountAdmin();

        if ($isPlatformAdmin) {
            // For platform admins, count distinct unread events across the platform
            $subQuery = (new Query($this->db))
                ->select(['max_id' => 'MAX(id)'])
                ->from('{{%user_notification}}')
                ->where(['is_read' => 0])
                ->groupBy([
                    'account_id',
                    'type',
                    'title',
                    'action_url',
                ]);

            return (int) (new Query($this->db))
                ->from(['sub' => $subQuery])
                ->count();
        }

        if ($isAccountAdmin) {
            // For account admin, count distinct unread events belonging to their workspace
            $accountId = $user->getAccountId() ?? 0;
            $subQuery = (new Query($this->db))
                ->select(['max_id' => 'MAX(id)'])
                ->from('{{%user_notification}}')
                ->where(['account_id' => $accountId, 'is_read' => 0])
                ->groupBy([
                    'account_id',
                    'type',
                    'title',
                    'action_url',
                ]);

            return (int) (new Query($this->db))
                ->from(['sub' => $subQuery])
                ->count();
        }

        // For workers, count strictly their own unread notifications
        $query = (new Query($this->db))
            ->from('{{%user_notification}}')
            ->where(['user_id' => $userId, 'is_read' => 0]);

        $accountId = $user->getAccountId() ?? 0;
        if ($accountId > 0) {
            $query->andWhere(['account_id' => $accountId]);
        }

        return (int) $query->count();
    }

    public function markAsRead(int $id, int $userId): bool
    {
        if ($id <= 0 || $userId <= 0) {
            return false;
        }

        // Check if notification belongs to user or is being updated by platform admin / account admin
        $row = (new Query($this->db))
            ->select(['id', 'user_id', 'account_id', 'type', 'title', 'action_url'])
            ->from('{{%user_notification}}')
            ->where(['id' => $id])
            ->one();

        if (!$row) {
            return false;
        }

        $now = time();

        // Check role from auth_assignment safely
        $role = (new Query($this->db))
            ->select(['item_name'])
            ->from('{{%auth_assignment}}')
            ->where(['user_id' => $userId])
            ->scalar();

        if ($role === User::ROLE_SUPER || $role === User::ROLE_ADMIN) {
            // Mark all sibling records for this shared event as read across the platform
            $this->db->createCommand()
                ->update('{{%user_notification}}', ['is_read' => 1, 'read_at' => $now], [
                    'account_id' => $row['account_id'],
                    'type' => $row['type'],
                    'title' => $row['title'],
                    'action_url' => $row['action_url'],
                    'is_read' => 0,
                ])
                ->execute();

            // Ensure specific clicked id is marked read
            $this->db->createCommand()
                ->update('{{%user_notification}}', ['is_read' => 1, 'read_at' => $now], ['id' => $id])
                ->execute();
            return true;
        }

        // If Account Admin marks an item belonging to their account
        if ($role === User::ROLE_ACCOUNT_ADMIN) {
            $userAccId = (new Query($this->db))
                ->select(['account_id'])
                ->from('{{%user}}')
                ->where(['id' => $userId])
                ->scalar();

            if ($userAccId && (int) $userAccId === (int) $row['account_id']) {
                // Mark all sibling records for this shared event in this account as read
                $this->db->createCommand()
                    ->update('{{%user_notification}}', ['is_read' => 1, 'read_at' => $now], [
                        'account_id' => $row['account_id'],
                        'type' => $row['type'],
                        'title' => $row['title'],
                        'action_url' => $row['action_url'],
                        'is_read' => 0,
                    ])
                    ->execute();

                $this->db->createCommand()
                    ->update('{{%user_notification}}', ['is_read' => 1, 'read_at' => $now], ['id' => $id])
                    ->execute();
                return true;
            }
        }

        // If user owns the notification, mark it read
        if ((int) $row['user_id'] === $userId) {
            $this->db->createCommand()
                ->update('{{%user_notification}}', ['is_read' => 1, 'read_at' => $now], ['id' => $id])
                ->execute();
            return true;
        }

        return false;
    }

    public function markAllAsRead(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $now = time();

        // Query role from auth_assignment safely
        $role = (new Query($this->db))
            ->select(['item_name'])
            ->from('{{%auth_assignment}}')
            ->where(['user_id' => $userId])
            ->scalar();

        $isPlatformAdmin = $role === User::ROLE_SUPER || $role === User::ROLE_ADMIN;

        if ($isPlatformAdmin) {
            // Platform admin marks all notifications across platform as read
            return $this->db->createCommand()
                ->update('{{%user_notification}}', ['is_read' => 1, 'read_at' => $now], ['is_read' => 0])
                ->execute();
        }

        // If Account Admin, mark all notifications for their user in their account as read
        if ($role === User::ROLE_ACCOUNT_ADMIN) {
            $accountId = (new Query($this->db))
                ->select(['account_id'])
                ->from('{{%user}}')
                ->where(['id' => $userId])
                ->scalar();

            if ($accountId) {
                // Mark user's own notifications in this account as read, plus account-level items
                return $this->db->createCommand()
                    ->update('{{%user_notification}}', ['is_read' => 1, 'read_at' => $now], [
                        'and',
                        ['is_read' => 0],
                        ['or', ['user_id' => $userId], ['account_id' => (int) $accountId]]
                    ])
                    ->execute();
            }
        }

        // Tenant user marks all their own notifications as read
        return $this->db->createCommand()
            ->update('{{%user_notification}}', ['is_read' => 1, 'read_at' => $now], ['user_id' => $userId, 'is_read' => 0])
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
        $isPlatformAdmin = $user->isSuperAdmin() || $user->isAdmin();
        $isAccountAdmin = $user->isAccountAdmin();
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

        if ($isPlatformAdmin) {
            $subQuery = (new Query($this->db))
                ->select(['max_id' => 'MAX(n.id)'])
                ->from(['n' => '{{%user_notification}}']);

            if ($status === 'unread') {
                $subQuery->andWhere(['n.is_read' => 0]);
            } elseif ($status === 'read') {
                $subQuery->andWhere(['n.is_read' => 1]);
            }

            if ($typeCondition !== null) {
                $subQuery->andWhere($typeCondition);
            }

            $subQuery->groupBy([
                'n.account_id',
                'n.type',
                'n.title',
                'n.action_url',
            ]);

            $total = (int) (new Query($this->db))
                ->from(['sub' => $subQuery])
                ->count();

            $totalPages = $total > 0 ? (int) ceil($total / $pageSize) : 1;

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
                ->where(['n.id' => $subQuery])
                ->orderBy(['n.created_at' => SORT_DESC, 'n.id' => SORT_DESC])
                ->offset($offset)
                ->limit($pageSize)
                ->all();
        } elseif ($isAccountAdmin) {
            $accountId = $user->getAccountId() ?? 0;
            $subQuery = (new Query($this->db))
                ->select(['max_id' => 'MAX(n.id)'])
                ->from(['n' => '{{%user_notification}}'])
                ->where(['n.account_id' => $accountId]);

            if ($status === 'unread') {
                $subQuery->andWhere(['n.is_read' => 0]);
            } elseif ($status === 'read') {
                $subQuery->andWhere(['n.is_read' => 1]);
            }

            if ($typeCondition !== null) {
                $subQuery->andWhere($typeCondition);
            }

            $subQuery->groupBy([
                'n.account_id',
                'n.type',
                'n.title',
                'n.action_url',
            ]);

            $total = (int) (new Query($this->db))
                ->from(['sub' => $subQuery])
                ->count();

            $totalPages = $total > 0 ? (int) ceil($total / $pageSize) : 1;

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
                ->where(['n.id' => $subQuery])
                ->orderBy(['n.created_at' => SORT_DESC, 'n.id' => SORT_DESC])
                ->offset($offset)
                ->limit($pageSize)
                ->all();
        } else {
            $accountId = $user->getAccountId() ?? 0;
            $query = (new Query($this->db))
                ->from(['n' => '{{%user_notification}}'])
                ->where(['n.user_id' => $userId]);

            if ($accountId > 0) {
                $query->andWhere(['n.account_id' => $accountId]);
            }

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
        }

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

