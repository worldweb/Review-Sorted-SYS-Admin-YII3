<?php

declare(strict_types=1);

namespace App\Domain\SocialConnection;

/**
 * Domain entity representing an external API configuration record (table: api).
 * Handles Facebook Developer Applications and Google Cloud Projects credentials.
 */
final class Api
{
    public const SERVICE_GOOGLE = 1;
    public const SERVICE_FACEBOOK = 2;
    public const SERVICE_TWITTER = 3;

    public function __construct(
        private int $id,
        private int $serviceId,
        private string $label,
        private ?string $apiKey = null,
        private ?string $clientId = null,
        private ?string $token = null,
        private ?string $clientJson = null,
        private ?int $createdAt = null,
        private ?int $updatedAt = null,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            serviceId: (int) ($row['service_id'] ?? 0),
            label: (string) ($row['label'] ?? ''),
            apiKey: isset($row['api_key']) ? (string) $row['api_key'] : null,
            clientId: isset($row['client_id']) ? (string) $row['client_id'] : null,
            token: isset($row['token']) ? (string) $row['token'] : null,
            clientJson: isset($row['client_json']) ? (string) $row['client_json'] : null,
            createdAt: isset($row['created_at']) ? (int) $row['created_at'] : null,
            updatedAt: isset($row['updated_at']) ? (int) $row['updated_at'] : null,
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getServiceId(): int
    {
        return $this->serviceId;
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

    public function getClientJson(): ?string
    {
        return $this->clientJson;
    }

    public function getCreatedAt(): ?int
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?int
    {
        return $this->updatedAt;
    }

    public function isFacebookAuthenticated(): bool
    {
        return !empty($this->token);
    }

    public function getCreatedAtStr(): string
    {
        if (empty($this->createdAt)) {
            return '—';
        }
        return date('d M Y H:i', $this->createdAt);
    }

    public function getAppSecretMasked(): string
    {
        if (empty($this->apiKey)) {
            return 'Not Configured';
        }
        $len = strlen($this->apiKey);
        if ($len > 6) {
            return str_repeat('*', $len - 6) . substr($this->apiKey, -6);
        }
        return str_repeat('*', $len);
    }
}
