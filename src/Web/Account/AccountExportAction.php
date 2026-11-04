<?php

declare(strict_types=1);

namespace App\Web\Account;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\Account\AccountSearchFilter;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * AccountExportAction.
 * Exports tenant accounts to CSV for Platform Administrators.
 */
final class AccountExportAction
{
    public function __construct(
        private AccountRepositoryInterface $accountRepo,
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $this->tenantContextProvider->get();
        if ($context->isGuest()) {
            return $this->responseFactory->createResponse(302)->withHeader('Location', \App\Environment::url('/login'));
        }

        $currentUser = $this->userRepo->findById($context->getUserId(), $context);
        if ($currentUser === null || !$currentUser->isAdmin()) {
            $res = $this->responseFactory->createResponse(403);
            $res->getBody()->write('Forbidden: Only platform administrators can export workspace data.');
            return $res;
        }

        $searchResult = $this->accountRepo->search(new AccountSearchFilter(pageSize: 10000), $context);
        $accounts = $searchResult['accounts'];

        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, ['ID', 'Business Name', 'Primary Contact', 'Email', 'Status', 'Max Seats', 'Created At']);

        foreach ($accounts as $acc) {
            $contactName = trim(($acc->getFirstName() ?? '') . ' ' . ($acc->getLastName() ?? ''));
            fputcsv($fp, [
                $acc->getId(),
                $acc->getBusinessName() ?? '',
                $contactName,
                $acc->getEmail() ?? '',
                $acc->getStatusName(),
                $acc->getMaxUsers(),
                $acc->getCreatedAtStr(),
            ]);
        }

        rewind($fp);
        $csvContent = stream_get_contents($fp);
        fclose($fp);

        $response = $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="workspaces_export_' . date('Y-m-d') . '.csv"');

        $response->getBody()->write($csvContent);
        return $response;
    }
}
