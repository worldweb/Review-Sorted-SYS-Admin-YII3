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
}
