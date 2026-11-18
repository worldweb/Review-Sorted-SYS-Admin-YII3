<?php

declare(strict_types=1);

namespace App\Web\User;

use App\Domain\User\UserRepositoryInterface;
use App\Security\Authorization\AccessDecisionManager;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;

/**
 * UserDeleteAction.
 * Handles user deletion matching Yii 2 UserController::actionDelete.
 */
final class UserDeleteAction
{
    public function __construct(
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private AccessDecisionManager $accessDecision,
        private ResponseFactoryInterface $responseFactory,
        private ?SessionInterface $session = null,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() !== 'POST') {
            return $this->responseFactory->createResponse(405)->withHeader('Allow', 'POST');
        }

        $context = $this->tenantContextProvider->get();
        if ($context->isGuest()) {
            return $this->redirect(\App\Environment::url('/login'));
        }

        $body = (array) ($request->getParsedBody() ?? []);
        $queryParams = $request->getQueryParams();
        $targetId = isset($body['id']) ? (int) $body['id'] : (isset($queryParams['id']) ? (int) $queryParams['id'] : 0);

        $targetUser = $this->userRepo->findById($targetId, $context);
        if ($targetUser === null) {
            $response = $this->responseFactory->createResponse(404);
            $response->getBody()->write('User not found.');
            return $response;
        }

        if ($targetUser->isSuperAdmin()) {
            $response = $this->responseFactory->createResponse(400);
            $response->getBody()->write('Cannot delete the super admin.');
            return $response;
        }

        if (!$this->accessDecision->canDeleteUser($context, $targetUser)) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: You are not authorized to delete this user.');
            return $response;
        }

        try {
            $hasDeps = $this->userRepo->hasDependencies($targetId);
            $this->userRepo->deleteUser($targetId, $context);
            if ($this->session !== null) {
                $msg = $hasDeps
                    ? 'User has existing activity records and was deactivated successfully.'
                    : 'User deleted successfully.';
                (new Flash($this->session))->add('success', $msg);
            }
        } catch (\Throwable $e) {
            $this->logger?->error('User deletion failed: ' . $e->getMessage(), ['exception' => $e]);
            if ($this->session !== null) {
                (new Flash($this->session))->add('error', 'Unable to delete user. Please ensure the user has no dependent records, or disable the user instead.');
            }
            return $this->redirect(\App\Environment::url('/users'));
        }

        return $this->redirect(\App\Environment::url('/users'));
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
