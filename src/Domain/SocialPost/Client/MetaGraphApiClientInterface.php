<?php

declare(strict_types=1);

namespace App\Domain\SocialPost\Client;

use App\Domain\SocialPost\Exception\MetaGraphApiException;
use App\Domain\SocialPost\Exception\MetaNetworkException;

/**
 * Contract for interacting with Meta Graph API v25.0 endpoints.
 */
interface MetaGraphApiClientInterface
{
    /**
     * Executes a GET request against Meta Graph API v25.0.
     *
     * @param string $endpoint The endpoint path (e.g. '/{page-id}/feed' or '{container-id}')
     * @param array<string, mixed> $params Query parameters
     * @param string $accessToken Page or User Access Token
     * @return array<string, mixed> Decoded JSON response
     * @throws MetaGraphApiException On confirmed Meta Graph API error response (HTTP 4xx/5xx)
     * @throws MetaNetworkException On transport, timeout, or network connection failures
     */
    public function get(string $endpoint, array $params, string $accessToken): array;

    /**
     * Executes a POST request against Meta Graph API v25.0.
     *
     * @param string $endpoint The endpoint path (e.g. '/{page-id}/feed')
     * @param array<string, mixed> $params Form parameters
     * @param string $accessToken Page or User Access Token
     * @return array<string, mixed> Decoded JSON response
     * @throws MetaGraphApiException On confirmed Meta Graph API error response (HTTP 4xx/5xx)
     * @throws MetaNetworkException On transport, timeout, or network connection failures
     */
    public function post(string $endpoint, array $params, string $accessToken): array;
}

