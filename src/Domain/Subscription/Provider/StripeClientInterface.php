<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Provider;

/**
 * StripeClientInterface.
 * Contract for interacting with the Stripe REST API.
 */
interface StripeClientInterface
{
    /**
     * Determine whether valid Stripe credentials are configured.
     */
    public function isConfigured(): bool;

    /**
     * Create a Stripe customer.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function createCustomer(array $params): array;

    /**
     * Retrieve a Stripe customer by ID.
     *
     * @return array<string, mixed>|null
     */
    public function retrieveCustomer(string $customerId): ?array;

    /**
     * Create a Stripe pricing plan / price item.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function createPlan(array $params): array;

    /**
     * Create a Stripe subscription with payment_behavior=default_incomplete.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function createSubscription(array $params): array;

    /**
     * Retrieve a Stripe subscription by ID.
     *
     * @return array<string, mixed>|null
     */
    public function retrieveSubscription(string $subscriptionId): ?array;

    /**
     * Update an existing Stripe subscription (e.g. for prorated plan changes).
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function updateSubscription(string $subscriptionId, array $params): array;

    /**
     * Cancel an existing Stripe subscription.
     *
     * @return array<string, mixed>
     */
    public function cancelSubscription(string $subscriptionId): array;

    /**
     * Retrieve a Stripe invoice by ID.
     *
     * @return array<string, mixed>|null
     */
    public function retrieveInvoice(string $invoiceId): ?array;

    /**
     * Retrieve a Stripe PaymentIntent by ID for independent backend verification.
     *
     * @return array<string, mixed>|null
     */
    public function retrievePaymentIntent(string $paymentIntentId): ?array;

    /**
     * Create a Stripe price item (Prices API compatibility).
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function createPrice(array $params): array;
}

