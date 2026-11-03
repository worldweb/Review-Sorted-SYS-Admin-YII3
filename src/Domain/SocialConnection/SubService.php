<?php

declare(strict_types=1);

namespace App\Domain\SocialConnection;

/**
 * SubService Domain Entity.
 * Represents an auxiliary service tied to a primary ServiceConfig (such as an Instagram business account linked to a Facebook Page).
 * Mapped to database table 'sub_service'.
 */
final class SubService
{
    public const TYPE_INSTAGRAM = 'INSTAGRAM';

    /**
     * @param int $id
     * @param int $accountId
     * @param int $serviceConfigId
     * @param string $type
     * @param string $name
     * @param string $objId
     * @param string|null $token
     * @param string|null $extra
     * @param int $createdAt
     * @param int $updatedAt
     */
    public function __construct(
        private int $id,
        private int $accountId,
        private int $serviceConfigId,
        private string $type,
        private string $name,
        private string $objId,
        private ?string $token = null,
        private ?string $extra = null,
        private int $createdAt = 0,
        private int $updatedAt = 0,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            accountId: (int) ($row['account_id'] ?? 0),
            serviceConfigId: (int) ($row['service_config_id'] ?? 0),
            type: (string) ($row['type'] ?? self::TYPE_INSTAGRAM),
            name: (string) ($row['name'] ?? ''),
            objId: (string) ($row['obj_id'] ?? ''),
            token: isset($row['token']) ? (string) $row['token'] : null,
            extra: isset($row['extra']) ? (string) $row['extra'] : null,
            createdAt: (int) ($row['created_at'] ?? 0),
            updatedAt: (int) ($row['updated_at'] ?? 0),
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function getServiceConfigId(): int
    {
        return $this->serviceConfigId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getObjId(): string
    {
        return $this->objId;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function getExtra(): ?string
    {
        return $this->extra;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): int
    {
        return $this->updatedAt;
    }
}
