<?php

declare(strict_types=1);

namespace App\Security;

use Yiisoft\RequestProvider\RequestProviderInterface;

/**
 * Tenant Context Provider Implementation.
 * Manages the current request's TenantContext within the DI container lifecycle.
 * Ensures strict per-request isolation and prevents tenant context leakage.
 */
final class TenantContextProvider implements TenantContextProviderInterface
{
    private TenantContextInterface $fallbackContext;

    public function __construct(
        private ?RequestProviderInterface $requestProvider = null,
        ?TenantContextInterface $initialContext = null,
    ) {
        $this->fallbackContext = $initialContext ?? TenantContext::guest();
    }

    public function get(): TenantContextInterface
    {
        // 1. Check if the current PSR-7 request carries an explicit request-scoped TenantContext
        if ($this->requestProvider !== null) {
            try {
                $request = $this->requestProvider->get();
                $context = $request->getAttribute('tenantContext');
                if ($context instanceof TenantContextInterface) {
                    return $context;
                }
            } catch (\Throwable) {
                // Request not set (e.g. CLI, queue, or early bootstrap)
            }
        }

        return $this->fallbackContext;
    }

    public function set(TenantContextInterface $context): void
    {
        $this->fallbackContext = $context;
    }

    public function reset(): void
    {
        $this->fallbackContext = TenantContext::guest();
    }
}
