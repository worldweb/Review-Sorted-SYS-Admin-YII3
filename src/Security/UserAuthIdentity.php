<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Dedicated Authentication Identity DTO.
 * Encapsulates credentials strictly for authentication services.
 * Never returned in domain user queries, API responses, or serialized arrays.
 */
final class UserAuthIdentity
{
    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;

    public function __construct(
        private int $id,
        private string $name,
        private string $email,
        private string $passwordHash,
        private ?string $authKey = null,
        private int $status = self::STATUS_ACTIVE,
        private ?int $accountId = null,
        private ?string $role = null,
        private ?int $ownerId = null,
    ) {
    }

    public static function fromDatabaseRow(array $row, ?string $role = null): self
    {
        return new self(
            id: (int) $row['id'],
            name: (string) ($row['name'] ?? ''),
            email: (string) ($row['email'] ?? ''),
            passwordHash: (string) ($row['password_hash'] ?? ''),
            authKey: isset($row['auth_key']) && $row['auth_key'] !== '' ? (string) $row['auth_key'] : null,
            status: (int) ($row['status'] ?? self::STATUS_ACTIVE),
            accountId: isset($row['account_id']) && $row['account_id'] !== null ? (int) $row['account_id'] : null,
            role: $role ?? ($row['role'] ?? null),
            ownerId: isset($row['owner_id']) && $row['owner_id'] !== null ? (int) $row['owner_id'] : null,
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

    /**
     * Validates a candidate plaintext password against the stored bcrypt hash.
     * Uses PHP's native password_verify API matching Yii 2 password verification.
     */
    public function validatePassword(string $password): bool
    {
        if ($this->passwordHash === '' || $password === '') {
            return false;
        }

        return password_verify($password, $this->passwordHash);
    }

    /**
     * Validates an authentication token (cookie / remember-me / bearer key) using timing-safe comparison.
     */
    public function validateAuthKey(string $authKey): bool
    {
        if ($this->authKey === null || $this->authKey === '' || $authKey === '') {
            return false;
        }

        return hash_equals($this->authKey, $authKey);
    }
}
