<?php

declare(strict_types=1);

namespace App\Domain\Notification;

use App\Domain\User\User;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * NotificationService.
 * Coordinates in-app notification dispatching across tenant users, account administrators, and platform admins.
 */
final class NotificationService
{
    private LoggerInterface $logger;

    public function __construct(
        private NotificationRepositoryInterface $notificationRepo,
        private ConnectionInterface $db,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Sends a notification to a specific user.
     */
    public function notifyUser(
        int $userId,
        int $accountId,
        string $type,
        string $title,
        ?string $message = null,
        ?string $actionUrl = null
    ): ?int {
        try {
            return $this->notificationRepo->create($userId, $accountId, $type, $title, $message, $actionUrl);
        } catch (Throwable $e) {
            $this->logger->warning('[NotificationService] notifyUser failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Sends a notification to ALL active users in an account (Account Admins and Workers).
     * Used for new Google and Facebook reviews so all team members are notified.
     *
     * @return int Count of notifications created.
     */
    public function notifyAccountUsers(
        int $accountId,
        string $type,
        string $title,
        ?string $message = null,
        ?string $actionUrl = null
    ): int {
        if ($accountId <= 0) {
            return 0;
        }

        try {
            // Find all active users in the account
            $userIds = (new Query($this->db))
                ->select(['id'])
                ->distinct()
                ->from('{{%user}}')
                ->where(['account_id' => $accountId, 'status' => User::STATUS_ACTIVE])
                ->column();

            if (empty($userIds)) {
                return 0;
            }

            $count = 0;
            $now = time();
            foreach ($userIds as $userId) {
                $this->notificationRepo->create((int) $userId, $accountId, $type, $title, $message, $actionUrl, $now);
                $count++;
            }

            return $count;
        } catch (Throwable $e) {
            $this->logger->warning('[NotificationService] notifyAccountUsers failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Sends a notification to all active Account Administrators for an account.
     */
    public function notifyAccountAdmins(
        int $accountId,
        string $type,
        string $title,
        ?string $message = null,
        ?string $actionUrl = null,
        int $dedupWindowSeconds = 0
    ): int {
        if ($accountId <= 0) {
            return 0;
        }

        try {
            if ($dedupWindowSeconds > 0 && $this->notificationRepo->existsSimilar($accountId, $type, $title, $dedupWindowSeconds)) {
                return 0;
            }

            $adminIds = (new Query($this->db))
                ->select(['u.id'])
                ->distinct()
                ->from(['u' => '{{%user}}'])
                ->innerJoin(['a' => '{{%auth_assignment}}'], 'a.user_id = u.id')
                ->where([
                    'u.account_id' => $accountId,
                    'a.item_name' => User::ROLE_ACCOUNT_ADMIN,
                    'u.status' => User::STATUS_ACTIVE,
                ])
                ->column();

            if (empty($adminIds)) {
                return 0;
            }

            $count = 0;
            $now = time();
            foreach ($adminIds as $adminId) {
                $this->notificationRepo->create((int) $adminId, $accountId, $type, $title, $message, $actionUrl, $now);
                $count++;
            }

            return $count;
        } catch (Throwable $e) {
            $this->logger->warning('[NotificationService] notifyAccountAdmins failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Sends a notification to all active Platform Administrators (super and admin).
     */
    public function notifyPlatformAdmins(
        string $type,
        string $title,
        ?string $message = null,
        ?string $actionUrl = null,
        int $accountId = 0
    ): int {
        try {
            $adminIds = (new Query($this->db))
                ->select(['u.id'])
                ->distinct()
                ->from(['u' => '{{%user}}'])
                ->innerJoin(['a' => '{{%auth_assignment}}'], 'a.user_id = u.id')
                ->where(['u.status' => User::STATUS_ACTIVE])
                ->andWhere(['in', 'a.item_name', [User::ROLE_SUPER, User::ROLE_ADMIN]])
                ->column();

            if (empty($adminIds)) {
                return 0;
            }

            $count = 0;
            $now = time();
            foreach ($adminIds as $adminId) {
                $this->notificationRepo->create((int) $adminId, $accountId, $type, $title, $message, $actionUrl, $now);
                $count++;
            }

            return $count;
        } catch (Throwable $e) {
            $this->logger->warning('[NotificationService] notifyPlatformAdmins failed: ' . $e->getMessage());
            return 0;
        }
    }
}

