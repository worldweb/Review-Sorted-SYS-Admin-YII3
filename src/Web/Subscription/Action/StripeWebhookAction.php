<?php

declare(strict_types=1);

namespace App\Web\Subscription\Action;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\Subscription\SubscriptionRepositoryInterface;
use App\Environment;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * StripeWebhookAction.
 * Receives incoming asynchronous webhook events from Stripe for invoice payment,
 * subscription renewal, cancellation, and payment failure.
 * Cryptographically validates webhook signatures using native HMAC-SHA256 matching Stripe specification.
 * Synchronizes local subscriptions, account invoices, and account entitlements idempotently.
 */
final class StripeWebhookAction
{
    private LoggerInterface $logger;

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private SubscriptionRepositoryInterface $subscriptionRepo,
        private AccountRepositoryInterface $accountRepo,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $body = (string) $request->getBody();
        if ($body === '') {
            try {
                $request->getBody()->rewind();
                $body = (string) $request->getBody();
            } catch (Throwable) {
            }
        }
        if ($body === '') {
            $body = (string) file_get_contents('php://input');
        }

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

        $eventType = (string) ($payload['type'] ?? 'unknown');
        $dataObject = $payload['data']['object'] ?? [];

        if (!is_array($dataObject)) {
            $response = $this->responseFactory->createResponse(200)
                ->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode(['status' => 'success', 'event' => $eventType]));
            return $response;
        }

        try {
            switch ($eventType) {
                case 'customer.subscription.created':
                    $this->handleSubscriptionCreated($dataObject);
                    break;

                case 'customer.subscription.deleted':
                    $this->handleSubscriptionDeleted($dataObject);
                    break;

                case 'customer.subscription.updated':
                    $this->handleSubscriptionUpdated($dataObject);
                    break;

                case 'invoice.paid':
                case 'invoice.payment_succeeded':
                    $this->handleInvoicePaid($dataObject);
                    break;

                case 'invoice.payment_failed':
                    $this->handleInvoicePaymentFailed($dataObject);
                    break;

                default:
                    $this->logger->info(sprintf('[StripeWebhook] Received unhandled event type "%s"', $eventType));
                    break;
            }
        } catch (Throwable $e) {
            $this->logger->error(sprintf('[StripeWebhook] Error processing event %s: %s', $eventType, $e->getMessage()));
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
     * Handles customer.subscription.created event.
     * Defer entitlement activation if subscription status is incomplete (payment not yet confirmed).
     *
     * @param array<string, mixed> $subscription
     */
    private function handleSubscriptionCreated(array $subscription): void
    {
        $subId = (string) ($subscription['id'] ?? '');
        $status = (string) ($subscription['status'] ?? '');
        if ($subId === '') {
            return;
        }

        $this->logger->info(sprintf('[StripeWebhook] customer.subscription.created received for ID: %s (Status: %s)', $subId, $status));

        // If status is incomplete or unpaid, payment has not been confirmed: DO NOT activate entitlements
        if (in_array($status, ['incomplete', 'incomplete_expired', 'unpaid'], true)) {
            $this->logger->info(sprintf('[StripeWebhook] Subscription %s is in state "%s" - deferring activation until payment confirmation.', $subId, $status));
            return;
        }

        // If subscription is already active or trialing, update/sync local subscription
        $subRecord = $this->subscriptionRepo->findSubscriptionByStripeId($subId);
        if ($subRecord !== null) {
            $this->handleSubscriptionUpdated($subscription);
        }
    }

    /**
     * Handles customer.subscription.deleted event.
     *
     * @param array<string, mixed> $subscription
     */
    private function handleSubscriptionDeleted(array $subscription): void
    {
        $subId = (string) ($subscription['id'] ?? '');
        if ($subId === '') {
            return;
        }

        $subRecord = $this->subscriptionRepo->findSubscriptionByStripeId($subId);
        if ($subRecord === null) {
            $this->logger->warning("[StripeWebhook] Subscription {$subId} not found in database for deletion");
            return;
        }

        $canceledAt = null;
        if (!empty($subscription['canceled_at'])) {
            $canceledAt = date('Y-m-d H:i:s', (int) $subscription['canceled_at']);
        } elseif (!empty($subscription['current_period_end'])) {
            $canceledAt = date('Y-m-d H:i:s', (int) $subscription['current_period_end']);
        }

        $this->subscriptionRepo->cancelSubscription($subRecord->getId(), $canceledAt);
        $this->logger->info("[StripeWebhook] Subscription {$subId} canceled with expire_at: " . ($canceledAt ?? 'now'));
    }

    /**
     * Handles customer.subscription.updated event.
     * Includes out-of-order protection: prevents resurrecting canceled/expired subscriptions.
     *
     * @param array<string, mixed> $subscription
     */
    private function handleSubscriptionUpdated(array $subscription): void
    {
        $subId = (string) ($subscription['id'] ?? '');
        if ($subId === '') {
            return;
        }

        $subRecord = $this->subscriptionRepo->findSubscriptionByStripeId($subId);
        if ($subRecord === null) {
            $this->logger->warning("[StripeWebhook] Subscription {$subId} not found in database for update");
            return;
        }

        $incomingStripeStatus = (string) ($subscription['status'] ?? 'active');
        $isLocalInactive = $subRecord->getStatus() === 'inactive';
        $localExpireAt = $subRecord->getExpireAt();

        // Out-of-order protection: if local subscription is canceled/inactive with an expiration timestamp,
        // prevent older webhook deliveries or stale updates from resurrecting it back to active.
        if ($isLocalInactive && !empty($localExpireAt)) {
            $localExpireTime = strtotime($localExpireAt);
            $incomingPeriodEnd = !empty($subscription['current_period_end']) ? (int) $subscription['current_period_end'] : 0;

            if ($incomingPeriodEnd <= $localExpireTime || in_array($incomingStripeStatus, ['canceled', 'unpaid', 'incomplete_expired'], true)) {
                $this->logger->warning(sprintf(
                    '[StripeWebhook] Out-of-order update ignored for subscription %s: local subscription is inactive (expire_at: %s), incoming period end is %s.',
                    $subId,
                    $localExpireAt,
                    date('Y-m-d H:i:s', $incomingPeriodEnd)
                ));
                return;
            }
        }

        $planObj = $subscription['plan'] ?? ($subscription['items']['data'][0]['plan'] ?? null);
        $planPriceId = $planObj ? (string) ($planObj['id'] ?? '') : null;
        $amountPaid = $planObj ? ((float) ($planObj['amount'] ?? 0)) / 100 : null;
        $currency = $planObj ? (string) ($planObj['currency'] ?? 'usd') : 'usd';
        $interval = $planObj ? (string) ($planObj['interval'] ?? 'month') : 'month';
        $intervalCount = $planObj ? (string) ($planObj['interval_count'] ?? '1') : '1';

        $matchedPlanId = $subRecord->getPlanId();
        if ($amountPaid !== null && $amountPaid > 0) {
            $matchedPlan = $this->subscriptionRepo->findPlanByPrice($amountPaid);
            if ($matchedPlan !== null) {
                $matchedPlanId = (string) $matchedPlan->getId();
            }
        }

        $periodStart = !empty($subscription['current_period_start'])
            ? date('Y-m-d H:i:s', (int) $subscription['current_period_start'])
            : null;
        $periodEnd = !empty($subscription['current_period_end'])
            ? date('Y-m-d H:i:s', (int) $subscription['current_period_end'])
            : null;

        $isActiveStatus = in_array($incomingStripeStatus, ['active', 'trialing'], true);

        $updateData = [
            'stripe_plan_id' => $planPriceId,
            'plan_id' => $matchedPlanId,
            'paid_amount' => $amountPaid !== null ? (string) $amountPaid : $subRecord->getPaidAmount(),
            'paid_amount_currency' => $currency,
            'plan_interval' => $interval,
            'plan_interval_count' => $intervalCount,
            'stripe_status' => $incomingStripeStatus,
            'status' => $isActiveStatus ? 'active' : 'inactive',
        ];

        if ($periodStart !== null) {
            $updateData['plan_period_start'] = $periodStart;
        }
        if ($periodEnd !== null) {
            $updateData['plan_period_end'] = $periodEnd;
        }

        $this->subscriptionRepo->updateSubscription($subRecord->getId(), $updateData);
        $this->logger->info("[StripeWebhook] Subscription {$subId} updated successfully (Status: {$incomingStripeStatus})");
    }

    /**
     * Handles invoice.paid / invoice.payment_succeeded event.
     *
     * @param array<string, mixed> $invoice
     */
    private function handleInvoicePaid(array $invoice): void
    {
        $invoice['status'] = 'paid';
        $success = $this->subscriptionRepo->syncAccountInvoice($invoice);
        if ($success) {
            $this->logger->info('[StripeWebhook] Synced paid invoice ' . ($invoice['id'] ?? 'unknown'));
        } else {
            $this->logger->warning('[StripeWebhook] Could not map paid invoice to account: ' . ($invoice['id'] ?? 'unknown'));
        }
    }

    /**
     * Handles invoice.payment_failed event.
     *
     * @param array<string, mixed> $invoice
     */
    private function handleInvoicePaymentFailed(array $invoice): void
    {
        $invoice['status'] = 'failed';
        $success = $this->subscriptionRepo->syncAccountInvoice($invoice);
        if ($success) {
            $this->logger->info('[StripeWebhook] Recorded failed invoice ' . ($invoice['id'] ?? 'unknown'));
        } else {
            $this->logger->warning('[StripeWebhook] Could not map failed invoice to account: ' . ($invoice['id'] ?? 'unknown'));
        }
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
