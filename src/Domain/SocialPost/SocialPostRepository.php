<?php

declare(strict_types=1);

namespace App\Domain\SocialPost;

use App\Security\TenantContextInterface;
use App\Security\TenantContextProviderInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;

final class SocialPostRepository implements SocialPostRepositoryInterface
{
    public function __construct(
        private ConnectionInterface $db,
        private ?TenantContextProviderInterface $tenantContextProvider = null,
    ) {
    }

    public function findById(int $id, ?TenantContextInterface $context = null): ?SocialPost
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        if ($ctx !== null && $ctx->isGuest()) {
            return null;
        }

        $query = (new Query($this->db))
            ->select([
                'sp.*',
                'author_email' => 'u.email',
                'account_business_name' => 'a.business_name',
            ])
            ->from(['sp' => '{{%social_post}}'])
            ->leftJoin(['u' => '{{%user}}'], 'u.id = sp.user_id')
            ->leftJoin(['a' => '{{%account}}'], 'a.id = sp.account_id')
            ->where(['sp.id' => $id]);

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $accessible = $ctx->getAccessibleAccountIds();
            if (empty($accessible)) {
                return null;
            }
            $query->andWhere(['in', 'sp.account_id', $accessible]);
        }

        /** @var array<string, mixed>|false $row */
        $row = $query->one();
        if ($row === null || $row === false) {
            return null;
        }

        $post = SocialPost::fromDatabaseRow($row);
        $destinations = $this->findDestinationsForPost($id);

        return $post->withDestinations($destinations);
    }

    public function findAllPosts(?TenantContextInterface $context = null, int $limit = 20, int $offset = 0, ?string $typeFilter = null, array $filters = []): array
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        $query = (new Query($this->db))
            ->select([
                'sp.*',
                'author_email' => 'u.email',
                'account_business_name' => 'a.business_name',
            ])
            ->distinct()
            ->from(['sp' => '{{%social_post}}'])
            ->leftJoin(['u' => '{{%user}}'], 'u.id = sp.user_id')
            ->leftJoin(['a' => '{{%account}}'], 'a.id = sp.account_id');

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $accessible = $ctx->getAccessibleAccountIds();
            if (empty($accessible)) {
                return [];
            }
            $query->andWhere(['in', 'sp.account_id', $accessible]);
        }

        if (!empty($typeFilter)) {
            $query->andWhere(['sp.post_type' => $typeFilter]);
        }

        if (!empty($filters['q'])) {
            $q = trim((string) $filters['q']);
            if (is_numeric($q)) {
                $query->andWhere(['or', ['sp.id' => (int) $q], ['sp.account_id' => (int) $q]]);
            } else {
                $query->andWhere(['or', ['like', 'sp.content', $q], ['like', 'a.business_name', $q]]);
            }
        }

        if (!empty($filters['date_from'])) {
            $query->andWhere(['>=', 'sp.scheduled_at', $filters['date_from'] . ' 00:00:00']);
        }
        if (!empty($filters['date_to'])) {
            $query->andWhere(['<=', 'sp.scheduled_at', $filters['date_to'] . ' 23:59:59']);
        }

        // Database-level filtering for destination platform and status
        if (!empty($filters['platform']) || !empty($filters['status'])) {
            $query->innerJoin(['d' => '{{%social_post_destination}}'], 'd.social_post_id = sp.id');
            if (!empty($filters['platform'])) {
                $query->andWhere(['d.platform' => (string) $filters['platform']]);
            }
            if (!empty($filters['status'])) {
                $st = (string) $filters['status'];
                if ($st === 'cancelled') {
                    $query->andWhere(['d.error_message' => 'Cancelled by user']);
                } else {
                    $query->andWhere(['d.status' => $st]);
                    $query->andWhere(['or', ['d.error_message' => null], ['!=', 'd.error_message', 'Cancelled by user']]);
                }
            }
        }

        $query->orderBy(['sp.id' => SORT_DESC])
            ->limit($limit)
            ->offset($offset);

        $rows = $query->all();
        $posts = [];
        foreach ($rows as $row) {
            $p = SocialPost::fromDatabaseRow($row);
            $destinations = $this->findDestinationsForPost($p->getId());
            $posts[] = $p->withDestinations($destinations);
        }

        return $posts;
    }

    public function countPosts(?TenantContextInterface $context = null, ?string $typeFilter = null, array $filters = []): int
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        $query = (new Query($this->db))
            ->select(['total' => new Expression('COUNT(DISTINCT sp.id)')])
            ->from(['sp' => '{{%social_post}}'])
            ->leftJoin(['a' => '{{%account}}'], 'a.id = sp.account_id');

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $accessible = $ctx->getAccessibleAccountIds();
            if (empty($accessible)) {
                return 0;
            }
            $query->andWhere(['in', 'sp.account_id', $accessible]);
        }

        if (!empty($typeFilter)) {
            $query->andWhere(['sp.post_type' => $typeFilter]);
        }

        if (!empty($filters['q'])) {
            $q = trim((string) $filters['q']);
            if (is_numeric($q)) {
                $query->andWhere(['or', ['sp.id' => (int) $q], ['sp.account_id' => (int) $q]]);
            } else {
                $query->andWhere(['or', ['like', 'sp.content', $q], ['like', 'a.business_name', $q]]);
            }
        }

        if (!empty($filters['date_from'])) {
            $query->andWhere(['>=', 'sp.scheduled_at', $filters['date_from'] . ' 00:00:00']);
        }
        if (!empty($filters['date_to'])) {
            $query->andWhere(['<=', 'sp.scheduled_at', $filters['date_to'] . ' 23:59:59']);
        }

        // Database-level filtering for destination platform and status
        if (!empty($filters['platform']) || !empty($filters['status'])) {
            $query->innerJoin(['d' => '{{%social_post_destination}}'], 'd.social_post_id = sp.id');
            if (!empty($filters['platform'])) {
                $query->andWhere(['d.platform' => (string) $filters['platform']]);
            }
            if (!empty($filters['status'])) {
                $st = (string) $filters['status'];
                if ($st === 'cancelled') {
                    $query->andWhere(['d.error_message' => 'Cancelled by user']);
                } else {
                    $query->andWhere(['d.status' => $st]);
                    $query->andWhere(['or', ['d.error_message' => null], ['!=', 'd.error_message', 'Cancelled by user']]);
                }
            }
        }

        return (int) ($query->scalar() ?? 0);
    }

    public function findPendingDestinations(int $limit = 20, array $supportedPlatforms = []): array
    {
        if (empty($supportedPlatforms)) {
            // Step 1 Foundation: When no platform resolvers are registered, return empty list immediately.
            // Queued destinations remain untouched in 'queued' status.
            return [];
        }

        $rows = (new Query($this->db))
            ->from('{{%social_post_destination}}')
            ->where(['status' => SocialPostDestination::STATUS_QUEUED])
            ->andWhere(['<=', 'scheduled_at', date('Y-m-d H:i:s')])
            ->andWhere(['platform' => $supportedPlatforms])
            ->orderBy(['id' => SORT_ASC])
            ->limit($limit)
            ->all();

        $destinations = [];
        foreach ($rows as $row) {
            $destinations[] = SocialPostDestination::fromDatabaseRow($row);
        }

        return $destinations;
    }

    public function claimPendingDestination(int $id, string $lockTime): bool
    {
        $now = time();
        $affected = $this->db->createCommand(
            'UPDATE {{%social_post_destination}} ' .
            'SET status = :proc, locked_at = :lockTime, updated_at = :now ' .
            'WHERE id = :id AND status = :queued',
            [
                ':proc' => SocialPostDestination::STATUS_PROCESSING,
                ':lockTime' => $lockTime,
                ':now' => $now,
                ':id' => $id,
                ':queued' => SocialPostDestination::STATUS_QUEUED,
            ]
        )->execute();

        return $affected > 0;
    }

    public function releaseClaimedDestination(int $id, string $lockTime): bool
    {
        $now = time();
        $affected = $this->db->createCommand(
            'UPDATE {{%social_post_destination}} ' .
            'SET status = :queued, locked_at = NULL, updated_at = :now ' .
            'WHERE id = :id AND status = :proc AND locked_at = :lockTime',
            [
                ':queued' => SocialPostDestination::STATUS_QUEUED,
                ':now' => $now,
                ':id' => $id,
                ':proc' => SocialPostDestination::STATUS_PROCESSING,
                ':lockTime' => $lockTime,
            ]
        )->execute();

        return $affected > 0;
    }

    public function recoverStaleDestinations(int $timeoutMinutes = 15): int
    {
        $staleThreshold = date('Y-m-d H:i:s', time() - ($timeoutMinutes * 60));
        $now = time();
        $nowDate = date('Y-m-d H:i:s');

        // Path 1: If remote_post_id exists, recover as published
        $recoveredPublished = $this->db->createCommand(
            'UPDATE {{%social_post_destination}} ' .
            'SET status = :pub, published_at = COALESCE(published_at, :nowDate), locked_at = NULL, updated_at = :now ' .
            'WHERE status = :proc AND locked_at <= :staleThreshold AND remote_post_id IS NOT NULL',
            [
                ':pub' => SocialPostDestination::STATUS_PUBLISHED,
                ':nowDate' => $nowDate,
                ':now' => $now,
                ':proc' => SocialPostDestination::STATUS_PROCESSING,
                ':staleThreshold' => $staleThreshold,
            ]
        )->execute();

        // Path 2: If remote_post_id is null, mark failed with ambiguous delivery prefix and stamp updated_at
        $recoveredAmbiguous = $this->db->createCommand(
            'UPDATE {{%social_post_destination}} ' .
            'SET status = :failed, error_message = :err, locked_at = NULL, updated_at = :now ' .
            'WHERE status = :proc AND locked_at <= :staleThreshold AND remote_post_id IS NULL',
            [
                ':failed' => SocialPostDestination::STATUS_FAILED,
                ':err' => 'Ambiguous delivery: Worker lease expired during processing',
                ':now' => $now,
                ':proc' => SocialPostDestination::STATUS_PROCESSING,
                ':staleThreshold' => $staleThreshold,
            ]
        )->execute();

        return $recoveredPublished + $recoveredAmbiguous;
    }

    public function markDestinationPublished(int $id, string $remotePostId, string $lockTime): bool
    {
        $now = time();
        $publishedAt = date('Y-m-d H:i:s');

        $affected = $this->db->createCommand(
            'UPDATE {{%social_post_destination}} ' .
            'SET status = :pub, remote_post_id = :remoteId, published_at = :pubAt, error_message = NULL, locked_at = NULL, updated_at = :now ' .
            'WHERE id = :id AND status = :proc AND locked_at = :lockTime',
            [
                ':pub' => SocialPostDestination::STATUS_PUBLISHED,
                ':remoteId' => $remotePostId,
                ':pubAt' => $publishedAt,
                ':now' => $now,
                ':id' => $id,
                ':proc' => SocialPostDestination::STATUS_PROCESSING,
                ':lockTime' => $lockTime,
            ]
        )->execute();

        return $affected > 0;
    }

    public function markDestinationFailed(int $id, string $errorMessage, string $lockTime, bool $isAmbiguous = false): bool
    {
        $now = time();
        $finalError = $errorMessage;
        if ($isAmbiguous && !str_starts_with($finalError, 'Ambiguous delivery:')) {
            $finalError = 'Ambiguous delivery: ' . $finalError;
        }

        $affected = $this->db->createCommand(
            'UPDATE {{%social_post_destination}} ' .
            'SET status = :failed, error_message = :err, locked_at = NULL, updated_at = :now ' .
            'WHERE id = :id AND status = :proc AND locked_at = :lockTime',
            [
                ':failed' => SocialPostDestination::STATUS_FAILED,
                ':err' => $finalError,
                ':now' => $now,
                ':id' => $id,
                ':proc' => SocialPostDestination::STATUS_PROCESSING,
                ':lockTime' => $lockTime,
            ]
        )->execute();

        return $affected > 0;
    }

    public function rescheduleDestination(int $id, int $retryCount, int $delaySeconds, string $lockTime): bool
    {
        $now = time();
        $nextScheduledAt = date('Y-m-d H:i:s', $now + $delaySeconds);

        $affected = $this->db->createCommand(
            'UPDATE {{%social_post_destination}} ' .
            'SET status = :queued, retry_count = :retries, scheduled_at = :schedAt, locked_at = NULL, updated_at = :now ' .
            'WHERE id = :id AND status = :proc AND locked_at = :lockTime',
            [
                ':queued' => SocialPostDestination::STATUS_QUEUED,
                ':retries' => $retryCount,
                ':schedAt' => $nextScheduledAt,
                ':now' => $now,
                ':id' => $id,
                ':proc' => SocialPostDestination::STATUS_PROCESSING,
                ':lockTime' => $lockTime,
            ]
        )->execute();

        return $affected > 0;
    }

    public function findDestinationById(int $destinationId, ?TenantContextInterface $context = null): ?SocialPostDestination
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        $query = (new Query($this->db))
            ->select([
                'spd.*',
                'destination_name' => 'COALESCE(sc.label, sub.name, spd.platform)',
            ])
            ->from(['spd' => '{{%social_post_destination}}'])
            ->leftJoin(['sc' => '{{%service_config}}'], 'sc.id = spd.service_config_id')
            ->leftJoin(['sub' => '{{%sub_service}}'], 'sub.id = spd.sub_service_id')
            ->where(['spd.id' => $destinationId]);

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $query->andWhere(['spd.account_id' => $ctx->getAccountId()]);
        }

        $row = $query->one();
        if ($row === null || $row === false) {
            return null;
        }

        return SocialPostDestination::fromDatabaseRow($row);
    }

    public function retryDestination(int $destinationId, ?TenantContextInterface $context = null, bool $isAmbiguousOverride = false): bool
    {
        $existing = $this->findDestinationById($destinationId, $context);
        if ($existing === null || !$existing->isFailed()) {
            return false;
        }

        $now = time();
        $nowDate = date('Y-m-d H:i:s');

        if ($isAmbiguousOverride) {
            // Ambiguous override requirements:
            // 1. Must have ambiguous delivery prefix
            // 2. Cooldown: updated_at <= time() - 900
            // 3. Reset retry_count to 0 for a new manually approved delivery cycle
            $cooldownThreshold = $now - 900;
            $sql = 'UPDATE {{%social_post_destination}} ' .
                   'SET status = :queued, error_message = NULL, retry_count = 0, scheduled_at = :nowDate, locked_at = NULL, updated_at = :now ' .
                   'WHERE id = :id AND status = :failed AND error_message LIKE :ambiguousPattern AND updated_at <= :cooldownThreshold';

            $params = [
                ':queued' => SocialPostDestination::STATUS_QUEUED,
                ':nowDate' => $nowDate,
                ':now' => $now,
                ':id' => $destinationId,
                ':failed' => SocialPostDestination::STATUS_FAILED,
                ':ambiguousPattern' => 'Ambiguous delivery:%',
                ':cooldownThreshold' => $cooldownThreshold,
            ];

            if ($context !== null && !$context->isUnrestricted()) {
                $sql .= ' AND account_id = :accId';
                $params[':accId'] = $context->getAccountId();
            }

            $affected = $this->db->createCommand($sql, $params)->execute();
            return $affected > 0;
        }

        // Standard non-ambiguous destination retry:
        // Must NOT be ambiguous delivery
        $sql = 'UPDATE {{%social_post_destination}} ' .
               'SET status = :queued, error_message = NULL, retry_count = 0, scheduled_at = :nowDate, locked_at = NULL, updated_at = :now ' .
               'WHERE id = :id AND status = :failed AND (error_message IS NULL OR error_message NOT LIKE :ambiguousPattern)';

        $params = [
            ':queued' => SocialPostDestination::STATUS_QUEUED,
            ':nowDate' => $nowDate,
            ':now' => $now,
            ':id' => $destinationId,
            ':failed' => SocialPostDestination::STATUS_FAILED,
            ':ambiguousPattern' => 'Ambiguous delivery:%',
        ];

        if ($context !== null && !$context->isUnrestricted()) {
            $sql .= ' AND account_id = :accId';
            $params[':accId'] = $context->getAccountId();
        }

        $affected = $this->db->createCommand($sql, $params)->execute();
        return $affected > 0;
    }

    public function createPost(array $data, array $destinations, ?TenantContextInterface $context = null): ?int
    {
        try {
            $now = time();
            $this->db->createCommand()->insert('{{%social_post}}', [
                'account_id' => $data['account_id'],
                'user_id' => $data['user_id'],
                'content' => $data['content'],
                'media_type' => $data['media_type'] ?? 'none',
                'media_url' => $data['media_url'] ?? null,
                'post_type' => $data['post_type'] ?? 'immediate',
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();

            $postId = (int) $this->db->getLastInsertID('{{%social_post}}');

            foreach ($destinations as $dest) {
                $this->db->createCommand()->insert('{{%social_post_destination}}', [
                    'social_post_id' => $postId,
                    'account_id' => $dest['account_id'] ?? $data['account_id'],
                    'platform' => $dest['platform'],
                    'service_config_id' => $dest['service_config_id'] ?? null,
                    'sub_service_id' => $dest['sub_service_id'] ?? null,
                    'status' => 'queued',
                    'scheduled_at' => $data['scheduled_at'] ?? date('Y-m-d H:i:s'),
                    'retry_count' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->execute();
            }

            return $postId;
        } catch (\Throwable) {
            return null;
        }
    }

    public function updatePost(int $id, array $data, ?TenantContextInterface $context = null): bool
    {
        $existing = $this->findById($id, $context);
        if ($existing === null) {
            return false;
        }

        try {
            $this->db->createCommand()->update('{{%social_post}}', [
                'content' => $data['content'] ?? $existing->getContent(),
                'scheduled_at' => $data['scheduled_at'] ?? $existing->getScheduledAt(),
                'updated_at' => time(),
            ], ['id' => $id])->execute();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function cancelPost(int $id, ?TenantContextInterface $context = null): bool
    {
        $existing = $this->findById($id, $context);
        if ($existing === null) {
            return false;
        }

        try {
            $this->db->createCommand()->update(
                '{{%social_post_destination}}',
                ['status' => 'failed', 'error_message' => 'Cancelled by user', 'updated_at' => time()],
                ['social_post_id' => $id, 'status' => 'queued']
            )->execute();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function deletePost(int $id, ?TenantContextInterface $context = null): bool
    {
        $existing = $this->findById($id, $context);
        if ($existing === null) {
            return false;
        }

        try {
            $this->db->createCommand()->delete('{{%social_post_destination}}', ['social_post_id' => $id])->execute();
            $this->db->createCommand()->delete('{{%social_post}}', ['id' => $id])->execute();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function retryPost(int $id, ?TenantContextInterface $context = null): bool
    {
        $existing = $this->findById($id, $context);
        if ($existing === null) {
            return false;
        }

        $now = time();
        $nowDate = date('Y-m-d H:i:s');

        try {
            // Strictly reset only standard failed destinations; ignore ambiguous deliveries
            $sql = 'UPDATE {{%social_post_destination}} ' .
                   'SET status = :queued, error_message = NULL, retry_count = 0, scheduled_at = :nowDate, locked_at = NULL, updated_at = :now ' .
                   'WHERE social_post_id = :postId AND status = :failed AND (error_message IS NULL OR error_message NOT LIKE :ambiguousPattern)';

            $params = [
                ':queued' => SocialPostDestination::STATUS_QUEUED,
                ':nowDate' => $nowDate,
                ':now' => $now,
                ':postId' => $id,
                ':failed' => SocialPostDestination::STATUS_FAILED,
                ':ambiguousPattern' => 'Ambiguous delivery:%',
            ];

            if ($context !== null && !$context->isUnrestricted()) {
                $sql .= ' AND account_id = :accId';
                $params[':accId'] = $context->getAccountId();
            }

            $this->db->createCommand($sql, $params)->execute();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<int, SocialPostDestination>
     */
    private function findDestinationsForPost(int $postId): array
    {
        $rows = (new Query($this->db))
            ->select([
                'spd.*',
                'destination_name' => 'COALESCE(sc.label, sub.name, spd.platform)',
            ])
            ->from(['spd' => '{{%social_post_destination}}'])
            ->leftJoin(['sc' => '{{%service_config}}'], 'sc.id = spd.service_config_id')
            ->leftJoin(['sub' => '{{%sub_service}}'], 'sub.id = spd.sub_service_id')
            ->where(['spd.social_post_id' => $postId])
            ->all();

        $destinations = [];
        foreach ($rows as $row) {
            $destinations[] = SocialPostDestination::fromDatabaseRow($row);
        }

        return $destinations;
    }
}
