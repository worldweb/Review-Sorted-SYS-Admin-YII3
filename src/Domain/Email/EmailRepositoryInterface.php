<?php

declare(strict_types=1);

namespace App\Domain\Email;

use App\Security\TenantContextInterface;

/**
 * Repository interface for Email domain operations.
 */
interface EmailRepositoryInterface
{
    /**
     * Find an email template by ID respecting tenant boundary.
     */
    public function findTemplateById(int $id, ?TenantContextInterface $context = null): ?EmailTemplate;

    /**
     * Find an email template by slug and account (or system default).
     */
    public function findTemplateBySlug(string $slug, ?int $accountId = null): ?EmailTemplate;

    /**
     * List all email templates for the given account / tenant context.
     *
     * @return array<int, EmailTemplate>
     */
    public function findTemplatesByAccount(?TenantContextInterface $context = null, ?int $accountId = null): array;

    /**
     * Find an individual contact by ID.
     */
    public function findContactById(int $id, ?TenantContextInterface $context = null): ?EmailAddress;

    /**
     * List email contacts for an account with pagination.
     *
     * @return array<int, EmailAddress>
     */
    public function findContactsByAccount(
        ?TenantContextInterface $context = null,
        ?int $accountId = null,
        int $page = 1,
        int $pageSize = 50,
    ): array;

    /**
     * Count total email contacts for an account.
     */
    public function countContactsByAccount(?TenantContextInterface $context = null, ?int $accountId = null): int;

    /**
     * Find an email queue record by ID.
     */
    public function findQueueById(int $id, ?TenantContextInterface $context = null): ?EmailQueue;

    /**
     * List outbound email queue items for an account with pagination.
     *
     * @return array<int, EmailQueue>
     */
    public function findQueueByAccount(
        ?TenantContextInterface $context = null,
        ?int $accountId = null,
        int $page = 1,
        int $pageSize = 20,
    ): array;

    /**
     * Count total queue records for an account.
     */
    public function countQueueByAccount(?TenantContextInterface $context = null, ?int $accountId = null): int;

    /**
     * Fetch pending jobs ready to be sent across all accounts (for background worker).
     *
     * @return array<int, EmailQueue>
     */
    public function findPendingQueue(int $batchSize = 100): array;

    /**
     * Check if a contact or template belongs to an accessible account.
     */
    public function checkAccountAccess(int $targetAccountId, TenantContextInterface $context): bool;

    // ==========================================
    // Prepared persistence methods for write activation
    // ==========================================

    public function saveTemplate(EmailTemplate $template): int;

    public function deleteTemplate(int $id): bool;

    public function saveContact(EmailAddress $contact): int;

    /**
     * @param array<int, array{name: string, email: string, type: string}> $rows
     */
    public function saveContactsBatch(array $rows, int $accountId): int;

    public function deleteContact(int $id): bool;

    /**
     * @param int[] $ids
     */
    public function deleteContactsBatch(array $ids, int $accountId): int;

    public function enqueueEmail(EmailQueue $email): int;

    public function claimQueueJob(int $id, string $lockTime): bool;

    public function markQueueSent(int $id, ?string $externalId = null): bool;

    public function markQueueFailed(int $id): bool;

    public function markQueueDelivered(int $id): bool;

    public function recoverStaleQueueJobs(int $timeoutMinutes = 15): int;
}
