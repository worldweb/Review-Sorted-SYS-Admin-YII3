<?php

declare(strict_types=1);

namespace App\Domain\Review\Sync;

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
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function syncGoogleReviews(?int $accountId = null, ?int $serviceConfigId = null): ReviewSyncResult
    {
        $result = new ReviewSyncResult();
        $this->logger->info('[ReviewSync] Starting Google review synchronization...');

        $query = (new Query($this->db))
            ->from('{{%service_config}}')
            ->where(['service_id' => ServiceConfig::SERVICE_GOOGLE, 'linked' => 1]);

        if ($accountId !== null && $accountId > 0) {
            $query->andWhere(['account_id' => $accountId]);
        }

        if ($serviceConfigId !== null && $serviceConfigId > 0) {
            $query->andWhere(['id' => $serviceConfigId]);
        }

        $configs = $query->all();
        if (empty($configs)) {
            $this->logger->info('[ReviewSync] No linked Google locations found for synchronization.');
            return $result;
        }

        foreach ($configs as $configRow) {
            $scId = (int) $configRow['id'];
            $label = (string) ($configRow['label'] ?? "Google Location #{$scId}");
            $accountId = isset($configRow['account_id']) ? (int) $configRow['account_id'] : null;

            $lockName = "sys_review_sync_sc_{$scId}";
            $acquired = (int) $this->db->createCommand('SELECT GET_LOCK(:name, 5)', [':name' => $lockName])->queryScalar();
            if ($acquired !== 1) {
                $this->logger->warning("[ReviewSync] Google location #{$scId} ({$label}) is currently locked by another synchronization process. Skipping.");
                continue;
            }

            $this->logger->info("[ReviewSync] Syncing Google location: {$label} (ID: {$scId}, Account: {$accountId})");

            try {
                $rawReviews = $this->googleClient->fetchReviews($configRow);
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
                    $rdata['is_google'] = true;

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

                    // Import merchant / customer replies if present
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

                // Safe non-destructive deletion detection matching Yii 2
                $this->detectMissingGoogleReviews($scId, $returnedObjIds, $label);

                $result->addChannelResult(
                    fetched: count($rawReviews),
                    inserted: $inserted,
                    updated: $updated,
                    unchanged: $unchanged
                );
            } catch (Throwable $e) {
                $errMsg = "Failed syncing Google location {$label} (ID: {$scId}): " . $e->getMessage();
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

        $this->logger->info(sprintf(
            '[ReviewSync] Google synchronization finished: %d channel(s), %d review(s) fetched, %d inserted, %d updated, %d unchanged.',
            $result->getChannelsProcessed(),
            $result->getReviewsFetched(),
            $result->getReviewsInserted(),
            $result->getReviewsUpdated(),
            $result->getReviewsUnchanged()
        ));

        return $result;
    }

    public function syncFacebookReviews(?int $accountId = null, ?int $serviceConfigId = null): ReviewSyncResult
    {
        $result = new ReviewSyncResult();
        $this->logger->info('[ReviewSync] Starting Facebook review synchronization...');

        $query = (new Query($this->db))
            ->from('{{%service_config}}')
            ->where(['service_id' => ServiceConfig::SERVICE_FACEBOOK, 'linked' => 1]);

        if ($accountId !== null && $accountId > 0) {
            $query->andWhere(['account_id' => $accountId]);
        }

        if ($serviceConfigId !== null && $serviceConfigId > 0) {
            $query->andWhere(['id' => $serviceConfigId]);
        }

        $configs = $query->all();
        if (empty($configs)) {
            $this->logger->info('[ReviewSync] No linked Facebook Pages found for synchronization.');
            return $result;
        }

        foreach ($configs as $configRow) {
            $scId = (int) $configRow['id'];
            $label = (string) ($configRow['label'] ?? "Facebook Page #{$scId}");
            $pageAccountId = isset($configRow['account_id']) ? (int) $configRow['account_id'] : null;

            $lockName = "sys_review_sync_sc_{$scId}";
            $acquired = (int) $this->db->createCommand('SELECT GET_LOCK(:name, 5)', [':name' => $lockName])->queryScalar();
            if ($acquired !== 1) {
                $this->logger->warning("[ReviewSync] Facebook Page #{$scId} ({$label}) is currently locked by another synchronization process. Skipping.");
                continue;
            }

            $this->logger->info("[ReviewSync] Syncing Facebook Page: {$label} (ID: {$scId}, Account: {$pageAccountId})");

            try {
                $rawReviews = $this->facebookClient->fetchReviews($configRow);
                $inserted = 0;
                $updated = 0;
                $unchanged = 0;

                // Process in chronological order matching legacy Yii 2
                $orderedReviews = array_reverse($rawReviews);

                foreach ($orderedReviews as $rdata) {
                    $rdata['account_id'] = $pageAccountId;
                    $rdata['service_config_id'] = $scId;
                    $rdata['is_google'] = false;

                    $upsertRes = $this->reviewRepo->upsertReview($rdata);
                    $reviewId = $upsertRes['review_id'];
                    $action = $upsertRes['action'];

                    if ($action === 'inserted') {
                        $inserted++;
                        if ($pageAccountId !== null && $reviewId > 0) {
                            $this->dispatchNewReviewNotification($pageAccountId, $reviewId, $rdata);
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
                                    accountId: (int) ($pageAccountId ?? 0),
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

                $result->addChannelResult(
                    fetched: count($rawReviews),
                    inserted: $inserted,
                    updated: $updated,
                    unchanged: $unchanged
                );
            } catch (Throwable $e) {
                $errMsg = "Failed syncing Facebook Page {$label} (ID: {$scId}): " . $e->getMessage();
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

        $this->logger->info(sprintf(
            '[ReviewSync] Facebook synchronization finished: %d channel(s), %d review(s) fetched, %d inserted, %d updated, %d unchanged.',
            $result->getChannelsProcessed(),
            $result->getReviewsFetched(),
            $result->getReviewsInserted(),
            $result->getReviewsUpdated(),
            $result->getReviewsUnchanged()
        ));

        return $result;
    }

    public function syncAll(): ReviewSyncResult
    {
        $googleRes = $this->syncGoogleReviews();
        $facebookRes = $this->syncFacebookReviews();

        return $googleRes->merge($facebookRes);
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
            $this->logger->info(sprintf(
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
            $activeUsers = (new Query($this->db))
                ->select(['id'])
                ->from('{{%user}}')
                ->where(['account_id' => $accountId, 'status' => 1])
                ->column();

            if (empty($activeUsers)) {
                return;
            }

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

