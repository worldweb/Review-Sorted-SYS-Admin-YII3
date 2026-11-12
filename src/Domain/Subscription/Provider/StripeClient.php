<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Provider;

use App\Environment;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * StripeClient.
 * REST client for Stripe API using Guzzle.
 * Enforces strict credentials check — will NEVER return simulated success or create fake billing records
 * when Stripe keys are unconfigured.
 */
final class StripeClient implements StripeClientInterface
{
    private const BASE_URI = 'https://api.stripe.com/v1/';

    private ?Client $httpClient = null;
    private LoggerInterface $logger;

    public function __construct(
        ?LoggerInterface $logger = null,
        private ?string $secretKey = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function isConfigured(): bool
    {
        $key = $this->getSecretKey();
        return $key !== '' && (str_starts_with($key, 'sk_test_') || str_starts_with($key, 'sk_live_') || str_starts_with($key, 'rk_'));
    }

    private function getSecretKey(): string
    {
        if ($this->secretKey !== null && $this->secretKey !== '') {
            return $this->secretKey;
        }

        return Environment::stripeSecretKey();
    }

    private function getHttpClient(): Client
    {
        if ($this->httpClient === null) {
            $key = $this->getSecretKey();
            if (!$this->isConfigured()) {
                throw new RuntimeException('Stripe API error: Secret key is not configured. Real Stripe operations cannot be performed.');
            }

            $this->httpClient = new Client([
                'base_uri' => self::BASE_URI,
                'timeout' => 15.0,
                'connect_timeout' => 5.0,
                'headers' => [
                    'Authorization' => 'Bearer ' . $key,
                    'Stripe-Version' => '2024-06-20',
                    'Accept' => 'application/json',
                ],
            ]);
        }

        return $this->httpClient;
    }

    public function createCustomer(array $params): array
    {
        return $this->post('customers', $params);
    }

    public function retrieveCustomer(string $customerId): ?array
    {
        return $this->get('customers/' . urlencode($customerId));
    }

    public function createPlan(array $params): array
    {
        return $this->post('plans', $params);
    }

    public function createSubscription(array $params): array
    {
        return $this->post('subscriptions', $params);
    }

    public function retrieveSubscription(string $subscriptionId): ?array
    {
        return $this->get('subscriptions/' . urlencode($subscriptionId));
    }

    public function updateSubscription(string $subscriptionId, array $params): array
    {
        return $this->post('subscriptions/' . urlencode($subscriptionId), $params);
    }

    public function cancelSubscription(string $subscriptionId): array
    {
        return $this->delete('subscriptions/' . urlencode($subscriptionId));
    }

    public function retrieveInvoice(string $invoiceId): ?array
    {
        return $this->get('invoices/' . urlencode($invoiceId));
    }

    public function retrievePaymentIntent(string $paymentIntentId): ?array
    {
        return $this->get('payment_intents/' . urlencode($paymentIntentId));
    }

    public function createPrice(array $params): array
    {
        return $this->post('prices', $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function post(string $endpoint, array $params): array
    {
        $client = $this->getHttpClient();
        try {
            $response = $client->post($endpoint, [
                'form_params' => $params,
            ]);
            $body = (string) $response->getBody();
            $data = json_decode($body, true);
            return is_array($data) ? $data : [];
        } catch (GuzzleException $e) {
            $this->logger->error(sprintf('[StripeClient] POST %s failed: %s', $endpoint, $e->getMessage()));
            throw new RuntimeException('Stripe API request failed: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function get(string $endpoint): ?array
    {
        $client = $this->getHttpClient();
        try {
            $response = $client->get($endpoint);
            $body = (string) $response->getBody();
            $data = json_decode($body, true);
            return is_array($data) ? $data : null;
        } catch (GuzzleException $e) {
            $this->logger->error(sprintf('[StripeClient] GET %s failed: %s', $endpoint, $e->getMessage()));
            throw new RuntimeException('Stripe API request failed: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function delete(string $endpoint): array
    {
        $client = $this->getHttpClient();
        try {
            $response = $client->delete($endpoint);
            $body = (string) $response->getBody();
            $data = json_decode($body, true);
            return is_array($data) ? $data : [];
        } catch (GuzzleException $e) {
            $this->logger->error(sprintf('[StripeClient] DELETE %s failed: %s', $endpoint, $e->getMessage()));
            throw new RuntimeException('Stripe API request failed: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}

