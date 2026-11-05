<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialPost\Client;

use App\Domain\SocialPost\Client\GoogleApiClientInterface;
use App\Domain\SocialPost\Exception\GoogleApiClientException;
use App\Domain\SocialPost\Exception\GoogleApiException;
use App\Domain\SocialPost\Exception\GoogleNetworkException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * Google Business Profile API HTTP Client.
 *
 * Interacts with Google My Business v4 Local Posts REST API and OAuth2 token endpoints.
 * Enforces credential redaction, granular failure classification, and distinct transport handling.
 */
final class GoogleApiClient implements GoogleApiClientInterface
{
    private const GOOGLE_API_BASE = 'https://mybusiness.googleapis.com/v4/';
    private const OAUTH_TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
    private const DEFAULT_TIMEOUT = 60.0;
    private const DEFAULT_CONNECT_TIMEOUT = 10.0;

    private ClientInterface $httpClient;
    private LoggerInterface $logger;

    public function __construct(
        ?ClientInterface $httpClient = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->httpClient = $httpClient ?? new Client([
            'timeout' => self::DEFAULT_TIMEOUT,
            'connect_timeout' => self::DEFAULT_CONNECT_TIMEOUT,
            'verify' => true,
        ]);
        $this->logger = $logger ?? new NullLogger();
    }

    public function createLocalPost(string $parent, array $postData, string $accessToken): array
    {
        $url = self::GOOGLE_API_BASE . trim($parent, '/') . '/localPosts';

        $this->logger->debug(sprintf(
            '[GoogleApiClient] Creating Local Post for parent resource "%s"',
            $this->sanitizeUrl($url)
        ));

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $postData,
            ]);

            return $this->parseResponse($response);
        } catch (RequestException $e) {
            throw $this->handleRequestException($e, isAmbiguousPossible: true);
        } catch (Throwable $e) {
            if ($e instanceof GoogleApiClientException) {
                throw $e;
            }
            throw new GoogleApiClientException($this->sanitizeMessage($e->getMessage()), 0, $e);
        }
    }

    public function refreshToken(string $refreshToken, string $clientId, string $clientSecret): array
    {
        $this->logger->debug('[GoogleApiClient] Requesting OAuth access token refresh from Google token endpoint.');

        try {
            $response = $this->httpClient->request('POST', self::OAUTH_TOKEN_ENDPOINT, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ],
                'form_params' => [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'refresh_token' => $refreshToken,
                    'grant_type' => 'refresh_token',
                ],
            ]);

            $data = $this->parseResponse($response);
            if (empty($data['access_token'])) {
                throw new GoogleApiException(
                    'Google OAuth token endpoint returned success but omitted access_token in response payload.',
                    $response->getStatusCode()
                );
            }

            return $data;
        } catch (RequestException $e) {
            // Token refresh failure cannot cause ambiguous post delivery on social platform
            throw $this->handleRequestException($e, isAmbiguousPossible: false);
        } catch (Throwable $e) {
            if ($e instanceof GoogleApiClientException) {
                throw $e;
            }
            throw new GoogleApiClientException($this->sanitizeMessage($e->getMessage()), 0, $e);
        }
    }

    /**
     * Parses a successful HTTP response.
     *
     * @return array<string, mixed>
     * @throws GoogleApiException
     */
    private function parseResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new GoogleApiException(
                'Google API returned a non-JSON or malformed response body.',
                $statusCode
            );
        }

        if (isset($data['error'])) {
            $retryAfter = $this->parseRetryAfter($response);
            throw GoogleApiException::fromResponseData($data, $statusCode, $retryAfter);
        }

        return $data;
    }

    /**
     * Translates a Guzzle RequestException into either a confirmed GoogleApiException
     * or a GoogleNetworkException.
     */
    private function handleRequestException(RequestException $e, bool $isAmbiguousPossible = true): Throwable
    {
        if ($e->hasResponse()) {
            $response = $e->getResponse();
            $statusCode = $response !== null ? $response->getStatusCode() : 400;
            $body = $response !== null ? (string) $response->getBody() : '';

            $data = json_decode($body, true);
            if (is_array($data)) {
                $retryAfter = $this->parseRetryAfter($response);
                return GoogleApiException::fromResponseData($data, $statusCode, $retryAfter);
            }

            return new GoogleApiException(
                $this->sanitizeMessage($e->getMessage()),
                $statusCode
            );
        }

        // No HTTP response received (DNS failure, connection drop, socket timeout)
        return new GoogleNetworkException(
            $this->sanitizeMessage($e->getMessage()),
            isAmbiguousPossible: $isAmbiguousPossible,
            previous: $e
        );
    }

    private function parseRetryAfter(?ResponseInterface $response): ?int
    {
        if ($response === null || !$response->hasHeader('Retry-After')) {
            return null;
        }

        $header = trim($response->getHeaderLine('Retry-After'));
        if (is_numeric($header)) {
            return (int) $header;
        }

        $timestamp = strtotime($header);
        if ($timestamp !== false) {
            $diff = $timestamp - time();
            return $diff > 0 ? $diff : null;
        }

        return null;
    }

    private function sanitizeUrl(string $url): string
    {
        return (string) preg_replace(
            '/([?&](?:access_token|refresh_token|client_secret|client_id|api_key|key)=)[^&\s]+/i',
            '$1[REDACTED]',
            $url
        );
    }

    private function sanitizeMessage(string $msg): string
    {
        // 1. Redact Authorization Bearer tokens
        $sanitized = (string) preg_replace('/(bearer\s+)[^\s"\';&]+/i', '$1[REDACTED]', $msg);

        // 2. Redact JSON-style credentials
        $sanitized = (string) preg_replace(
            '/(["\']?\b(?:access_token|refresh_token|client_secret|client_id|api_key|key)\b["\']?\s*:\s*["\']?)[^"\'\r\n,}]+/i',
            '$1[REDACTED]',
            $sanitized
        );

        // 3. Redact Query-string and Form-encoded credentials
        return (string) preg_replace(
            '/(\b(?:access_token|refresh_token|client_secret|client_id|api_key|key)=)[^&\s"\';]+/i',
            '$1[REDACTED]',
            $sanitized
        );
    }
}
