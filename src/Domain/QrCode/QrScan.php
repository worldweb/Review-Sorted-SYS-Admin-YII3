<?php

declare(strict_types=1);

namespace App\Domain\QrCode;

/**
 * QR Code Scan Domain Entity.
 * Represents an individual client device scan event for dynamic tracking & analytics.
 * Mapped to database table 'qrcode_scan'.
 */
final class QrScan
{
    public function __construct(
        private int $id,
        private int $qrCodeId,
        private string $userIp,
        private int $createdAt,
        private int $updatedAt,
        // Joined metadata
        private ?string $qrCodeName = null,
        private ?string $qrDestinationUrl = null,
    ) {
    }

    /**
     * Reconstitute a QrScan domain entity from a database row.
     *
     * @param array<string, mixed> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            qrCodeId: (int) $row['qrcode_id'],
            userIp: (string) ($row['user_ip'] ?? ''),
            createdAt: (int) ($row['created_at'] ?? 0),
            updatedAt: (int) ($row['updated_at'] ?? 0),
            qrCodeName: isset($row['qrcode_name']) ? (string) $row['qrcode_name'] : null,
            qrDestinationUrl: isset($row['qrcode_url']) ? (string) $row['qrcode_url'] : null,
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getQrCodeId(): int
    {
        return $this->qrCodeId;
    }

    public function getUserIp(): string
    {
        return $this->userIp;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): int
    {
        return $this->updatedAt;
    }

    public function getQrCodeName(): ?string
    {
        return $this->qrCodeName;
    }

    public function getQrDestinationUrl(): ?string
    {
        return $this->qrDestinationUrl;
    }

    public function getCreatedDateTime(): \DateTimeImmutable
    {
        return (new \DateTimeImmutable())->setTimestamp($this->createdAt);
    }

    public function getUpdatedDateTime(): \DateTimeImmutable
    {
        return (new \DateTimeImmutable())->setTimestamp($this->updatedAt);
    }
}
