<?php

declare(strict_types=1);

namespace App\Domain\Email;

/**
 * Email Queue Domain Entity.
 * Represents an outbound message queued for transmission via Mailgun API.
 * Mapped to database table 'email_queue'.
 */
final class EmailQueue
{
    public const STATUS_FAILED = -1;
    public const STATUS_QUEUED = 0;
    public const STATUS_SENT = 1;
    public const STATUS_DELIVERED = 2;
    public const STATUS_ACCEPTED = 3;
    public const STATUS_PROCESSING = 4;

    public const STATUS_LABELS = [
        self::STATUS_FAILED => 'Failed',
        self::STATUS_QUEUED => 'Queued',
        self::STATUS_SENT => 'Sent',
        self::STATUS_DELIVERED => 'Delivered',
        self::STATUS_ACCEPTED => 'Accepted',
        self::STATUS_PROCESSING => 'Processing',
    ];

    public function __construct(
        private int $id,
        private int $accountId,
        private ?int $emailTemplateId,
        private ?int $ref,
        private string $name,
        private string $email,
        private ?string $bcc,
        private string $subject,
        private string $body,
        private int $status,
        private ?string $sendAt = null,
        private ?string $sentAt = null,
        private ?string $lockedAt = null,
        private ?string $externalId = null,
        private ?int $createdAt = null,
        private ?int $updatedAt = null,
        private ?string $templateName = null,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            accountId: (int) ($row['account_id'] ?? 0),
            emailTemplateId: isset($row['email_template_id']) && (int) $row['email_template_id'] > 0
                ? (int) $row['email_template_id']
                : null,
            ref: isset($row['ref']) && (int) $row['ref'] > 0 ? (int) $row['ref'] : null,
            name: (string) ($row['name'] ?? ''),
            email: (string) ($row['email'] ?? ''),
            bcc: isset($row['bcc']) && $row['bcc'] !== '' ? (string) $row['bcc'] : null,
            subject: (string) ($row['subject'] ?? ''),
            body: (string) ($row['body'] ?? ''),
            status: (int) ($row['status'] ?? self::STATUS_QUEUED),
            sendAt: isset($row['send_at']) && $row['send_at'] !== '' ? (string) $row['send_at'] : null,
            sentAt: isset($row['sent_at']) && $row['sent_at'] !== '' ? (string) $row['sent_at'] : null,
            lockedAt: isset($row['locked_at']) && $row['locked_at'] !== '' ? (string) $row['locked_at'] : null,
            externalId: isset($row['external_id']) && $row['external_id'] !== '' ? (string) $row['external_id'] : null,
            createdAt: isset($row['created_at']) && (int) $row['created_at'] > 0 ? (int) $row['created_at'] : null,
            updatedAt: isset($row['updated_at']) && (int) $row['updated_at'] > 0 ? (int) $row['updated_at'] : null,
            templateName: isset($row['template_name']) && $row['template_name'] !== '' ? (string) $row['template_name'] : null,
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

    public function getEmailTemplateId(): ?int
    {
        return $this->emailTemplateId;
    }

    public function getRef(): ?int
    {
        return $this->ref;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getBcc(): ?string
    {
        return $this->bcc;
    }

    /**
     * @return string[]
     */
    public function getBccArray(): array
    {
        if (empty($this->bcc)) {
            return [];
        }

        $decoded = json_decode($this->bcc, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('strval', $decoded)));
        }

        return [trim($this->bcc)];
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getStatusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? 'Unknown';
    }

    public function getSendAt(): ?string
    {
        return $this->sendAt;
    }

    public function getSentAt(): ?string
    {
        return $this->sentAt;
    }

    public function getLockedAt(): ?string
    {
        return $this->lockedAt;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function getCreatedAt(): ?int
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?int
    {
        return $this->updatedAt;
    }

    public function getTemplateName(): ?string
    {
        return $this->templateName;
    }

    public function isQueued(): bool
    {
        return $this->status === self::STATUS_QUEUED;
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT || $this->status === self::STATUS_DELIVERED || $this->status === self::STATUS_ACCEPTED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isTimeToSend(): bool
    {
        if (empty($this->sendAt)) {
            return true;
        }

        return strtotime($this->sendAt) <= time();
    }

    public static function createQueued(
        int $accountId,
        string $name,
        string $email,
        string $subject,
        string $body,
        ?int $templateId = null,
        ?string $bcc = null,
        ?string $sendAt = null,
        ?int $ref = null,
    ): self {
        $now = time();
        return new self(
            id: 0,
            accountId: $accountId,
            emailTemplateId: $templateId,
            ref: $ref,
            name: $name,
            email: $email,
            bcc: $bcc,
            subject: $subject,
            body: $body,
            status: self::STATUS_QUEUED,
            sendAt: $sendAt,
            sentAt: null,
            lockedAt: null,
            externalId: null,
            createdAt: $now,
            updatedAt: $now,
        );
    }
}
