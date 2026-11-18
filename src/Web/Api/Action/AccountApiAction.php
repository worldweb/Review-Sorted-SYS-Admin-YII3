<?php

declare(strict_types=1);

namespace App\Web\Api\Action;

use App\Domain\Account\AccountRepositoryInterface;
use App\Security\RateLimiter\FileRateLimiter;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * AccountApiAction.
 * REST API endpoint replicating legacy AccountapiController for tenant accounts.
 */
final class AccountApiAction
{
    public function __construct(
        private AccountRepositoryInterface $accountRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private ResponseFactoryInterface $responseFactory,
        private ?FileRateLimiter $rateLimiter = null,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $limiter = $this->rateLimiter ?? new FileRateLimiter();
        $context = $this->tenantContextProvider->get();

        if ($context->isGuest()) {
            $ip = $limiter->getClientIp($request);
            $guestKey = "api:guest:{$ip}";
            if (!$limiter->check($guestKey, 15, 60)) {
                $retryAfter = $limiter->getRetryAfter($guestKey, 60);
                $resp = $this->responseFactory->createResponse(429)
                    ->withHeader('Content-Type', 'application/json; charset=UTF-8')
                    ->withHeader('Retry-After', (string) $retryAfter);
                $resp->getBody()->write((string) json_encode([
                    'error' => 'Too Many Requests',
                    'message' => 'Rate limit exceeded. Please try again later.',
                ], JSON_UNESCAPED_SLASHES));
                return $resp;
            }

            $resp = $this->responseFactory->createResponse(401)
                ->withHeader('Content-Type', 'application/json; charset=UTF-8');
            $resp->getBody()->write((string) json_encode([
                'error' => 'Unauthorized',
                'message' => 'Authentication required',
            ], JSON_UNESCAPED_SLASHES));
            return $resp;
        }

        // Authenticated client rate limit: 60 requests per 60 seconds
        $userId = $context->getUserId();
        $userKey = "api:user:{$userId}";
        if (!$limiter->check($userKey, 60, 60)) {
            $retryAfter = $limiter->getRetryAfter($userKey, 60);
            $resp = $this->responseFactory->createResponse(429)
                ->withHeader('Content-Type', 'application/json; charset=UTF-8')
                ->withHeader('Retry-After', (string) $retryAfter);
            $resp->getBody()->write((string) json_encode([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Please try again later.',
            ], JSON_UNESCAPED_SLASHES));
            return $resp;
        }

        $response = $this->responseFactory->createResponse(200)->withHeader('Content-Type', 'application/json; charset=UTF-8');

        if ($context->isUnrestricted()) {
            $accounts = $this->accountRepo->findAllActive(100, 0, $context);
        } else {
            $accId = $context->getAccountId();
            $acc = $accId ? $this->accountRepo->findById($accId, $context) : null;
            $accounts = $acc ? [$acc] : [];
        }

        $data = [];
        foreach ($accounts as $a) {
            $data[] = [
                'id' => $a->getId(),
                'business_name' => $a->getBusinessName(),
                'status' => $a->getStatus(),
                'created_at' => $a->getCreatedAt(),
            ];
        }

        $response->getBody()->write(json_encode(['data' => $data]));
        return $response;
    }
}
