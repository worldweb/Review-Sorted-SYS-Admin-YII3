<?php

declare(strict_types=1);

namespace App\Domain\Review\Sync;

use App\Domain\Notification\NotificationService;
use App\Domain\Review\Review;
use App\Domain\Review\ReviewRepositoryInterface;
use App\Domain\SocialConnection\ServiceConfig;
use App\Domain\SocialConnection\SocialConnectionRepositoryInterface;
use App\Security\TenantContext;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * ReviewSyncService.
 * Coordinates end-to-end synchronization of Google and Facebook reviews.
 * Implements deduplication, non-destructive deletion detection, and in-app notifications.
 */
final class ReviewSyncService implements ReviewSyncServiceInterface
{
    private LoggerInterface $logger;

    public function __construct(
        private ConnectionInterface $db,
        private ReviewRepositoryInterface $reviewRepo,
        private SocialConnectionRepositoryInterface $connectionRepo,
        private GoogleReviewSyncClientInterface $googleClient,
        private FacebookReviewSyncClientInterface $facebookClient,
        private ?NotificationService $notificationService = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function syncGoogleReviews(?int $accountId = null, ?int $serviceConfigId = null, int $maxPages = 50): ReviewSyncResult
    {
        $result = new ReviewSyncResult();
        $this->logger->debug('[ReviewSync] Starting Google review synchronization...');

        $query = (new Query($this->db))
            ->from('{{%service_config}}')
            ->where(['service_id' => ServiceConfig::SERVICE_GOOGLE, 'linked' => ServiceConfig::LINKED_YES]);

        if ($accountId !== null && $accountId > 0) {
            $query->andWhere(['account_id' => $accountId]);
        }

        if ($serviceConfigId !== null && $serviceConfigId > 0) {
            $query->andWhere(['id' => $serviceConfigId]);
        }

        $configs = $query->orderBy(['id' => SORT_ASC])->all();
        if (empty($configs)) {
            $this->logger->debug('[ReviewSync] No linked Google locations found for synchronization.');
            return $result;
        }

        foreach ($configs as $configRow) {
            $this->processSingleChannel($configRow, $maxPages, $result);
        }

        $summaryMsg = sprintf(
            '[ReviewSync] Google synchronization finished: %d channel(s) processed (%d successful, %d failed), %d review(s) fetched, %d inserted, %d updated, %d unchanged.',
            $result->getChannelsProcessed(),
            $result->getChannelsSuccessful(),
            $result->getChannelsFailed(),
            $result->getReviewsFetched(),
            $result->getReviewsInserted(),
            $result->getReviewsUpdated(),
            $result->getReviewsUnchanged()
        );

        if ($result->getReviewsInserted() > 0 || $result->getReviewsUpdated() > 0 || $result->hasErrors()) {
            $this->logger->info($summaryMsg);
        } else {
            $this->logger->debug($summaryMsg);
        }

        return $result;
    }

    public function syncFacebookReviews(?int $accountId = null, ?int $serviceConfigId = null, int $maxPages = 50): ReviewSyncResult
    {
        $result = new ReviewSyncResult();
        $this->logger->debug('[ReviewSync] Starting Facebook review synchronization...');

        $query = (new Query($this->db))
            ->from('{{%service_config}}')
            ->where(['service_id' => ServiceConfig::SERVICE_FACEBOOK, 'linked' => ServiceConfig::LINKED_YES]);

        if ($accountId !== null && $accountId > 0) {
            $query->andWhere(['account_id' => $accountId]);
        }

        if ($serviceConfigId !== null && $serviceConfigId > 0) {
            $query->andWhere(['id' => $serviceConfigId]);
        }

        $configs = $query->orderBy(['id' => SORT_ASC])->all();
        if (empty($configs)) {
            $this->logger->debug('[ReviewSync] No linked Facebook Pages found for synchronization.');
            return $result;
        }

        foreach ($configs as $configRow) {
            $this->processSingleChannel($configRow, $maxPages, $result);
        }

        $summaryMsg = sprintf(
            '[ReviewSync] Facebook synchronization finished: %d channel(s) processed (%d successful, %d failed), %d review(s) fetched, %d inserted, %d updated, %d unchanged.',
            $result->getChannelsProcessed(),
            $result->getChannelsSuccessful(),
            $result->getChannelsFailed(),
            $result->getReviewsFetched(),
            $result->getReviewsInserted(),
            $result->getReviewsUpdated(),
            $result->getReviewsUnchanged()
        );

        if ($result->getReviewsInserted() > 0 || $result->getReviewsUpdated() > 0 || $result->hasErrors()) {
            $this->logger->info($summaryMsg);
        } else {
            $this->logger->debug($summaryMsg);
        }

        return $result;
    }

    public function syncAll(int $maxPages = 50): ReviewSyncResult
    {
        $googleRes = $this->syncGoogleReviews(maxPages: $maxPages);
        $facebookRes = $this->syncFacebookReviews(maxPages: $maxPages);

        return $googleRes->merge($facebookRes);
    }

    public function syncChunk(
        ?int $accountId = null,
        ?int $serviceConfigId = null,
        int $lastChannelId = 0,
        int $limit = 10,
        int $maxPages = 2
    ): array {
        $baseQuery = (new Query($this->db))
            ->from('{{%service_config}}')
            ->where([
                'service_id' => [ServiceConfig::SERVICE_GOOGLE, ServiceConfig::SERVICE_FACEBOOK],
                'linked' => ServiceConfig::LINKED_YES,
            ]);

        if ($accountId !== null && $accountId > 0) {
            $baseQuery->andWhere(['account_id' => $accountId]);
        }

        if ($serviceConfigId !== null && $serviceConfigId > 0) {
            $baseQuery->andWhere(['id' => $serviceConfigId]);
        }

        $totalChannels = (int) (clone $baseQuery)->count('*', $this->db);

        $configs = (clone $baseQuery)
            ->andWhere(['>', 'id', $lastChannelId])
            ->orderBy(['id' => SORT_ASC])
            ->limit($limit)
            ->all();

        $chunkResult = new ReviewSyncResult();
        $newLastChannelId = $lastChannelId;

        foreach ($configs as $configRow) {
            $scId = (int) $configRow['id'];
            $this->processSingleChannel($configRow, $maxPages, $chunkResult);
            $newLastChannelId = $scId;
        }

        $hasMore = false;
        if (count($configs) === $limit) {
            $hasMore = (clone $baseQuery)
                ->andWhere(['>', 'id', $newLastChannelId])
                ->exists($this->db);
        }

        return [
            'total_channels' => $totalChannels,
            'channels_processed' => count($configs),
            'last_channel_id' => $newLastChannelId,
            'has_more' => $hasMore,
            'reviews_fetched' => $chunkResult->getReviewsFetched(),
            'reviews_inserted' => $chunkResult->getReviewsInserted(),
            'reviews_updated' => $chunkResult->getReviewsUpdated(),
            'errors' => $chunkResult->getErrors(),
        ];
    }

    /**
     * Synchronizes a single channel config row (Google or Facebook) safely with advisory lock isolation.
     */
    private function processSingleChannel(array $configRow, int $maxPages, ReviewSyncResult $result): void
    {
        $scId = (int) $configRow['id'];
        $serviceId = (int) $configRow['service_id'];
        $isGoogle = ($serviceId === ServiceConfig::SERVICE_GOOGLE);
        $platformName = $isGoogle ? 'Google location' : 'Facebook Page';
        $label = (string) ($configRow['label'] ?? "{$platformName} #{$scId}");
        $accountId = isset($configRow['account_id']) ? (int) $configRow['account_id'] : null;

        $lockName = "sys_review_sync_sc_{$scId}";
        $acquired = (int) $this->db->createCommand('SELECT GET_LOCK(:name, 5)', [':name' => $lockName])->queryScalar();
        if ($acquired !== 1) {
            $this->logger->warning("[ReviewSync] {$platformName} #{$scId} ({$label}) is currently locked by another synchronization process. Skipping.");
            return;
        }

        $this->logger->debug("[ReviewSync] Syncing {$platformName}: {$label} (ID: {$scId}, Account: {$accountId})");

        try {
            if ($isGoogle) {
                $rawReviews = $this->googleClient->fetchReviews($configRow, $maxPages);
            } else {
                $rawReviews = $this->facebookClient->fetchReviews($configRow, $maxPages);
            }

            $inserted = 0;
            $updated = 0;
            $unchanged = 0;
            $returnedObjIds = [];

            // Process in chronological order matching legacy Yii 2
            $orderedReviews = array_reverse($rawReviews);

            foreach ($orderedReviews as $rdata) {
                $objId = (string) ($rdata['obj_id'] ?? '');
                if ($objId !== '') {
                    $returnedObjIds[] = $objId;
                }

                $rdata['account_id'] = $accountId;
                $rdata['service_config_id'] = $scId;
                $rdata['is_google'] = $isGoogle;

                $upsertRes = $this->reviewRepo->upsertReview($rdata);
                $reviewId = $upsertRes['review_id'];
                $action = $upsertRes['action'];

                if ($action === 'inserted') {
                    $inserted++;
                    if ($accountId !== null && $reviewId > 0) {
                        $this->dispatchNewReviewNotification($accountId, $reviewId, $rdata);
                    }
                } elseif ($action === 'updated') {
                    $updated++;
                } else {
                    $unchanged++;
                }

                // Import post comments / replies if present
                if (!empty($rdata['comments']) && is_array($rdata['comments']) && $reviewId > 0) {
                    foreach ($rdata['comments'] as $cdata) {
                        $cObjId = (string) ($cdata['obj_id'] ?? '');
                        if ($cObjId !== '') {
                            $this->reviewRepo->insertOrUpdateReviewReplyComment(
                                reviewId: $reviewId,
                                accountId: (int) ($accountId ?? 0),
                                serviceConfigId: $scId,
                                objId: $cObjId,
                                customerId: isset($cdata['customer_id']) ? (string) $cdata['customer_id'] : null,
                                customerName: isset($cdata['customer_name']) ? (string) $cdata['customer_name'] : null,
                                replyText: (string) ($cdata['reply_text'] ?? ''),
                                replyDate: (string) ($cdata['reply_date'] ?? date('Y-m-d H:i:s')),
                                replyExtra: isset($cdata['reply_extra']) ? (string) $cdata['reply_extra'] : null
                            );
                        }
                    }
                }
            }

            // Safe non-destructive deletion detection ONLY during full unconstrained sync passes (Requirement 7)
            if ($isGoogle && $maxPages >= 50) {
                $this->detectMissingGoogleReviews($scId, $returnedObjIds, $label);
            }

            $result->addChannelResult(
                fetched: count($rawReviews),
                inserted: $inserted,
                updated: $updated,
                unchanged: $unchanged
            );
        } catch (Throwable $e) {
            $errMsg = "Failed syncing {$platformName} {$label} (ID: {$scId}): " . $e->getMessage();
            $this->logger->error("[ReviewSync] {$errMsg}");
            $result->addChannelResult(0, 0, 0, 0, $errMsg);
        } finally {
            try {
                $this->db->createCommand('SELECT RELEASE_LOCK(:name)', [':name' => $lockName])->execute();
            } catch (Throwable) {
                // Ignore release errors
            }
        }
    }

    public function syncFacebookPageReviewsByPlatformId(string $pageId): ReviewSyncResult
    {
        $config = (new Query($this->db))
            ->select(['id'])
            ->from('{{%service_config}}')
            ->where(['service_id' => ServiceConfig::SERVICE_FACEBOOK, 'page_id' => $pageId])
            ->one();

        if ($config === false || $config === null) {
            $this->logger->warning("[ReviewSync] Cannot sync Facebook page: no ServiceConfig found for Page ID {$pageId}.");
            return new ReviewSyncResult();
        }

        return $this->syncFacebookReviews(serviceConfigId: (int) $config['id']);
    }

    /**
     * Non-destructive missing review detection.
     * Identifies stored reviews that are no longer returned by the Google API,
     * logging the occurrence without deleting database history (matches Yii 2 safe policy).
     *
     * @param string[] $returnedObjIds
     */
    private function detectMissingGoogleReviews(int $serviceConfigId, array $returnedObjIds, string $label): void
    {
        if (empty($returnedObjIds)) {
            return;
        }

        $existingObjIds = $this->reviewRepo->findExistingObjectIdsForConfig($serviceConfigId);
        $missing = array_diff($existingObjIds, $returnedObjIds);

        if (!empty($missing)) {
            $this->logger->debug(sprintf(
                '[ReviewSync] Non-destructive check for %s: %d review(s) in local database are no longer returned by Google API: [%s]. Preserving records in database.',
                $label,
                count($missing),
                implode(', ', array_slice($missing, 0, 10))
            ));
        }
    }

    /**
     * Creates in-app notification in user_notification table for active users in the account.
     *
     * @param array<string, mixed> $reviewData
     */
    private function dispatchNewReviewNotification(int $accountId, int $reviewId, array $reviewData): void
    {
        try {
            $rate = isset($reviewData['review_rate']) ? (float) $reviewData['review_rate'] : null;
            $rateStr = $rate !== null && $rate > 0 ? " ({$rate}★)" : '';
            $customerName = (string) ($reviewData['customer_name'] ?? 'A Customer');
            $service = (string) ($reviewData['review_service'] ?? 'External');
            $title = "New {$service} Review" . $rateStr;

            $textSnippet = (string) ($reviewData['review_text'] ?? '');
            if (mb_strlen($textSnippet) > 80) {
                $textSnippet = mb_substr($textSnippet, 0, 77) . '...';
            }
            $message = "New review received from {$customerName}: \"{$textSnippet}\"";
            $actionUrl = \App\Environment::url('/reviews/view?id=' . $reviewId);

            if ($this->notificationService !== null) {
                $this->notificationService->notifyAccountUsers($accountId, 'review', $title, $message, $actionUrl);
                return;
            }

            // Fallback direct dispatch if service not injected
            $activeUsers = (new Query($this->db))
                ->select(['id'])
                ->from('{{%user}}')
                ->where(['account_id' => $accountId, 'status' => 1])
                ->column();

            if (empty($activeUsers)) {
                return;
            }

            $now = time();
            foreach ($activeUsers as $userId) {
                $this->db->createCommand(
                    'INSERT INTO {{%user_notification}} (`user_id`, `account_id`, `type`, `title`, `message`, `action_url`, `is_read`, `created_at`) ' .
                    'VALUES (:userId, :accountId, :type, :title, :message, :actionUrl, 0, :createdAt)'
                )->bindValues([
                    ':userId' => (int) $userId,
                    ':accountId' => $accountId,
                    ':type' => 'review',
                    ':title' => $title,
                    ':message' => $message,
                    ':actionUrl' => $actionUrl,
                    ':createdAt' => $now,
                ])->execute();
            }
        } catch (Throwable $e) {
            $this->logger->warning('[ReviewSync] Could not create in-app notification: ' . $e->getMessage());
        }
    }
}

