<?php

declare(strict_types=1);

namespace App\Domain\Review;

/**
 * Result DTO representing the outcome of an external channel reply dispatch.
 */
final class ReplyDispatchResult
{
    public function __construct(
        private bool $success,
        private ?string $objId = null,
        private ?string $replyDate = null,
        private ?string $replyExtra = null,
        private ?string $errorMessage = null,
        private ?string $errorCode = null,
        private ?string $platform = null,
    ) {
    }

    public static function success(
        string $objId,
        string $replyDate,
        ?string $replyExtra = null,
        ?string $platform = null,
    ): self {
        return new self(
            success: true,
            objId: $objId,
            replyDate: $replyDate,
            replyExtra: $replyExtra,
            errorMessage: null,
            errorCode: null,
            platform: $platform,
        );
    }

    public static function failure(
        string $errorMessage,
        ?string $errorCode = null,
        ?string $platform = null,
    ): self {
        return new self(
            success: false,
            objId: null,
            replyDate: null,
            replyExtra: null,
            errorMessage: $errorMessage,
            errorCode: $errorCode,
            platform: $platform,
        );
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getObjId(): ?string
    {
        return $this->objId;
    }

    public function getReplyDate(): ?string
    {
        return $this->replyDate;
    }

    public function getReplyExtra(): ?string
    {
        return $this->replyExtra;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getPlatform(): ?string
    {
        return $this->platform;
    }
}
