<?php

declare(strict_types=1);

namespace App\Domain\User;

/**
 * User Domain Entity / DTO.
 * Represents a user safely without exposing auth tokens, password hashes, or session keys.
 */
final class User
{
    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;

    public const ROLE_SUPER = 'super';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_ACCOUNT_ADMIN = 'account_admin';
    public const ROLE_WORKER = 'worker';

    public function __construct(
        private int $id,
        private string $name,
        private string $email,
        private int $status = self::STATUS_ACTIVE,
        private ?int $accountId = null,
        private ?int $ownerId = null,
        private ?string $role = null,
        private ?int $createdAt = null,
        private ?int $updatedAt = null,
    ) {
    }

    public static function fromDatabaseRow(array $row, ?string $role = null): self
    {
        return new self(
            id: (int) $row['id'],
            name: (string) ($row['name'] ?? ''),
            email: (string) ($row['email'] ?? ''),
            status: (int) ($row['status'] ?? self::STATUS_ACTIVE),
            accountId: isset($row['account_id']) && $row['account_id'] !== null ? (int) $row['account_id'] : null,
            ownerId: isset($row['owner_id']) && $row['owner_id'] !== null ? (int) $row['owner_id'] : null,
            role: $role ?? ($row['role'] ?? null),
            createdAt: isset($row['created_at']) && $row['created_at'] !== null ? (int) $row['created_at'] : null,
            updatedAt: isset($row['updated_at']) && $row['updated_at'] !== null ? (int) $row['updated_at'] : null,
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getAccountId(): ?int
    {
        return $this->accountId;
    }

    public function getOwnerId(): ?int
    {
        return $this->ownerId;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN || $this->role === self::ROLE_SUPER;
    }

    public function isAccountAdmin(): bool
    {
        return $this->role === self::ROLE_ACCOUNT_ADMIN;
    }

    public function isWorker(): bool
    {
        return $this->role === self::ROLE_WORKER;
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
     * Safe array representation excluding credentials and hashes.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'is_active' => $this->isActive(),
            'account_id' => $this->accountId,
            'owner_id' => $this->ownerId,
            'role' => $this->role,
            'is_admin' => $this->isAdmin(),
            'is_account_admin' => $this->isAccountAdmin(),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
