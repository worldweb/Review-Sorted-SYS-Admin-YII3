<?php

declare(strict_types=1);

namespace App\Web\Api\Action;

use App\Domain\Account\AccountRepositoryInterface;
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
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $this->tenantContextProvider->get();
        if ($context->isGuest()) {
            $resp = $this->responseFactory->createResponse(401)->withHeader('Content-Type', 'application/json');
            $resp->getBody()->write(json_encode(['error' => 'Unauthorized', 'message' => 'Authentication required']));
            return $resp;
        }

        $response = $this->responseFactory->createResponse(200)->withHeader('Content-Type', 'application/json');

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
