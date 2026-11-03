<?php

declare(strict_types=1);

namespace App\Domain\SocialConnection;

/**
 * ServiceConfig Domain Entity.
 * Represents an account connection to an external social platform (Google or Facebook).
 * Mapped to database table 'service_config'.
 */
final class ServiceConfig
{
    public const SERVICE_GOOGLE = 1;
    public const SERVICE_FACEBOOK = 2;

    public const LINKED_NO = 0;
    public const LINKED_YES = 1;

    /**
     * @param int $id
     * @param int|null $accountId
     * @param int $serviceId
     * @param int|null $apiId
     * @param string $label
     * @param string|null $apiKey
     * @param string|null $clientId
     * @param string|null $token
     * @param string|null $pageId
     * @param string|null $pageName
     * @param string|null $pageToken
     * @param int $linked
     * @param string|null $emailLowReview
     * @param int|null $createdAt
     * @param int|null $updatedAt
     * @param array<int, SubService> $subServices
     */
    public function __construct(
        private int $id,
        private ?int $accountId,
        private int $serviceId,
        private ?int $apiId,
        private string $label,
        private ?string $apiKey = null,
        private ?string $clientId = null,
        private ?string $token = null,
        private ?string $pageId = null,
        private ?string $pageName = null,
        private ?string $pageToken = null,
        private int $linked = self::LINKED_YES,
        private ?string $emailLowReview = null,
        private ?int $createdAt = null,
        private ?int $updatedAt = null,
        private array $subServices = [],
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            accountId: isset($row['account_id']) ? (int) $row['account_id'] : null,
            serviceId: (int) ($row['service_id'] ?? 0),
            apiId: isset($row['api_id']) ? (int) $row['api_id'] : null,
            label: (string) ($row['label'] ?? ''),
            apiKey: isset($row['api_key']) ? (string) $row['api_key'] : null,
            clientId: isset($row['client_id']) ? (string) $row['client_id'] : null,
            token: isset($row['token']) ? (string) $row['token'] : null,
            pageId: isset($row['page_id']) ? (string) $row['page_id'] : null,
            pageName: isset($row['page_name']) ? (string) $row['page_name'] : null,
            pageToken: isset($row['page_token']) ? (string) $row['page_token'] : null,
            linked: (int) ($row['linked'] ?? self::LINKED_YES),
            emailLowReview: isset($row['email_low_review']) ? (string) $row['email_low_review'] : null,
            createdAt: isset($row['created_at']) ? (int) $row['created_at'] : null,
            updatedAt: isset($row['updated_at']) ? (int) $row['updated_at'] : null,
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAccountId(): ?int
    {
        return $this->accountId;
    }

    public function getServiceId(): int
    {
        return $this->serviceId;
    }

    public function isGoogle(): bool
    {
        return $this->serviceId === self::SERVICE_GOOGLE;
    }

    public function isFacebook(): bool
    {
        return $this->serviceId === self::SERVICE_FACEBOOK;
    }

    public function getApiId(): ?int
    {
        return $this->apiId;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function getPageId(): ?string
    {
        return $this->pageId;
    }

    public function getPageName(): ?string
    {
        return $this->pageName;
    }

    public function getDisplayName(): string
    {
        return !empty($this->pageName) ? $this->pageName : $this->label;
    }

    public function getPageToken(): ?string
    {
        return $this->pageToken;
    }

    public function isLinked(): bool
    {
        return $this->linked === self::LINKED_YES;
    }

    public function getLinked(): int
    {
        return $this->linked;
    }

    public function getEmailLowReview(): ?string
    {
        return $this->emailLowReview;
    }

    public function getCreatedAt(): ?int
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?int
    {
        return $this->updatedAt;
    }

    /**
     * @return array<int, SubService>
     */
    public function getSubServices(): array
    {
        return $this->subServices;
    }

    /**
     * @param array<int, SubService> $subServices
     */
    public function withSubServices(array $subServices): self
    {
        $clone = clone $this;
        $clone->subServices = $subServices;
        return $clone;
    }
}
