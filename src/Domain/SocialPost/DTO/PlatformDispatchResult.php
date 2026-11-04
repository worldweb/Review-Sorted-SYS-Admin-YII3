<?php

declare(strict_types=1);

namespace App\Domain\SocialPost\DTO;

/**
 * Readonly DTO representing the outcome of a remote platform publishing dispatch attempt.
 */
final readonly class PlatformDispatchResult
{
    /**
     * @param DispatchOutcome $outcome Classified delivery outcome
     * @param string|null $remotePostId Remote post ID confirmed by external platform
     * @param int|null $httpStatusCode HTTP response status code, if an HTTP response was received
     * @param string|null $errorMessage Human-readable error description or failure reason
     * @param int|null $retryAfterSeconds Recommended delay in seconds if provided by platform Retry-After header
     * @param array<string, mixed>|null $rawResponse Sanitized diagnostic response metadata
     */
    public function __construct(
        private DispatchOutcome $outcome,
        private ?string $remotePostId = null,
        private ?int $httpStatusCode = null,
        private ?string $errorMessage = null,
        private ?int $retryAfterSeconds = null,
        private ?array $rawResponse = null,
    ) {
    }

    public static function success(
        string $remotePostId,
        ?int $httpStatusCode = 200,
        ?array $rawResponse = null,
    ): self {
        return new self(
            outcome: DispatchOutcome::SUCCESS,
            remotePostId: $remotePostId,
            httpStatusCode: $httpStatusCode,
            rawResponse: self::sanitizeResponse($rawResponse),
        );
    }

    public static function temporaryFailure(
        string $errorMessage,
        ?int $httpStatusCode = null,
        ?int $retryAfterSeconds = null,
        ?array $rawResponse = null,
    ): self {
        return new self(
            outcome: DispatchOutcome::TEMPORARY_FAILURE,
            httpStatusCode: $httpStatusCode,
            errorMessage: $errorMessage,
            retryAfterSeconds: $retryAfterSeconds,
            rawResponse: self::sanitizeResponse($rawResponse),
        );
    }

    public static function permanentFailure(
        string $errorMessage,
        ?int $httpStatusCode = null,
        ?array $rawResponse = null,
    ): self {
        return new self(
            outcome: DispatchOutcome::PERMANENT_FAILURE,
            httpStatusCode: $httpStatusCode,
            errorMessage: $errorMessage,
            rawResponse: self::sanitizeResponse($rawResponse),
        );
    }

    public static function ambiguousFailure(
        string $errorMessage,
        ?int $httpStatusCode = null,
    ): self {
        return new self(
            outcome: DispatchOutcome::AMBIGUOUS_FAILURE,
            httpStatusCode: $httpStatusCode,
            errorMessage: $errorMessage,
        );
    }

    public function getOutcome(): DispatchOutcome
    {
        return $this->outcome;
    }

    public function getRemotePostId(): ?string
    {
        return $this->remotePostId;
    }

    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getRetryAfterSeconds(): ?int
    {
        return $this->retryAfterSeconds;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRawResponse(): ?array
    {
        return $this->rawResponse;
    }

    public function isSuccess(): bool
    {
        return $this->outcome->isSuccess();
    }

    public function isRetryable(): bool
    {
        return $this->outcome->isRetryable();
    }

    public function isAmbiguous(): bool
    {
        return $this->outcome->isAmbiguous();
    }

    public function isPermanentFailure(): bool
    {
        return $this->outcome->isPermanentFailure();
    }

    /**
     * Sanitizes response data to prevent accidental persistence of credentials or access tokens.
     *
     * @param array<string, mixed>|null $data
     * @return array<string, mixed>|null
     */
    private static function sanitizeResponse(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        $sensitiveKeys = ['access_token', 'token', 'secret', 'client_secret', 'password', 'authorization'];
        $sanitized = [];

        foreach ($data as $key => $val) {
            if (in_array(strtolower((string) $key), $sensitiveKeys, true)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($val)) {
                $sanitized[$key] = self::sanitizeResponse($val);
            } else {
                $sanitized[$key] = $val;
            }
        }

        return $sanitized;
    }
}

