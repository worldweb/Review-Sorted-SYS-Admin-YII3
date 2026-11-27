<?php

declare(strict_types=1);

namespace App\Domain\Review\Sync;

use App\Domain\Account\Account;
use App\Domain\Review\Review;
use App\Domain\Review\ReviewRepositoryInterface;
use App\Domain\SocialConnection\ServiceConfig;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * Coordinates scheduled polling of Facebook Page mentions, Instagram mentions,
 * and Facebook Page post replies, mirroring legacy Yii2 MentionsController and RepliesController.
 */
final class SocialMentionSyncService
{
    private LoggerInterface $logger;

    public function __construct(
        private ConnectionInterface $db,
        private ReviewRepositoryInterface $reviewRepo,
        private FacebookReviewSyncClientInterface $facebookClient,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function syncFacebookMentions(?int $accountId = null, ?int $serviceConfigId = null, int $maxPages = 5): ReviewSyncResult
    {
        $result = new ReviewSyncResult();
        $configs = $this->findLinkedFacebookConfigs($accountId, $serviceConfigId);
        if (empty($configs)) {
            $this->logger->debug('[SocialMentionSync] No linked Facebook Pages found for mentions sync.');
            return $result;
        }

        foreach ($configs as $configRow) {
            $scId = (int) $configRow['id'];
            $label = (string) ($configRow['label'] ?? "Facebook Page #{$scId}");
            $accId = isset($configRow['account_id']) ? (int) $configRow['account_id'] : null;

            $this->runWithLock("sys_fb_mention_sync_sc_{$scId}", "Facebook Page #{$scId} ({$label})", function () use ($configRow, $scId, $accId, $label, $maxPages, $result): void {
                try {
                    $lastObjId = $this->findLastObjectId($scId, null, Review::TYPE_MENTION);
                    $items = $this->facebookClient->fetchPageMentions($configRow, $lastObjId, $maxPages);
                    $this->upsertItems($items, $scId, null, $accId, $result);
                } catch (Throwable $e) {
                    $errMsg = "Failed syncing Facebook mentions for {$label} (ID: {$scId}): " . $e->getMessage();
                    $this->logger->error("[SocialMentionSync] {$errMsg}");
                    $result->addChannelResult(0, 0, 0, 0, $errMsg);
                }
            });
        }

        return $result;
    }

    public function syncInstagramMentions(?int $accountId = null, ?int $subServiceId = null, int $maxPages = 5): ReviewSyncResult
    {
        $result = new ReviewSyncResult();
        $query = (new Query($this->db))
            ->select([
                'sub_id' => 'sub.id',
                'sub_obj_id' => 'sub.obj_id',
                'sub_name' => 'sub.name',
                'sc_id' => 'sc.id',
                'account_id' => 'sc.account_id',
                'page_token' => 'sc.page_token',
            ])
            ->from(['sub' => '{{%sub_service}}'])
            ->innerJoin(['sc' => '{{%service_config}}'], 'sc.id = sub.service_config_id')
            ->innerJoin(['acc' => '{{%account}}'], 'acc.id = sc.account_id')
            ->where([
                'sub.type' => 'instagram',
                'sc.service_id' => ServiceConfig::SERVICE_FACEBOOK,
                'sc.linked' => ServiceConfig::LINKED_YES,
                'acc.status' => Account::STATUS_ACTIVE,
            ]);

        if ($accountId !== null && $accountId > 0) {
            $query->andWhere(['sc.account_id' => $accountId]);
        }
        if ($subServiceId !== null && $subServiceId > 0) {
            $query->andWhere(['sub.id' => $subServiceId]);
        }

        $rows = $query->orderBy(['sub.id' => SORT_ASC])->all();
        if (empty($rows)) {
            $this->logger->debug('[SocialMentionSync] No linked Instagram Business accounts found for mentions sync.');
            return $result;
        }

        foreach ($rows as $row) {
            $subId = (int) $row['sub_id'];
            $scId = (int) $row['sc_id'];
            $accId = isset($row['account_id']) ? (int) $row['account_id'] : null;
            $instaObjId = (string) ($row['sub_obj_id'] ?? '');
            $pageToken = (string) ($row['page_token'] ?? '');
            $label = (string) ($row['sub_name'] ?? "Instagram #{$subId}");

            $this->runWithLock("sys_ig_mention_sync_sub_{$subId}", "Instagram #{$subId} ({$label})", function () use ($subId, $scId, $accId, $instaObjId, $pageToken, $label, $maxPages, $result): void {
                try {
                    $lastObjId = $this->findLastObjectId($scId, $subId, Review::TYPE_MENTION);
                    $items = $this->facebookClient->fetchInstagramMentions($instaObjId, $pageToken, $label, $lastObjId, $maxPages);
                    $this->upsertItems($items, $scId, $subId, $accId, $result);
                } catch (Throwable $e) {
                    $errMsg = "Failed syncing Instagram mentions for {$label} (SubID: {$subId}): " . $e->getMessage();
                    $this->logger->error("[SocialMentionSync] {$errMsg}");
                    $result->addChannelResult(0, 0, 0, 0, $errMsg);
                }
            });
        }

        return $result;
    }

    public function syncFacebookPostReplies(?int $accountId = null, ?int $serviceConfigId = null, int $maxPages = 2): ReviewSyncResult
    {
        $result = new ReviewSyncResult();
        $configs = $this->findLinkedFacebookConfigs($accountId, $serviceConfigId);
        if (empty($configs)) {
            $this->logger->debug('[SocialMentionSync] No linked Facebook Pages found for post replies sync.');
            return $result;
        }

        foreach ($configs as $configRow) {
            $scId = (int) $configRow['id'];
            $label = (string) ($configRow['label'] ?? "Facebook Page #{$scId}");
            $accId = isset($configRow['account_id']) ? (int) $configRow['account_id'] : null;

            $this->runWithLock("sys_fb_reply_sync_sc_{$scId}", "Facebook Page #{$scId} ({$label})", function () use ($configRow, $scId, $accId, $label, $maxPages, $result): void {
                try {
                    $items = $this->facebookClient->fetchPostReplies($configRow, $maxPages);
                    $this->upsertItems($items, $scId, null, $accId, $result);
                } catch (Throwable $e) {
                    $errMsg = "Failed syncing Facebook post replies for {$label} (ID: {$scId}): " . $e->getMessage();
                    $this->logger->error("[SocialMentionSync] {$errMsg}");
                    $result->addChannelResult(0, 0, 0, 0, $errMsg);
                }
            });
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function findLinkedFacebookConfigs(?int $accountId, ?int $serviceConfigId): array
    {
        $query = (new Query($this->db))
            ->select(['sc.*'])
            ->from(['sc' => '{{%service_config}}'])
            ->innerJoin(['acc' => '{{%account}}'], 'acc.id = sc.account_id')
            ->where([
                'sc.service_id' => ServiceConfig::SERVICE_FACEBOOK,
                'sc.linked' => ServiceConfig::LINKED_YES,
                'acc.status' => Account::STATUS_ACTIVE,
            ]);

        if ($accountId !== null && $accountId > 0) {
            $query->andWhere(['sc.account_id' => $accountId]);
        }
        if ($serviceConfigId !== null && $serviceConfigId > 0) {
            $query->andWhere(['sc.id' => $serviceConfigId]);
        }

        return $query->orderBy(['sc.id' => SORT_ASC])->all();
    }

    private function findLastObjectId(int $serviceConfigId, ?int $subServiceId, string $type): ?string
    {
        $query = (new Query($this->db))
            ->select(['obj_id'])
            ->from('{{%review}}')
            ->where(['service_config_id' => $serviceConfigId, 'type' => $type]);

        if ($subServiceId !== null) {
            $query->andWhere(['sub_service_id' => $subServiceId]);
        }

        $val = $query->orderBy(['id' => SORT_DESC])->scalar();
        return is_string($val) && $val !== '' ? $val : null;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function upsertItems(array $items, int $scId, ?int $subServiceId, ?int $accountId, ReviewSyncResult $result): void
    {
        $inserted = 0;
        $updated = 0;
        $unchanged = 0;

        foreach (array_reverse($items) as $item) {
            $item['account_id'] = $accountId;
            $item['service_config_id'] = $scId;
            if ($subServiceId !== null) {
                $item['sub_service_id'] = $subServiceId;
            }
            $item['is_google'] = false;

            $res = $this->reviewRepo->upsertReview($item);
            if ($res['action'] === 'inserted') {
                $inserted++;
            } elseif ($res['action'] === 'updated') {
                $updated++;
            } else {
                $unchanged++;
            }
        }

        $result->addChannelResult(count($items), $inserted, $updated, $unchanged);
    }

    private function runWithLock(string $lockName, string $label, callable $callback): void
    {
        $acquired = (int) $this->db->createCommand('SELECT GET_LOCK(:name, 5)', [':name' => $lockName])->queryScalar();
        if ($acquired !== 1) {
            $this->logger->warning("[SocialMentionSync] {$label} is currently locked by another sync process. Skipping.");
            return;
        }

        try {
            $callback();
        } finally {
            try {
                $this->db->createCommand('SELECT RELEASE_LOCK(:name)', [':name' => $lockName])->execute();
            } catch (Throwable) {
            }
        }
    }
}
