<?php

declare(strict_types=1);

namespace App\Domain\WhatsApp;

use App\Security\TenantContextInterface;

/**
 * Repository interface for WhatsApp domain operations.
 */
interface WhatsAppRepositoryInterface
{
    /**
     * Find a WhatsApp template by ID.
     */
    public function findTemplateById(int $id, ?TenantContextInterface $context = null): ?WhatsAppTemplate;

    /**
     * Find a WhatsApp template by slug.
     */
    public function findTemplateBySlug(string $slug, ?int $accountId = null): ?WhatsAppTemplate;

    /**
     * List all WhatsApp templates for the given account / tenant context.
     *
     * @return array<int, WhatsAppTemplate>
     */
    public function findTemplatesByAccount(?TenantContextInterface $context = null, ?int $accountId = null): array;

    /**
     * List all WhatsApp templates accessible under the given context.
     *
     * @return array<int, WhatsAppTemplate>
     */
    public function findAllTemplates(?TenantContextInterface $context = null): array;

    /**
     * Find an individual WhatsApp contact by ID.
     */
    public function findContactById(int $id, ?TenantContextInterface $context = null): ?WhatsAppAddress;

    /**
     * List WhatsApp contacts for an account with pagination.
     *
     * @return array<int, WhatsAppAddress>
     */
    public function findContactsByAccount(
        ?TenantContextInterface $context = null,
        ?int $accountId = null,
        int $page = 1,
        int $pageSize = 50,
    ): array;

    /**
     * Count total WhatsApp contacts for an account.
     */
    public function countContactsByAccount(?TenantContextInterface $context = null, ?int $accountId = null): int;

    /**
     * Find a WhatsApp queue record by ID.
     */
    public function findQueueById(int $id, ?TenantContextInterface $context = null): ?WhatsAppQueue;

    /**
     * List outbound WhatsApp message history for an account with pagination.
     *
     * @return array<int, WhatsAppQueue>
     */
    public function findHistoryByAccount(
        ?TenantContextInterface $context = null,
        ?int $accountId = null,
        int $page = 1,
        int $pageSize = 20,
    ): array;

    /**
     * Count total WhatsApp history records for an account.
     */
    public function countHistoryByAccount(?TenantContextInterface $context = null, ?int $accountId = null): int;

    /**
     * Fetch pending jobs ready to be sent across all accounts (for background worker).
     *
     * @return array<int, WhatsAppQueue>
     */
    public function findPendingQueue(int $batchSize = 100): array;

    /**
     * Check if an account is accessible under context.
     */
    public function checkAccountAccess(int $targetAccountId, TenantContextInterface $context): bool;

    // ==========================================
    // Prepared persistence methods for write activation
    // ==========================================

    public function saveTemplate(WhatsAppTemplate $template): int;

    public function deleteTemplate(int $id): bool;

    public function saveContact(WhatsAppAddress $contact): int;

    /**
     * @param array<int, array{name: string, phone: string}> $rows
     */
    public function saveContactsBatch(array $rows, int $accountId): int;

    public function deleteContact(int $id): bool;

    /**
     * @param int[] $ids
     */
    public function deleteContactsBatch(array $ids, int $accountId): int;

    public function enqueueMessage(WhatsAppQueue $message): int;

    public function claimQueueJob(int $id, string $lockTime): bool;

    public function markQueueSent(int $id, ?string $providerMessageId = null): bool;

    public function markQueueFailed(int $id): bool;

    public function recoverStaleQueueJobs(int $timeoutMinutes = 15): int;
}
