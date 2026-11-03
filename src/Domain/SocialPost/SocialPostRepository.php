<?php

declare(strict_types=1);

namespace App\Domain\SocialPost;

use App\Security\TenantContextInterface;
use App\Security\TenantContextProviderInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
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

    public function findAllPosts(?TenantContextInterface $context = null, int $limit = 20, int $offset = 0, ?string $typeFilter = null): array
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        $query = (new Query($this->db))
            ->select([
                'sp.*',
                'author_email' => 'u.email',
                'account_business_name' => 'a.business_name',
            ])
            ->from(['sp' => '{{%social_post}}'])
            ->leftJoin(['u' => '{{%user}}'], 'u.id = sp.user_id')
            ->leftJoin(['a' => '{{%account}}'], 'a.id = sp.account_id')
            ->orderBy(['sp.id' => SORT_DESC])
            ->limit($limit)
            ->offset($offset);

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

        $rows = $query->all();
        $posts = [];
        foreach ($rows as $row) {
            $p = SocialPost::fromDatabaseRow($row);
            $destinations = $this->findDestinationsForPost($p->getId());
            $posts[] = $p->withDestinations($destinations);
        }

        return $posts;
    }

    public function countPosts(?TenantContextInterface $context = null, ?string $typeFilter = null): int
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        $query = (new Query($this->db))
            ->from('{{%social_post}}');

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $accessible = $ctx->getAccessibleAccountIds();
            if (empty($accessible)) {
                return 0;
            }
            $query->andWhere(['in', 'account_id', $accessible]);
        }

        if (!empty($typeFilter)) {
            $query->andWhere(['post_type' => $typeFilter]);
        }

        return (int) $query->count();
    }

    public function findPendingDestinations(int $limit = 20): array
    {
        $rows = (new Query($this->db))
            ->from('{{%social_post_destination}}')
            ->where(['status' => SocialPostDestination::STATUS_QUEUED])
            ->andWhere(['<=', 'scheduled_at', date('Y-m-d H:i:s')])
            ->orderBy(['id' => SORT_ASC])
            ->limit($limit)
            ->all();

        $destinations = [];
        foreach ($rows as $row) {
            $destinations[] = SocialPostDestination::fromDatabaseRow($row);
        }

        return $destinations;
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
                    'account_id' => $data['account_id'],
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

        try {
            $this->db->createCommand()->update(
                '{{%social_post_destination}}',
                ['status' => 'queued', 'error_message' => null, 'updated_at' => time()],
                ['social_post_id' => $id, 'status' => 'failed']
            )->execute();

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
