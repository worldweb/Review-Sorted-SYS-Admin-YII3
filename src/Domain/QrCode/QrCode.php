<?php

declare(strict_types=1);

namespace App\Domain\QrCode;

/**
 * QR Code Domain Entity.
 * Represents a saved QR Code configuration and generated asset record.
 * Mapped to database table 'qrcode'.
 */
final class QrCode
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_DEACTIVE = 'deactive';

    public const LOGO_TYPE_DEFAULT = 'default';
    public const LOGO_TYPE_UPLOADED = 'uploaded';

    public function __construct(
        private int $id,
        private int $userId,
        private ?string $name,
        private ?string $uploadLogo,
        private ?string $logoType,
        private ?string $qrcode,
        private ?string $url,
        private ?string $templateName,
        private ?string $defaultLogoName,
        private ?string $frameName,
        private ?string $totalScan,
        private string $status,
        private int $createdAt,
        private int $updatedAt,
        // Joined / calculated metadata
        private ?int $accountId = null,
        private ?string $creatorName = null,
        private ?string $creatorEmail = null,
        private ?int $scanCount = null,
    ) {
    }

    /**
     * Reconstitute a QrCode domain entity from a database row.
     *
     * @param array<string, mixed> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            name: isset($row['name']) && $row['name'] !== '' ? (string) $row['name'] : null,
            uploadLogo: isset($row['upload_logo']) && $row['upload_logo'] !== '' ? (string) $row['upload_logo'] : null,
            logoType: isset($row['logo_type']) && $row['logo_type'] !== '' ? (string) $row['logo_type'] : null,
            qrcode: isset($row['qrcode']) && $row['qrcode'] !== '' ? (string) $row['qrcode'] : null,
            url: isset($row['url']) && $row['url'] !== '' ? (string) $row['url'] : null,
            templateName: isset($row['template_name']) && $row['template_name'] !== '' ? (string) $row['template_name'] : null,
            defaultLogoName: isset($row['default_logo_name']) && $row['default_logo_name'] !== '' ? (string) $row['default_logo_name'] : null,
            frameName: isset($row['frame_name']) && $row['frame_name'] !== '' ? (string) $row['frame_name'] : null,
            totalScan: isset($row['total_scan']) && $row['total_scan'] !== '' ? (string) $row['total_scan'] : null,
            status: (string) ($row['status'] ?? self::STATUS_ACTIVE),
            createdAt: (int) ($row['created_at'] ?? 0),
            updatedAt: (int) ($row['updated_at'] ?? 0),
            accountId: isset($row['account_id']) && $row['account_id'] !== null ? (int) $row['account_id'] : null,
            creatorName: isset($row['creator_name']) ? (string) $row['creator_name'] : (isset($row['creator_username']) ? (string) $row['creator_username'] : null),
            creatorEmail: isset($row['creator_email']) ? (string) $row['creator_email'] : null,
            scanCount: isset($row['scan_count']) && $row['scan_count'] !== null ? (int) $row['scan_count'] : null,
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getUploadLogo(): ?string
    {
        return $this->uploadLogo;
    }

    public function getLogoType(): ?string
    {
        return $this->logoType;
    }

    public function getQrcode(): ?string
    {
        return $this->qrcode;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getTemplateName(): ?string
    {
        return $this->templateName;
    }

    public function getDefaultLogoName(): ?string
    {
        return $this->defaultLogoName;
    }

    public function getFrameName(): ?string
    {
        return $this->frameName;
    }

    public function getTotalScan(): ?string
    {
        return $this->totalScan;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): int
    {
        return $this->updatedAt;
    }

    public function getAccountId(): ?int
    {
        return $this->accountId;
    }

    public function getCreatorName(): ?string
    {
        return $this->creatorName;
    }

    public function getCreatorUsername(): ?string
    {
        return $this->creatorName;
    }

    public function getCreatorEmail(): ?string
    {
        return $this->creatorEmail;
    }

    public function getScanCount(): ?int
    {
        return $this->scanCount;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isDeactive(): bool
    {
        return $this->status === self::STATUS_DEACTIVE;
    }

    public function isUploadedLogo(): bool
    {
        return $this->logoType === self::LOGO_TYPE_UPLOADED;
    }

    public function isDefaultLogo(): bool
    {
        return $this->logoType === self::LOGO_TYPE_DEFAULT;
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
