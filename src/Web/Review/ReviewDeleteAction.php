<?php

declare(strict_types=1);

namespace App\Web\Review;

use App\Domain\Review\ReviewRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\Authorization\AccessDecisionManager;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;

/**
 * ReviewDeleteAction.
 * Handles single review deletion matching legacy ReviewsController::actionDelete.
 * Supports POST and GET methods, verifies RBAC, enforces tenant isolation, cascades reply deletion.
 */
final class ReviewDeleteAction
{
    public function __construct(
        private ReviewRepositoryInterface $reviewRepo,
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private AccessDecisionManager $accessDecision,
        private SessionInterface $session,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $this->tenantContextProvider->get();
        if ($context->isGuest()) {
            return $this->redirect(\App\Environment::url('/login'));
        }

        $flash = new Flash($this->session);

        $method = $request->getMethod();
        if ($method !== 'POST' && $method !== 'GET') {
            $flash->set('error', 'Method not allowed for review deletion.');
            return $this->redirect(\App\Environment::url('/reviews'));
        }

        $currentUserId = $context->getUserId();
        $currentUser = $this->userRepo->findById($currentUserId, $context);
        if ($currentUser === null) {
            return $this->redirect(\App\Environment::url('/login'));
        }

        // Authorization check: Admin, account admin, or review permission
        if (
            !$currentUser->isAdmin() &&
            !$currentUser->isAccountAdmin() &&
            !$this->accessDecision->getRbac()->can($currentUser, '/reviews/*')
        ) {
            $flash->set('error', 'Forbidden: You do not have permission to delete reviews.');
            return $this->redirect(\App\Environment::url('/reviews'));
        }

        $queryParams = $request->getQueryParams();
        $body = (array) ($request->getParsedBody() ?? []);
        $id = isset($body['id']) ? (int) $body['id'] : (isset($queryParams['id']) ? (int) $queryParams['id'] : 0);

        if ($id <= 0) {
            $flash->set('error', 'Invalid review ID.');
            return $this->redirect(\App\Environment::url('/reviews'));
        }

        $success = $this->reviewRepo->delete($id, $context);
        if ($success) {
            $flash->set('success', 'Review deleted successfully.');
        } else {
            $flash->set('error', 'The requested review does not exist or you do not have permission to delete it.');
        }

        return $this->redirect(\App\Environment::url('/reviews'));
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
