<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Interface for Tenant Context Provider.
 * Allows request-scoped resolution and dynamic binding of TenantContext
 * without relying on global mutable state.
 */
interface TenantContextProviderInterface
{
    /**
     * Get the current active TenantContext.
     */
    public function get(): TenantContextInterface;

    /**
     * Set the active TenantContext for the current request / process lifecycle.
     */
    public function set(TenantContextInterface $context): void;

    /**
     * Reset the active context back to unauthenticated guest.
     */
    public function reset(): void;
}
