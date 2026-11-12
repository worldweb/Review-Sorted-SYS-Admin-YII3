<?php

declare(strict_types=1);

namespace App\Domain\Subscription;

use App\Security\TenantContextInterface;

/**
 * Interface for Subscription and Plan Repository.
 */
interface SubscriptionRepositoryInterface
{
    /**
     * Find all pricing plans.
     *
     * @return array<int, Plan>
     */
    public function findAllPlans(): array;

    /**
     * Find a single plan by ID.
     */
    public function findPlanById(int $id): ?Plan;

    /**
     * Create a new plan.
     *
     * @param array<string, mixed> $data
     */
    public function createPlan(array $data): ?int;

    /**
     * Update an existing plan.
     *
     * @param array<string, mixed> $data
     */
    public function updatePlan(int $id, array $data): bool;

    /**
     * Delete a plan by ID.
     */
    public function deletePlan(int $id): bool;

    /**
     * Find all customer subscriptions.
     *
     * @return array<int, Subscription>
     */
    public function findAllSubscriptions(?TenantContextInterface $context = null, int $limit = 50, int $offset = 0): array;

    /**
     * Count subscriptions.
     */
    public function countSubscriptions(?TenantContextInterface $context = null): int;

    /**
     * Find all invoices for an account.
     *
     * @return array<int, AccountInvoice>
     */
    public function findInvoicesForAccount(int $accountId, int $limit = 20): array;

    /**
     * Find active paid subscription for a user.
     */
    public function findActiveSubscriptionByUserId(int $userId): ?Subscription;

    /**
     * Find subscription by Stripe subscription ID.
     */
    public function findSubscriptionByStripeId(string $stripeSubscriptionId): ?Subscription;

    /**
     * Find latest subscription for a user regardless of status.
     */
    public function findLatestSubscriptionByUserId(int $userId): ?Subscription;

    /**
     * Insert a new subscription record.
     *
     * @param array<string, mixed> $data
     */
    public function insertSubscription(array $data): int;

    /**
     * Update an existing subscription record.
     *
     * @param array<string, mixed> $data
     */
    public function updateSubscription(int $id, array $data): bool;

    /**
     * Mark subscription canceled or set expire_at.
     */
    public function cancelSubscription(int $id, ?string $expireAt = null): bool;

    /**
     * Idempotently sync a Stripe invoice into account_invoice table.
     *
     * @param array<string, mixed> $data
     */
    public function syncAccountInvoice(array $data, ?int $accountId = null, ?int $userId = null): bool;

    /**
     * Find invoice by Stripe invoice ID.
     */
    public function findInvoiceByStripeInvoiceId(string $stripeInvoiceId): ?AccountInvoice;

    /**
     * Find plan by exact price amount.
     */
    public function findPlanByPrice(float $price): ?Plan;

    /**
     * Find all distinct user IDs that have subscriptions.
     *
     * @return array<int>
     */
    public function findDistinctSubscribedUserIds(): array;
}
