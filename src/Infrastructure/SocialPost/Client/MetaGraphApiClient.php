<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialPost\Client;

use App\Domain\SocialPost\Client\MetaGraphApiClientInterface;
use App\Domain\SocialPost\Exception\MetaClientException;
use App\Domain\SocialPost\Exception\MetaGraphApiException;
use App\Domain\SocialPost\Exception\MetaNetworkException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * Meta Graph API HTTP Client targeting API version v25.0.
 *
 * Implements strict credential sanitization, granular outcome exceptions,
 * and distinguished handling of transport vs confirmed API errors.
 */
final class MetaGraphApiClient implements MetaGraphApiClientInterface
{
    private const GRAPH_API_BASE = 'https://graph.facebook.com/v25.0/';
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

    public function get(string $endpoint, array $params, string $accessToken): array
    {
        $url = $this->buildUrl($endpoint);
        $queryParams = array_merge($params, ['access_token' => $accessToken]);

        $this->logger->debug(sprintf(
            '[MetaGraphApiClient] Executing GET request to endpoint "%s"',
            $this->sanitizeUrl($url)
        ));

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'query' => $queryParams,
            ]);

            return $this->parseResponse($response);
        } catch (RequestException $e) {
            throw $this->handleRequestException($e, 'GET');
        } catch (Throwable $e) {
            throw new MetaClientException($this->sanitizeMessage($e->getMessage()), 0, $e);
        }
    }

    public function post(string $endpoint, array $params, string $accessToken): array
    {
        $url = $this->buildUrl($endpoint);
        $formParams = array_merge($params, ['access_token' => $accessToken]);

        $this->logger->debug(sprintf(
            '[MetaGraphApiClient] Executing POST request to endpoint "%s"',
            $this->sanitizeUrl($url)
        ));

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'form_params' => $formParams,
            ]);

            return $this->parseResponse($response);
        } catch (RequestException $e) {
            throw $this->handleRequestException($e, 'POST');
        } catch (Throwable $e) {
            throw new MetaClientException($this->sanitizeMessage($e->getMessage()), 0, $e);
        }
    }

    private function buildUrl(string $endpoint): string
    {
        return self::GRAPH_API_BASE . ltrim($endpoint, '/');
    }

    /**
     * Parses a successful HTTP response.
     *
     * @return array<string, mixed>
     * @throws MetaGraphApiException
     */
    private function parseResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new MetaGraphApiException(
                'Meta Graph API returned a non-JSON or malformed response.',
                $statusCode
            );
        }

        if (isset($data['error']) && is_array($data['error'])) {
            $retryAfter = $this->parseRetryAfter($response);
            throw MetaGraphApiException::fromResponseData($data['error'], $statusCode, $retryAfter);
        }

        return $data;
    }

    /**
     * Translates a Guzzle RequestException into either a confirmed MetaGraphApiException
     * or a MetaNetworkException.
     */
    private function handleRequestException(RequestException $e, string $httpMethod): Throwable
    {
        if ($e->hasResponse()) {
            $response = $e->getResponse();
            $statusCode = $response !== null ? $response->getStatusCode() : 400;
            $body = $response !== null ? (string) $response->getBody() : '';

            $data = json_decode($body, true);
            if (is_array($data) && isset($data['error']) && is_array($data['error'])) {
                $retryAfter = $this->parseRetryAfter($response);
                return MetaGraphApiException::fromResponseData($data['error'], $statusCode, $retryAfter);
            }

            return new MetaGraphApiException(
                $this->sanitizeMessage($e->getMessage()),
                $statusCode
            );
        }

        // No HTTP response received (DNS failure, connection refused, socket timeout, host unreachable)
        $isAmbiguousPossible = ($httpMethod === 'POST');

        return new MetaNetworkException(
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
        return (string) preg_replace('/([?&]access_token=)[^&\s]+/i', '$1[REDACTED]', $url);
    }

    private function sanitizeMessage(string $msg): string
    {
        return (string) preg_replace('/([?&]access_token=)[^&\s]+/i', '$1[REDACTED]', $msg);
    }
}

