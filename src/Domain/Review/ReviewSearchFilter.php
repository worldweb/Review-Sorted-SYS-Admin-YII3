<?php

declare(strict_types=1);

namespace App\Domain\Review;

/**
 * ReviewSearchFilter DTO.
 * Encapsulates search and filtering parameters for review queries.
 */
final class ReviewSearchFilter
{
    public const VIRTUAL_SERV_INSTAGRAM = -1;

    public function __construct(
        private ?int $serviceConfigId = null,
        private ?int $serviceId = null,
        private ?float $reviewRate = null,
        private ?int $read = null,
        private ?string $customerName = null,
        private ?string $reviewText = null,
        private ?string $reviewDate = null,
        private ?string $replyDate = null,
        private int $page = 1,
        private int $pageSize = 20,
        private string $sort = 'review_date',
        private string $order = 'DESC',
    ) {
    }

    public static function fromQueryParams(array $params, int $defaultPageSize = 20): self
    {
        // Support nested ReviewSearch params or flat query parameters
        $data = isset($params['ReviewSearch']) && is_array($params['ReviewSearch'])
            ? array_merge($params, $params['ReviewSearch'])
            : $params;

        $serviceConfigId = null;
        if (isset($data['service_config']) && $data['service_config'] !== '') {
            $serviceConfigId = (int) $data['service_config'];
        } elseif (isset($data['service_config_id']) && $data['service_config_id'] !== '') {
            $serviceConfigId = (int) $data['service_config_id'];
        }

        $serviceId = null;
        if (isset($data['service']) && $data['service'] !== '') {
            $serviceId = (int) $data['service'];
        }

        $reviewRate = null;
        if (isset($data['review_rate']) && $data['review_rate'] !== '') {
            $reviewRate = (float) $data['review_rate'];
        }

        $read = null;
        if (isset($data['read']) && $data['read'] !== '') {
            $read = (int) $data['read'];
        }

        $customerName = !empty($data['customer_name']) ? trim((string) $data['customer_name']) : null;
        $reviewText = !empty($data['review_text']) ? trim((string) $data['review_text']) : null;
        $reviewDate = !empty($data['review_date']) ? trim((string) $data['review_date']) : null;
        $replyDate = !empty($data['reply_date']) ? trim((string) $data['reply_date']) : null;

        $page = isset($data['page']) ? max(1, (int) $data['page']) : 1;
        $pageSize = isset($data['pageSize']) ? max(1, min(100, (int) $data['pageSize'])) : $defaultPageSize;

        $sort = isset($data['sort']) && in_array($data['sort'], ['review_date', 'review_rate', 'customer_name', 'created_at'], true)
            ? (string) $data['sort']
            : 'review_date';

        $order = isset($data['order']) && strtoupper((string) $data['order']) === 'ASC' ? 'ASC' : 'DESC';

        return new self(
            serviceConfigId: $serviceConfigId,
            serviceId: $serviceId,
            reviewRate: $reviewRate,
            read: $read,
            customerName: $customerName,
            reviewText: $reviewText,
            reviewDate: $reviewDate,
            replyDate: $replyDate,
            page: $page,
            pageSize: $pageSize,
            sort: $sort,
            order: $order,
        );
    }

    public function getServiceConfigId(): ?int
    {
        return $this->serviceConfigId;
    }

    public function getServiceId(): ?int
    {
        return $this->serviceId;
    }

    public function getReviewRate(): ?float
    {
        return $this->reviewRate;
    }

    public function getRead(): ?int
    {
        return $this->read;
    }

    public function getCustomerName(): ?string
    {
        return $this->customerName;
    }

    public function getReviewText(): ?string
    {
        return $this->reviewText;
    }

    public function getReviewDate(): ?string
    {
        return $this->reviewDate;
    }

    public function getReplyDate(): ?string
    {
        return $this->replyDate;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function getSort(): string
    {
        return $this->sort;
    }

    public function getOrder(): string
    {
        return $this->order;
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->pageSize;
    }
}
