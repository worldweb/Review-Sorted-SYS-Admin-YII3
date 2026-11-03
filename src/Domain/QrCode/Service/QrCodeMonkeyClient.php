<?php

declare(strict_types=1);

namespace App\Domain\QrCode\Service;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Throwable;

/**
 * QRCodeMonkey Remote API Client.
 * Communicates with https://api.qrcode-monkey.com/ for high-resolution QR rendering
 * and logo uploading with TLS verification and connection timeouts.
 */
final class QrCodeMonkeyClient implements QrCodeMonkeyClientInterface
{
    private const API_CUSTOM_QR = 'https://api.qrcode-monkey.com/qr/custom';
    private const API_UPLOAD_IMAGE = 'https://api.qrcode-monkey.com/qr/uploadimage';

    private ClientInterface $httpClient;
    private LoggerInterface $logger;

    public function __construct(
        ?ClientInterface $httpClient = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->httpClient = $httpClient ?? new Client([
            'timeout' => 15.0,
            'connect_timeout' => 5.0,
            'verify' => true,
        ]);
        $this->logger = $logger ?? new NullLogger();
    }

    public function generateCustomQr(
        string $payloadUrl,
        array $config,
        int $size,
        ?string $localLogoPath,
        string $outputPath
    ): string {
        $effectiveConfig = $config;

        // If local custom logo provided, upload to remote API first
        if ($localLogoPath !== null && $localLogoPath !== '' && file_exists($localLogoPath)) {
            $uploadedLogoId = $this->uploadLogo($localLogoPath);
            if ($uploadedLogoId !== null && $uploadedLogoId !== '') {
                $effectiveConfig['logo'] = $uploadedLogoId;
                $effectiveConfig['logoMode'] = 'default';
            }
        }

        $postData = [
            'data' => $payloadUrl,
            'config' => $effectiveConfig,
            'size' => $size,
            'download' => 'imageUrl',
            'file' => 'png',
        ];

        try {
            $response = $this->httpClient->request('POST', self::API_CUSTOM_QR, [
                'json' => $postData,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'verify' => true,
                'timeout' => 15.0,
                'connect_timeout' => 5.0,
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error('QRCodeMonkey API custom QR generation failed with HTTP status: ' . $response->getStatusCode());
                throw new RuntimeException('Remote QR API generation failed.');
            }

            $body = (string) $response->getBody();
            $result = json_decode($body, true);

            if (!is_array($result) || empty($result['imageUrl'])) {
                $errorMsg = $result['errorMessage'] ?? 'Unknown API response';
                $this->logger->error('QRCodeMonkey API returned invalid payload or error: ' . $errorMsg);
                throw new RuntimeException('Remote QR API returned an error response.');
            }

            $imageUrl = (string) $result['imageUrl'];
            if (str_starts_with($imageUrl, '//')) {
                $imageUrl = 'https:' . $imageUrl;
            }

            // Download generated image stream
            $imgResponse = $this->httpClient->request('GET', $imageUrl, [
                'verify' => true,
                'timeout' => 15.0,
                'connect_timeout' => 5.0,
            ]);

            if ($imgResponse->getStatusCode() !== 200) {
                $this->logger->error('Failed to download generated QR image stream with HTTP status: ' . $imgResponse->getStatusCode());
                throw new RuntimeException('Failed to download generated QR image stream.');
            }

            $imgData = (string) $imgResponse->getBody();
            if ($imgData === '') {
                throw new RuntimeException('Downloaded QR payload is empty.');
            }

            // Validate that downloaded data is a valid image stream
            $testImg = @imagecreatefromstring($imgData);
            if (!$testImg) {
                throw new RuntimeException('Downloaded QR payload is not a valid image format.');
            }

            // Ensure destination directory exists
            $dir = dirname($outputPath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }

            if (file_put_contents($outputPath, $imgData) === false) {
                throw new RuntimeException('Failed to write QR image to disk: ' . $outputPath);
            }

            return $outputPath;

        } catch (GuzzleException $e) {
            $this->logger->error('QRCodeMonkey API network/HTTP failure: ' . $e->getMessage());
            throw new RuntimeException('Remote QR generation service is temporarily unavailable.', 0, $e);
        } catch (Throwable $e) {
            $this->logger->error('QRCodeMonkey API unexpected error: ' . $e->getMessage());
            throw new RuntimeException('Failed to process remote QR code generation.', 0, $e);
        }
    }

    public function uploadLogo(string $localLogoPath): ?string
    {
        if (!file_exists($localLogoPath) || !is_readable($localLogoPath)) {
            return null;
        }

        $fileName = basename($localLogoPath);

        try {
            $response = $this->httpClient->request('POST', self::API_UPLOAD_IMAGE, [
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => fopen($localLogoPath, 'r'),
                        'filename' => $fileName,
                    ],
                ],
                'verify' => true,
                'timeout' => 10.0,
                'connect_timeout' => 5.0,
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error('QRCodeMonkey logo upload returned HTTP status: ' . $response->getStatusCode());
                return null;
            }

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            if (is_array($data) && !empty($data['file'])) {
                return (string) $data['file'];
            }

            $this->logger->warning('QRCodeMonkey logo upload response missing file identifier.');
            return null;

        } catch (Throwable $e) {
            $this->logger->error('QRCodeMonkey logo upload failed: ' . $e->getMessage());
            return null;
        }
    }
}
