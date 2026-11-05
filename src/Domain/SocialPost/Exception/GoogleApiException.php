<?php

declare(strict_types=1);

namespace App\Domain\SocialPost\Exception;

/**
 * Exception thrown when Google API or OAuth endpoint returns an HTTP 4xx or 5xx error response.
 */
final class GoogleApiException extends GoogleApiClientException
{
    /**
     * @param string $message Sanitized error message
     * @param int $httpStatusCode HTTP response status code
     * @param string|int $errorCode Numeric HTTP code or OAuth error string (e.g. 403, 'invalid_grant')
     * @param string|null $errorStatus Google API status string (e.g. 'PERMISSION_DENIED', 'RESOURCE_EXHAUSTED', 'NOT_FOUND')
     * @param int|null $retryAfterSeconds Delay suggested by Retry-After header
     * @param array<string, mixed>|null $rawError Raw error payload from response
     */
    public function __construct(
        string $message,
        private int $httpStatusCode = 400,
        private string|int $errorCode = 0,
        private ?string $errorStatus = null,
        private ?int $retryAfterSeconds = null,
        private ?array $rawError = null,
    ) {
        parent::__construct($message, is_int($errorCode) ? $errorCode : $httpStatusCode);
    }

    /**
     * @param array<string, mixed> $errorData Decoded response payload or error block
     */
    public static function fromResponseData(array $errorData, int $httpStatusCode, ?int $retryAfter = null): self
    {
        $raw = $errorData;
        $errorStatus = null;
        $errorCode = $httpStatusCode;

        if (isset($errorData['error']) && is_array($errorData['error'])) {
            // Google v4 REST API error format: {"error": {"code": 403, "message": "...", "status": "PERMISSION_DENIED"}}
            $inner = $errorData['error'];
            $rawMsg = (string) ($inner['message'] ?? 'Unknown Google API error');
            $errorCode = (int) ($inner['code'] ?? $httpStatusCode);
            $errorStatus = isset($inner['status']) ? (string) $inner['status'] : null;
            $raw = $inner;
        } elseif (isset($errorData['error']) && is_string($errorData['error'])) {
            // Google OAuth token endpoint format: {"error": "invalid_grant", "error_description": "..."}
            $errorStatus = (string) $errorData['error'];
            $rawMsg = (string) ($errorData['error_description'] ?? $errorData['error']);
            $errorCode = $errorStatus;
            $raw = $errorData;
        } else {
            $rawMsg = (string) ($errorData['message'] ?? 'Unknown Google API error');
            $errorStatus = isset($errorData['status']) ? (string) $errorData['status'] : null;
            $errorCode = (int) ($errorData['code'] ?? $httpStatusCode);
        }

        // Sanitize sensitive credentials out of message
        $cleanMsg = (string) preg_replace(
            '/(bearer\s+|access_token=|refresh_token=|client_secret=)[^\s&]+/i',
            '$1[REDACTED]',
            $rawMsg
        );

        return new self(
            message: $cleanMsg,
            httpStatusCode: $httpStatusCode,
            errorCode: $errorCode,
            errorStatus: $errorStatus,
            retryAfterSeconds: $retryAfter,
            rawError: $raw,
        );
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    public function getErrorCode(): string|int
    {
        return $this->errorCode;
    }

    public function getErrorStatus(): ?string
    {
        return $this->errorStatus;
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
        $statusLower = strtolower($this->errorStatus ?? '');
        $msgLower = strtolower($this->getMessage());

        return $this->httpStatusCode === 401
            || $this->errorStatus === 'UNAUTHENTICATED'
            || $this->errorCode === 'invalid_grant'
            || str_contains($statusLower, 'unauthenticated')
            || str_contains($msgLower, 'invalid_grant')
            || str_contains($msgLower, 'unauthenticated');
    }

    public function isPermissionError(): bool
    {
        return $this->httpStatusCode === 403 || $this->errorStatus === 'PERMISSION_DENIED';
    }

    public function isNotFound(): bool
    {
        return $this->httpStatusCode === 404 || $this->errorStatus === 'NOT_FOUND';
    }

    public function isPreconditionFailed(): bool
    {
        $statusLower = strtolower($this->errorStatus ?? '');
        $msgLower = strtolower($this->getMessage());

        return $this->errorStatus === 'FAILED_PRECONDITION'
            || str_contains($statusLower, 'failed_precondition')
            || str_contains($msgLower, 'failed_precondition');
    }

    public function isRateLimit(): bool
    {
        return $this->httpStatusCode === 429
            || $this->errorStatus === 'RESOURCE_EXHAUSTED'
            || ($this->httpStatusCode === 403 && $this->errorStatus === 'RATE_LIMIT_EXCEEDED');
    }

    public function isServerCrash(): bool
    {
        return $this->httpStatusCode >= 500
            || in_array($this->errorStatus, ['INTERNAL', 'UNAVAILABLE', 'DEADLINE_EXCEEDED'], true);
    }
}

