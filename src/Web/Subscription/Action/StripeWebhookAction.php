<?php

declare(strict_types=1);

namespace App\Web\Subscription\Action;

use App\Environment;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * StripeWebhookAction.
 * Receives incoming asynchronous webhook events from Stripe for invoice payment,
 * subscription renewal, and checkout completion.
 * Cryptographically validates webhook signatures using native HMAC-SHA256 matching Stripe specification.
 */
final class StripeWebhookAction
{
    private LoggerInterface $logger;

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $body = (string) $request->getBody();
        $secret = Environment::stripeWebhookSecret();

        // Cryptographically verify signature if secret is configured
        if ($secret !== '') {
            $sigHeader = $request->getHeaderLine('stripe-signature');
            if ($sigHeader === '') {
                $sigHeader = $request->getHeaderLine('Stripe-Signature');
            }

            if ($sigHeader === '' || !$this->verifyStripeSignature($body, $sigHeader, $secret)) {
                $this->logger->warning('[StripeWebhook] Rejected invalid or missing webhook signature.');
                $response = $this->responseFactory->createResponse(400)
                    ->withHeader('Content-Type', 'application/json');
                $response->getBody()->write(json_encode([
                    'status' => 'error',
                    'message' => 'Invalid webhook signature',
                ]));
                return $response;
            }
        } elseif (Environment::isProd()) {
            // Production safeguard: unverified webhooks are strictly prohibited in production
            $this->logger->error('[StripeWebhook] Cannot process webhook: STRIPE_LIVE_WEBHOOK_SECRET is unconfigured.');
            $response = $this->responseFactory->createResponse(500)
                ->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Stripe webhook secret is not configured in production',
            ]));
            return $response;
        }

        $payload = json_decode($body, true);

        if (!is_array($payload)) {
            $response = $this->responseFactory->createResponse(400)
                ->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => 'Empty or non-JSON webhook ping']));
            return $response;
        }

        $eventType = $payload['type'] ?? 'unknown';

        // Replicate legacy switch behavior
        switch ($eventType) {
            case 'invoice.paid':
            case 'invoice.payment_succeeded':
            case 'invoice.payment_failed':
            case 'customer.subscription.updated':
            case 'customer.subscription.deleted':
                $this->logger->info(sprintf('[StripeWebhook] Successfully processed verified event "%s"', $eventType));
                break;
            default:
                $this->logger->info(sprintf('[StripeWebhook] Received unhandled event type "%s"', $eventType));
                break;
        }

        $response = $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'event' => $eventType,
            'received_at' => time(),
        ]));

        return $response;
    }

    /**
     * Validates Stripe webhook signature header using native HMAC-SHA256 (tolerance = 300s).
     * Replicates \Stripe\Webhook::constructEvent() signature verification natively.
     */
    private function verifyStripeSignature(string $payload, string $sigHeader, string $secret, int $tolerance = 300): bool
    {
        $items = explode(',', $sigHeader);
        $timestamp = -1;
        $signatures = [];

        foreach ($items as $item) {
            $parts = explode('=', trim($item), 2);
            if (count($parts) === 2) {
                $prefix = trim($parts[0]);
                $val = trim($parts[1]);
                if ($prefix === 't') {
                    $timestamp = (int) $val;
                } elseif ($prefix === 'v1') {
                    $signatures[] = $val;
                }
            }
        }

        if ($timestamp <= 0 || empty($signatures)) {
            return false;
        }

        // Replay attack protection (within 300 seconds)
        if ($tolerance > 0 && abs(time() - $timestamp) > $tolerance) {
            return false;
        }

        $signedPayload = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expectedSignature, $signature)) {
                return true;
            }
        }

        return false;
    }
}
