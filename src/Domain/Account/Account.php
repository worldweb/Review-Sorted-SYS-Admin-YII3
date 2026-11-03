<?php

declare(strict_types=1);

namespace App\Domain\Account;

/**
 * Account Domain Entity / DTO
 * Represents a tenant account record safely, avoiding exposure of sensitive credentials.
 */
final class Account
{
    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;

    public function __construct(
        private int $id,
        private ?string $businessName = null,
        private int $status = self::STATUS_ACTIVE,
        private int $maxUsers = 1,
        private ?string $activationKey = null,
        private ?string $state = null,
        private ?string $firstName = null,
        private ?string $lastName = null,
        private ?string $email = null,
        private ?string $contactNumber = null,
        private ?string $website = null,
        private ?int $createdAt = null,
        private ?int $updatedAt = null,
        private int $qrCode = 0,
    ) {
    }

    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            businessName: $row['business_name'] ?? null,
            status: (int) ($row['status'] ?? self::STATUS_ACTIVE),
            maxUsers: (int) ($row['maxUsers'] ?? $row['max_users'] ?? 1),
            activationKey: $row['activation_key'] ?? null,
            state: $row['state'] ?? null,
            firstName: $row['first_name'] ?? null,
            lastName: $row['last_name'] ?? null,
            email: $row['email'] ?? null,
            contactNumber: $row['contact_number'] ?? null,
            website: $row['website'] ?? null,
            createdAt: isset($row['created_at']) && $row['created_at'] !== null ? (int) $row['created_at'] : null,
            updatedAt: isset($row['updated_at']) && $row['updated_at'] !== null ? (int) $row['updated_at'] : null,
            qrCode: (int) ($row['qr_code'] ?? 0),
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getBusinessName(): ?string
    {
        return $this->businessName;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getStatusName(): string
    {
        return $this->status === self::STATUS_ACTIVE ? 'Active' : 'Disabled';
    }

    public function getCreatedAtStr(string $format = 'M j, Y'): string
    {
        return $this->createdAt !== null ? date($format, $this->createdAt) : '-';
    }

    public function getMaxUsers(): int
    {
        return $this->maxUsers;
    }

    public function getActivationKey(): ?string
    {
        return $this->activationKey;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getContactNumber(): ?string
    {
        return $this->contactNumber;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function getCreatedAt(): ?int
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?int
    {
        return $this->updatedAt;
    }

    public function getQrCode(): int
    {
        return $this->qrCode;
    }

    public function isQrCodeEnabled(): bool
    {
        return $this->qrCode === 1;
    }

    /**
     * Safe array representation excluding sensitive internal API keys or tokens.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'business_name' => $this->businessName,
            'status' => $this->status,
            'is_active' => $this->isActive(),
            'max_users' => $this->maxUsers,
            'state' => $this->state,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'contact_number' => $this->contactNumber,
            'website' => $this->website,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
