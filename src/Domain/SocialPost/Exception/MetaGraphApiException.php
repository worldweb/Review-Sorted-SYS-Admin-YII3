<?php

declare(strict_types=1);

namespace App\Domain\SocialPost\Exception;

/**
 * Exception thrown when Meta Graph API returns an HTTP 4xx or 5xx error response with an error payload.
 */
final class MetaGraphApiException extends MetaClientException
{
    /**
     * @param string $message Sanitized error message
     * @param int $httpStatusCode HTTP response status code
     * @param int $errorCode Meta Graph API numeric error code (e.g. 190, 200, 100, 4)
     * @param int|null $errorSubcode Meta error subcode (e.g. 463 for expired token)
     * @param string|null $errorType Meta error type (e.g. 'OAuthException')
     * @param string|null $errorUserTitle User-facing error title if provided
     * @param string|null $errorUserMsg User-facing error message if provided
     * @param string|null $fbtraceId Trace ID for Meta support
     * @param int|null $retryAfterSeconds Delay suggested by Retry-After header
     * @param array<string, mixed>|null $rawError Raw error array from response
     */
    public function __construct(
        string $message,
        private int $httpStatusCode = 400,
        private int $errorCode = 0,
        private ?int $errorSubcode = null,
        private ?string $errorType = null,
        private ?string $errorUserTitle = null,
        private ?string $errorUserMsg = null,
        private ?string $fbtraceId = null,
        private ?int $retryAfterSeconds = null,
        private ?array $rawError = null,
    ) {
        parent::__construct($message, $errorCode);
    }

    /**
     * @param array<string, mixed> $errorData Decoded 'error' block from Meta response
     */
    public static function fromResponseData(array $errorData, int $httpStatusCode, ?int $retryAfter = null): self
    {
        $rawMsg = (string) ($errorData['message'] ?? 'Unknown Meta Graph API error');
        $code = (int) ($errorData['code'] ?? $httpStatusCode);
        $subcode = isset($errorData['error_subcode']) ? (int) $errorData['error_subcode'] : null;
        $type = isset($errorData['type']) ? (string) $errorData['type'] : null;
        $userTitle = isset($errorData['error_user_title']) ? (string) $errorData['error_user_title'] : null;
        $userMsg = isset($errorData['error_user_msg']) ? (string) $errorData['error_user_msg'] : null;
        $fbtraceId = isset($errorData['fbtrace_id']) ? (string) $errorData['fbtrace_id'] : null;

        // Sanitize access tokens out of error messages
        $cleanMsg = (string) preg_replace('/([?&]access_token=)[^&\s]+/i', '$1[REDACTED]', $rawMsg);

        return new self(
            message: $cleanMsg,
            httpStatusCode: $httpStatusCode,
            errorCode: $code,
            errorSubcode: $subcode,
            errorType: $type,
            errorUserTitle: $userTitle,
            errorUserMsg: $userMsg,
            fbtraceId: $fbtraceId,
            retryAfterSeconds: $retryAfter,
            rawError: $errorData,
        );
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    public function getErrorSubcode(): ?int
    {
        return $this->errorSubcode;
    }

    public function getErrorType(): ?string
    {
        return $this->errorType;
    }

    public function getErrorUserTitle(): ?string
    {
        return $this->errorUserTitle;
    }

    public function getErrorUserMsg(): ?string
    {
        return $this->errorUserMsg;
    }

    public function getFbtraceId(): ?string
    {
        return $this->fbtraceId;
    }

    public function getRetryAfterSeconds(): ?int
    {
        return $this->retryAfterSeconds;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRawError(): ?array
    {
        return $this->rawError;
    }

    public function isAuthError(): bool
    {
        return $this->errorCode === 190
            || ($this->errorSubcode !== null && in_array($this->errorSubcode, [458, 459, 460, 463, 467, 490], true));
    }

    public function isPermissionError(): bool
    {
        return in_array($this->errorCode, [10, 200, 298], true);
    }

    public function isRateLimit(): bool
    {
        return in_array($this->errorCode, [4, 17, 32, 341, 613], true)
            || ($this->httpStatusCode === 429)
            || (isset($this->rawError['is_transient']) && $this->rawError['is_transient'] === true);
    }

    public function isPermanentParameterError(): bool
    {
        return $this->errorCode === 100;
    }

    public function isServerCrash(): bool
    {
        return in_array($this->errorCode, [1, 2], true) || $this->httpStatusCode >= 500;
    }
}

