<?php

declare(strict_types=1);

namespace App\Domain\SocialPost\Client;

use App\Domain\SocialPost\Exception\GoogleApiException;
use App\Domain\SocialPost\Exception\GoogleNetworkException;

/**
 * Contract for interacting with Google Business Profile Local Posts API v4 and OAuth token endpoint.
 */
interface GoogleApiClientInterface
{
    /**
     * Publishes a Local Post to Google Business Profile v4 API.
     *
     * @param string $parent Resource name (e.g. 'accounts/{acc}/locations/{loc}' or 'locations/{loc}')
     * @param array<string, mixed> $postData Local Post payload (summary, topicType, media, languageCode)
     * @param string $accessToken Valid OAuth access token
     * @return array<string, mixed> Decoded JSON response containing 'name'
     * @throws GoogleApiException On confirmed Google API error response (HTTP 4xx/5xx)
     * @throws GoogleNetworkException On transport, timeout, or network connection failures
     */
    public function createLocalPost(string $parent, array $postData, string $accessToken): array;

    /**
     * Refreshes an expired OAuth access token using Google's OAuth2 token endpoint.
     *
     * @param string $refreshToken OAuth refresh token
     * @param string $clientId Google client ID
     * @param string $clientSecret Google client secret
     * @return array<string, mixed> Decoded token response with 'access_token', 'expires_in', etc.
     * @throws GoogleApiException On OAuth endpoint rejection (HTTP 4xx/5xx)
     * @throws GoogleNetworkException On transport or connection failure
     */
    public function refreshToken(string $refreshToken, string $clientId, string $clientSecret): array;
}

