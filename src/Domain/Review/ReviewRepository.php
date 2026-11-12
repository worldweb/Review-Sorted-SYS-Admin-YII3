<?php

declare(strict_types=1);

namespace App\Domain\Review;

use App\Security\TenantContextInterface;
use App\Security\TenantContextProviderInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;

/**
 * ReviewRepository.
 * Safe, parameterized implementation of ReviewRepositoryInterface.
 * Strictly enforces TenantContext isolation across all queries.
 */
final class ReviewRepository implements ReviewRepositoryInterface
{
    public function __construct(
        private ConnectionInterface $db,
        private ?TenantContextProviderInterface $tenantContextProvider = null,
    ) {
    }

    public function findById(int $id, ?TenantContextInterface $context = null): ?Review
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        $query = (new Query($this->db))
            ->select([
                'r.*',
                'service_config_label' => 'sc.label',
                'service_label' => 's.label',
                'sub_service_name' => 'sub.name',
                'sub_service_type' => 'sub.type',
                'is_service_ready' => new Expression("(sc.page_token IS NOT NULL AND sc.page_token != '' OR s.class = 'plugin')"),
                'reply_count' => new Expression('(SELECT COUNT(*) FROM {{%review_reply}} rr WHERE rr.review_id = r.id)'),
            ])
            ->from(['r' => '{{%review}}'])
            ->leftJoin(['sc' => '{{%service_config}}'], 'sc.id = r.service_config_id')
            ->leftJoin(['s' => '{{%service}}'], 's.id = sc.service_id')
            ->leftJoin(['sub' => '{{%sub_service}}'], 'sub.id = r.sub_service_id')
            ->where(['r.id' => $id]);

        // Tenant Boundary
        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $accountId = $ctx->getAccountId();
            if ($accountId === null) {
                return null;
            }
            $query->andWhere(['r.account_id' => $accountId]);
        }

        $row = $query->one();
        if ($row === null || $row === false) {
            return null;
        }

        // Final verification check on account permission
        if ($ctx !== null && isset($row['account_id']) && !$ctx->canAccessAccount((int) $row['account_id'])) {
            return null;
        }

        return Review::fromDatabaseRow($row);
    }

    public function search(ReviewSearchFilter $filter, ?TenantContextInterface $context = null): array
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        $query = (new Query($this->db))
            ->from(['r' => '{{%review}}'])
            ->leftJoin(['sc' => '{{%service_config}}'], 'sc.id = r.service_config_id')
            ->leftJoin(['s' => '{{%service}}'], 's.id = sc.service_id')
            ->leftJoin(['sub' => '{{%sub_service}}'], 'sub.id = r.sub_service_id')
            ->leftJoin(['rr' => '{{%review_reply}}'], 'rr.review_id = r.id');

        // Tenant isolation
        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $accountId = $ctx->getAccountId();
            if ($accountId === null) {
                return ['reviews' => [], 'total' => 0];
            }
            $query->andWhere(['r.account_id' => $accountId]);
        }

        // Service Config Filter
        if ($filter->getServiceConfigId() !== null) {
            $query->andWhere(['r.service_config_id' => $filter->getServiceConfigId()]);
        }

        // Service Channel Filter
        if ($filter->getServiceId() !== null) {
            if ($filter->getServiceId() > 0) {
                $query->andWhere(['sc.service_id' => $filter->getServiceId()])
                    ->andWhere(new Expression("sub.id IS NULL OR sub.type != 'instagram'"));
            } elseif ($filter->getServiceId() === ReviewSearchFilter::VIRTUAL_SERV_INSTAGRAM) {
                $query->andWhere(['sc.service_id' => 2])
                    ->andWhere(['sub.type' => 'instagram']);
            }
        }

        // Review Rate
        if ($filter->getReviewRate() !== null) {
            $query->andWhere(['r.review_rate' => $filter->getReviewRate()]);
        }

        // Customer Name
        if ($filter->getCustomerName() !== null && $filter->getCustomerName() !== '') {
            $query->andWhere(['like', 'r.customer_name', $filter->getCustomerName()]);
        }

        // Review Text
        if ($filter->getReviewText() !== null && $filter->getReviewText() !== '') {
            $query->andWhere(['like', 'r.review_text', $filter->getReviewText()]);
        }

        // Review Date
        if ($filter->getReviewDate() !== null && $filter->getReviewDate() !== '') {
            $query->andWhere(new Expression(
                "DATE_FORMAT(r.review_date, '%d/%m/%Y %H:%i:%s') LIKE :dateFilter",
                [':dateFilter' => '%' . $filter->getReviewDate() . '%']
            ));
        }

        // Reply Date
        if ($filter->getReplyDate() !== null && $filter->getReplyDate() !== '') {
            $query->andWhere(new Expression(
                "DATE_FORMAT(rr.reply_date, '%d/%m/%Y %H:%i:%s') LIKE :replyDateFilter",
                [':replyDateFilter' => '%' . $filter->getReplyDate() . '%']
            ));
        }

        // Group by review ID to aggregate replies
        $query->groupBy(['r.id']);

        // Read / Status filtering (Status Replied = 9, Read = 1, Unread = 0)
        if ($filter->getRead() !== null) {
            if ($filter->getRead() === Review::STATUS_REPLIED) {
                $query->having(new Expression('COUNT(rr.id) > 0'));
            } else {
                $query->having(new Expression('COUNT(rr.id) = 0'));
                $query->andWhere(['r.read' => $filter->getRead()]);
            }
        }

        // Calculate total count via subquery
        $countQuery = (clone $query)->select(['review_id' => 'r.id']);
        $total = (new Query($this->db))
            ->from(['subq' => $countQuery])
            ->count();

        // Main Select
        $query->select([
            'r.*',
            'service_config_label' => 'sc.label',
            'service_label' => 's.label',
            'sub_service_name' => 'sub.name',
            'sub_service_type' => 'sub.type',
            'is_service_ready' => new Expression("(sc.page_token IS NOT NULL AND sc.page_token != '' OR s.class = 'plugin')"),
            'reply_count' => new Expression('COUNT(rr.id)'),
        ]);

        $sortCol = 'r.' . $filter->getSort();
        $query->orderBy([$sortCol => $filter->getOrder() === 'ASC' ? SORT_ASC : SORT_DESC, 'r.id' => SORT_DESC]);
        $query->limit($filter->getPageSize())->offset($filter->getOffset());

        $rows = $query->all();
        $reviews = [];
        foreach ($rows as $row) {
            $reviews[] = Review::fromDatabaseRow($row);
        }

        return [
            'reviews' => $reviews,
            'total' => (int) $total,
        ];
    }

    public function getMetrics(?TenantContextInterface $context = null): array
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        // Base total query
        $totalQuery = (new Query($this->db))
            ->from(['r' => '{{%review}}'])
            ->innerJoin(['sc' => '{{%service_config}}'], 'sc.id = r.service_config_id');

        // Unread query
        $unreadQuery = (new Query($this->db))
            ->from(['r' => '{{%review}}'])
            ->innerJoin(['sc' => '{{%service_config}}'], 'sc.id = r.service_config_id')
            ->leftJoin(['rr' => '{{%review_reply}}'], 'rr.review_id = r.id')
            ->where(['r.read' => Review::STATUS_NOT_READ])
            ->andWhere(new Expression('rr.id IS NULL'));

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $accountId = $ctx->getAccountId();
            if ($accountId === null) {
                return ['total' => 0, 'unread' => 0];
            }
            $totalQuery->andWhere(['r.account_id' => $accountId]);
            $unreadQuery->andWhere(['r.account_id' => $accountId]);
        }

        $total = (int) $totalQuery->count();
        $unread = (int) $unreadQuery->count();

        return [
            'total' => $total,
            'unread' => $unread,
        ];
    }

    public function getRepliesForReview(int $reviewId, ?TenantContextInterface $context = null): array
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        $query = (new Query($this->db))
            ->select([
                'rr.*',
                'user_name' => 'u.name',
                'service_config_label' => 'sc.label',
            ])
            ->from(['rr' => '{{%review_reply}}'])
            ->innerJoin(['r' => '{{%review}}'], 'r.id = rr.review_id')
            ->leftJoin(['u' => '{{%user}}'], 'u.id = rr.user_id')
            ->leftJoin(['sc' => '{{%service_config}}'], 'sc.id = rr.service_config_id')
            ->where(['rr.review_id' => $reviewId]);

        // Tenant boundary on review replies
        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $accountId = $ctx->getAccountId();
            if ($accountId === null) {
                return [];
            }
            $query->andWhere(['r.account_id' => $accountId]);
        }

        $query->orderBy(['rr.reply_date' => SORT_DESC]);

        $rows = $query->all();
        $replies = [];
        foreach ($rows as $row) {
            $replies[] = ReviewReply::fromDatabaseRow($row);
        }

        return $replies;
    }

    public function getServiceConfigOptions(?TenantContextInterface $context = null): array
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        $query = (new Query($this->db))
            ->select(['sc.id', 'sc.label'])
            ->from(['sc' => '{{%service_config}}'])
            ->where(['sc.linked' => 1]);

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $accountId = $ctx->getAccountId();
            if ($accountId === null) {
                return [];
            }
            $query->andWhere(['sc.account_id' => $accountId]);
        }

        $query->orderBy(['sc.label' => SORT_ASC]);

        $rows = $query->all();
        $options = [];
        foreach ($rows as $row) {
            $options[(int) $row['id']] = (string) $row['label'];
        }

        return $options;
    }

    public function getServiceOptions(): array
    {
        return [
            1 => 'Google MyBusiness',
            2 => 'Facebook',
            ReviewSearchFilter::VIRTUAL_SERV_INSTAGRAM => 'Instagram',
        ];
    }

    public function getExportData(array $params, ?TenantContextInterface $context = null): iterable
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        $query = (new Query($this->db))
            ->select([
                'r.id',
                'review_date' => 'r.review_date',
                'customer_name' => 'r.customer_name',
                'page_name' => 'sc.label',
                'review_rate' => 'r.review_rate',
                'review_text' => 'r.review_text',
                'status' => new Expression("IF(COUNT(rr.id) > 0, 'REPLIED', IF(r.read = 1, 'READ', 'UNREAD'))"),
            ])
            ->from(['r' => '{{%review}}'])
            ->innerJoin(['sc' => '{{%service_config}}'], 'sc.id = r.service_config_id')
            ->leftJoin(['rr' => '{{%review_reply}}'], 'rr.review_id = r.id');

        // Tenant protection: fail closed if restricted and accountId is null
        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $accountId = $ctx->getAccountId();
            if ($accountId === null) {
                return [];
            }
            $query->andWhere(['r.account_id' => $accountId]);
        }

        // Date range handling matching Yii 2 ReviewSearchExport
        $range = strtolower(trim((string) ($params['date_range'] ?? '')));
        if ($range === '30' || $range === '30_days') {
            $query->andWhere(['>=', 'r.review_date', date('Y-m-d 00:00:00', strtotime('-30 days'))]);
        } elseif ($range === '60' || $range === '60_days') {
            $query->andWhere(['>=', 'r.review_date', date('Y-m-d 00:00:00', strtotime('-60 days'))]);
        } elseif ($range === '90' || $range === '90_days') {
            $query->andWhere(['>=', 'r.review_date', date('Y-m-d 00:00:00', strtotime('-90 days'))]);
        }

        // Safe custom date boundary validation
        if (!empty($params['review_date_min'])) {
            $dateMin = trim((string) $params['review_date_min']);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateMin) === 1) {
                $query->andWhere(['>=', 'r.review_date', $dateMin . ' 00:00:00']);
            } elseif (strtotime($dateMin) !== false) {
                $query->andWhere(['>=', 'r.review_date', $dateMin]);
            }
        }
        if (!empty($params['review_date_max'])) {
            $dateMax = trim((string) $params['review_date_max']);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateMax) === 1) {
                $query->andWhere(['<=', 'r.review_date', $dateMax . ' 23:59:59']);
            } elseif (strtotime($dateMax) !== false) {
                $query->andWhere(['<=', 'r.review_date', $dateMax]);
            }
        }

        $query->groupBy(['r.id', 'r.review_date', 'r.customer_name', 'sc.label', 'r.review_rate', 'r.review_text', 'r.read'])
            ->orderBy(['r.review_date' => SORT_DESC]);

        // Chunked cursor streaming via generator
        foreach ($query->each(200) as $row) {
            yield [
                'date' => (string) ($row['review_date'] ?? ''),
                'customer' => (string) ($row['customer_name'] ?? ''),
                'page' => (string) ($row['page_name'] ?? ''),
                'rating' => $row['review_rate'] !== null ? (float) $row['review_rate'] : '',
                'status' => (string) ($row['status'] ?? 'UNREAD'),
                'text' => (string) ($row['review_text'] ?? ''),
            ];
        }
    }

    public function markAsRead(int $reviewId, ?TenantContextInterface $context = null): bool
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        $review = $this->findById($reviewId, $ctx);
        if ($review === null || $review->isRead()) {
            return false;
        }

        try {
            $command = $this->db->createCommand(
                'UPDATE {{%review}} SET `read` = 1 WHERE `id` = :id'
            )->bindValues([':id' => $reviewId]);

            $command->execute();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function delete(int $reviewId, ?TenantContextInterface $context = null): bool
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        // Direct tenant isolation check matching legacy ReviewsController::findModel
        $checkQuery = (new Query($this->db))
            ->from('{{%review}}')
            ->where(['id' => $reviewId]);

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $accountId = $ctx->getAccountId();
            if ($accountId === null) {
                return false;
            }
            $checkQuery->andWhere(['account_id' => $accountId]);
        }

        $row = $checkQuery->one();
        if ($row === false || $row === null) {
            return false;
        }

        $transaction = $this->db->beginTransaction();
        try {
            // Cascade delete replies first
            $this->db->createCommand(
                'DELETE FROM {{%review_reply}} WHERE `review_id` = :reviewId'
            )->bindValues([':reviewId' => $reviewId])->execute();

            // Delete the review
            $this->db->createCommand(
                'DELETE FROM {{%review}} WHERE `id` = :id'
            )->bindValues([':id' => $reviewId])->execute();

            $transaction->commit();
            return true;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            return false;
        }
    }

    public function saveReply(
        int $reviewId,
        int $userId,
        string $message,
        string $objId,
        string $replyDate,
        ?string $replyExtra = null,
        ?TenantContextInterface $context = null,
    ): ?ReviewReply {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        $review = $this->findById($reviewId, $ctx);
        if ($review === null) {
            return null;
        }

        $now = time();
        $transaction = $this->db->beginTransaction();
        try {
            // Check if reply already exists by obj_id
            $existingRow = (new Query($this->db))
                ->from('{{%review_reply}}')
                ->where(['obj_id' => $objId])
                ->one();

            if ($existingRow !== false && $existingRow !== null) {
                $replyId = (int) $existingRow['id'];
                $this->db->createCommand(
                    'UPDATE {{%review_reply}} SET `reply_text` = :replyText, `reply_date` = :replyDate, `reply_extra` = :replyExtra, `updated_at` = :updatedAt WHERE `id` = :id'
                )->bindValues([
                    ':replyText' => $message,
                    ':replyDate' => $replyDate,
                    ':replyExtra' => $replyExtra,
                    ':updatedAt' => $now,
                    ':id' => $replyId,
                ])->execute();
            } else {
                $this->db->createCommand(
                    'INSERT INTO {{%review_reply}} (`account_id`, `user_id`, `review_id`, `service_config_id`, `obj_id`, `reply_text`, `reply_date`, `reply_extra`, `created_at`, `updated_at`) ' .
                    'VALUES (:accountId, :userId, :reviewId, :serviceConfigId, :objId, :replyText, :replyDate, :replyExtra, :createdAt, :updatedAt)'
                )->bindValues([
                    ':accountId' => $review->getAccountId(),
                    ':userId' => $userId,
                    ':reviewId' => $reviewId,
                    ':serviceConfigId' => $review->getServiceConfigId(),
                    ':objId' => $objId,
                    ':replyText' => $message,
                    ':replyDate' => $replyDate,
                    ':replyExtra' => $replyExtra,
                    ':createdAt' => $now,
                    ':updatedAt' => $now,
                ])->execute();

                $replyId = (int) $this->db->getLastInsertID();
            }

            // Update review updated_at timestamp matching legacy Yii 2 ($review->save(false))
            $this->db->createCommand(
                'UPDATE {{%review}} SET `updated_at` = :updatedAt WHERE `id` = :id'
            )->bindValues([
                ':updatedAt' => $now,
                ':id' => $reviewId,
            ])->execute();

            $transaction->commit();

            $createdRow = (new Query($this->db))
                ->select([
                    'rr.*',
                    'user_name' => 'u.username',
                    'service_config_label' => 'sc.label',
                ])
                ->from(['rr' => '{{%review_reply}}'])
                ->leftJoin(['u' => '{{%user}}'], 'u.id = rr.user_id')
                ->innerJoin(['sc' => '{{%service_config}}'], 'sc.id = rr.service_config_id')
                ->where(['rr.id' => $replyId])
                ->one();

            return ($createdRow !== false && $createdRow !== null) ? ReviewReply::fromDatabaseRow($createdRow) : null;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            return null;
        }
    }

    public function findReviewsByIds(array $ids, ?TenantContextInterface $context = null): array
    {
        if (empty($ids)) {
            return [];
        }

        $sanitizedIds = array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0);
        if (empty($sanitizedIds)) {
            return [];
        }

        $ctx = $context ?? $this->tenantContextProvider?->get();

        $query = (new Query($this->db))
            ->select([
                'r.*',
                'service_config_label' => 'sc.label',
                'service_label' => 's.label',
                'sub_service_name' => 'sub.name',
                'sub_service_type' => 'sub.type',
                'is_service_ready' => new Expression("(sc.page_token IS NOT NULL AND sc.page_token != '' OR s.class = 'plugin')"),
                'reply_count' => new Expression('(SELECT COUNT(*) FROM {{%review_reply}} rr WHERE rr.review_id = r.id)'),
            ])
            ->from(['r' => '{{%review}}'])
            ->leftJoin(['sc' => '{{%service_config}}'], 'sc.id = r.service_config_id')
            ->leftJoin(['s' => '{{%service}}'], 's.id = sc.service_id')
            ->leftJoin(['sub' => '{{%sub_service}}'], 'sub.id = r.sub_service_id')
            ->where(['r.id' => $sanitizedIds])
            ->andWhere(['r.type' => Review::TYPE_REVIEW]);

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $accountId = $ctx->getAccountId();
            if ($accountId === null) {
                return [];
            }
            $query->andWhere(['r.account_id' => $accountId]);
        }

        $rows = $query->all();
        $reviews = [];
        foreach ($rows as $row) {
            if ($ctx !== null && isset($row['account_id']) && !$ctx->canAccessAccount((int) $row['account_id'])) {
                continue;
            }
            $reviews[] = Review::fromDatabaseRow($row);
        }

        return $reviews;
    }

    public function findReviewByObjectId(string $objId): ?Review
    {
        $row = (new Query($this->db))
            ->select(['r.*'])
            ->from(['r' => '{{%review}}'])
            ->where(['r.obj_id' => $objId])
            ->one();

        return ($row !== false && $row !== null) ? Review::fromDatabaseRow($row) : null;
    }

    public function findReviewByCustomerDetails(string $customerName, string $reviewDate, string $reviewText, ?int $serviceConfigId = null): ?Review
    {
        $query = (new Query($this->db))
            ->select(['r.*'])
            ->from(['r' => '{{%review}}'])
            ->where([
                'r.customer_name' => $customerName,
                'r.review_date' => $reviewDate,
                'r.review_text' => $reviewText,
            ]);

        if ($serviceConfigId !== null) {
            $query->andWhere(['r.service_config_id' => $serviceConfigId]);
        }

        $row = $query->one();
        return ($row !== false && $row !== null) ? Review::fromDatabaseRow($row) : null;
    }

    public function upsertReview(array $data): array
    {
        $objId = trim((string) ($data['obj_id'] ?? ''));
        if ($objId === '') {
            return ['review_id' => 0, 'action' => 'failed', 'changed' => false];
        }

        $lockKey = 'sys_rev_upsert_' . md5($objId);
        $this->db->createCommand('SELECT GET_LOCK(:name, 10)', [':name' => $lockKey])->execute();

        try {
            $existing = $this->findReviewByObjectId($objId);

            // Google fallback check matching legacy Yii 2
            $isGoogle = !empty($data['is_google']);
            if ($existing === null && $isGoogle) {
                $existing = $this->findReviewByCustomerDetails(
                    (string) ($data['customer_name'] ?? ''),
                    (string) ($data['review_date'] ?? ''),
                    (string) ($data['review_text'] ?? ''),
                    isset($data['service_config_id']) ? (int) $data['service_config_id'] : null
                );
            }

            // Special case: Google User "A Google User" (GMBS)
            if ($existing !== null && $isGoogle && ($data['customer_name'] ?? '') === 'A Google User') {
                return ['review_id' => $existing->getId(), 'action' => 'unchanged', 'changed' => false];
            }

            $now = time();

            if ($existing !== null) {
                $rateMatches = (float) $existing->getReviewRate() === (float) ($data['review_rate'] ?? 0);
                $textMatches = $existing->getReviewText() === (string) ($data['review_text'] ?? '');

                if ($rateMatches && $textMatches) {
                    return ['review_id' => $existing->getId(), 'action' => 'unchanged', 'changed' => false];
                }

                // Record changed: update existing review
                $this->db->createCommand(
                    'UPDATE {{%review}} SET `review_rate` = :rate, `review_text` = :text, `review_extra` = :extra, `review_recommend` = :recommend, `updated_at` = :upd WHERE `id` = :id'
                )->bindValues([
                    ':rate' => $data['review_rate'] ?? null,
                    ':text' => $data['review_text'] ?? '',
                    ':extra' => $data['review_extra'] ?? null,
                    ':recommend' => $data['review_recommend'] ?? null,
                    ':upd' => $now,
                    ':id' => $existing->getId(),
                ])->execute();

                return ['review_id' => $existing->getId(), 'action' => 'updated', 'changed' => true];
            }

            // Insert new review record
            $this->db->createCommand(
                'INSERT INTO {{%review}} (' .
                '`account_id`, `service_config_id`, `sub_service_id`, `obj_id`, `customer_id`, ' .
                '`customer_name`, `customer_email`, `customer_phone`, `customer_state`, `customer_region`, ' .
                '`review_rate`, `review_text`, `review_recommend`, `review_service`, `review_date`, ' .
                '`review_extra`, `read`, `spam_score`, `spam_status`, `type`, `created_at`, `updated_at`' .
                ') VALUES (' .
                ':accountId, :serviceConfigId, :subServiceId, :objId, :customerId, ' .
                ':customerName, :customerEmail, :customerPhone, :customerState, :customerRegion, ' .
                ':reviewRate, :reviewText, :reviewRecommend, :reviewService, :reviewDate, ' .
                ':reviewExtra, :read, :spamScore, :spamStatus, :type, :createdAt, :updatedAt' .
                ')'
            )->bindValues([
                ':accountId' => isset($data['account_id']) ? (int) $data['account_id'] : null,
                ':serviceConfigId' => (int) ($data['service_config_id'] ?? 0),
                ':subServiceId' => isset($data['sub_service_id']) ? (int) $data['sub_service_id'] : null,
                ':objId' => $objId,
                ':customerId' => (string) ($data['customer_id'] ?? ''),
                ':customerName' => (string) ($data['customer_name'] ?? 'Customer'),
                ':customerEmail' => isset($data['customer_email']) ? (string) $data['customer_email'] : null,
                ':customerPhone' => isset($data['customer_phone']) ? (string) $data['customer_phone'] : null,
                ':customerState' => isset($data['customer_state']) ? (string) $data['customer_state'] : null,
                ':customerRegion' => isset($data['customer_region']) ? (string) $data['customer_region'] : null,
                ':reviewRate' => isset($data['review_rate']) ? (float) $data['review_rate'] : null,
                ':reviewText' => (string) ($data['review_text'] ?? '-'),
                ':reviewRecommend' => isset($data['review_recommend']) ? (string) $data['review_recommend'] : null,
                ':reviewService' => isset($data['review_service']) ? (string) $data['review_service'] : 'Google',
                ':reviewDate' => (string) ($data['review_date'] ?? date('Y-m-d H:i:s')),
                ':reviewExtra' => isset($data['review_extra']) ? (string) $data['review_extra'] : null,
                ':read' => isset($data['read']) ? (int) $data['read'] : Review::STATUS_NOT_READ,
                ':spamScore' => isset($data['spam_score']) ? (int) $data['spam_score'] : 0,
                ':spamStatus' => isset($data['spam_status']) ? (string) $data['spam_status'] : Review::SPAM_NORMAL,
                ':type' => (string) ($data['type'] ?? Review::TYPE_REVIEW),
                ':createdAt' => $now,
                ':updatedAt' => $now,
            ])->execute();

            $newId = (int) $this->db->getLastInsertID();

            return ['review_id' => $newId, 'action' => 'inserted', 'changed' => false];
        } finally {
            try {
                $this->db->createCommand('SELECT RELEASE_LOCK(:name)', [':name' => $lockKey])->execute();
            } catch (Throwable) {
                // Ignore lock release error
            }
        }
    }

    public function insertOrUpdateReviewReplyComment(
        int $reviewId,
        int $accountId,
        int $serviceConfigId,
        string $objId,
        ?string $customerId,
        ?string $customerName,
        string $replyText,
        string $replyDate,
        ?string $replyExtra = null
    ): ?int {
        $lockKey = 'sys_reply_upsert_' . md5($objId);
        $this->db->createCommand('SELECT GET_LOCK(:name, 10)', [':name' => $lockKey])->execute();

        try {
            $now = time();
            $row = (new Query($this->db))
                ->select(['id'])
                ->from('{{%review_reply}}')
                ->where(['obj_id' => $objId])
                ->one();

            if ($row !== false && $row !== null) {
                $replyId = (int) $row['id'];
                $this->db->createCommand(
                    'UPDATE {{%review_reply}} SET `reply_text` = :text, `reply_date` = :date, `reply_extra` = :extra, `updated_at` = :upd WHERE `id` = :id'
                )->bindValues([
                    ':text' => $replyText,
                    ':date' => $replyDate,
                    ':extra' => $replyExtra,
                    ':upd' => $now,
                    ':id' => $replyId,
                ])->execute();

                return $replyId;
            }

            $this->db->createCommand(
                'INSERT INTO {{%review_reply}} (' .
                '`account_id`, `review_id`, `service_config_id`, `obj_id`, `customer_id`, ' .
                '`customer_name`, `reply_text`, `reply_date`, `reply_extra`, `created_at`, `updated_at`' .
                ') VALUES (' .
                ':accId, :revId, :scId, :objId, :custId, :custName, :text, :date, :extra, :created, :updated' .
                ')'
            )->bindValues([
                ':accId' => $accountId,
                ':revId' => $reviewId,
                ':scId' => $serviceConfigId,
                ':objId' => $objId,
                ':custId' => $customerId,
                ':custName' => $customerName,
                ':text' => $replyText,
                ':date' => $replyDate,
                ':extra' => $replyExtra,
                ':created' => $now,
                ':updated' => $now,
            ])->execute();

            return (int) $this->db->getLastInsertID();
        } finally {
            try {
                $this->db->createCommand('SELECT RELEASE_LOCK(:name)', [':name' => $lockKey])->execute();
            } catch (Throwable) {
                // Ignore lock release error
            }
        }
    }

    public function findExistingObjectIdsForConfig(int $serviceConfigId): array
    {
        $rows = (new Query($this->db))
            ->select(['obj_id'])
            ->from('{{%review}}')
            ->where(['service_config_id' => $serviceConfigId])
            ->column();

        return array_map('strval', $rows);
    }
}
