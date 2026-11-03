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
 * Restricts to POST method, verifies RBAC, enforces tenant isolation, cascades reply deletion.
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
            return $this->redirect('/sys-yii3/login');
        }

        $flash = new Flash($this->session);

        if ($request->getMethod() !== 'POST') {
            $flash->set('error', 'Method not allowed for review deletion.');
            return $this->redirect('/sys-yii3/reviews');
        }

        $currentUserId = $context->getUserId();
        $currentUser = $this->userRepo->findById($currentUserId, $context);
        if ($currentUser === null) {
            return $this->redirect('/sys-yii3/login');
        }

        // Authorization check: Admin, account admin, or review permission
        if (
            !$currentUser->isAdmin() &&
            !$currentUser->isAccountAdmin() &&
            !$this->accessDecision->getRbac()->can($currentUser, '/reviews/*')
        ) {
            $flash->set('error', 'Forbidden: You do not have permission to delete reviews.');
            return $this->redirect('/sys-yii3/reviews');
        }

        $queryParams = $request->getQueryParams();
        $id = isset($queryParams['id']) ? (int) $queryParams['id'] : 0;
        if ($id <= 0) {
            $body = (array) ($request->getParsedBody() ?? []);
            $id = isset($body['id']) ? (int) $body['id'] : 0;
        }

        if ($id <= 0) {
            $flash->set('error', 'Invalid review ID.');
            return $this->redirect('/sys-yii3/reviews');
        }

        $review = $this->reviewRepo->findById($id, $context);
        if ($review === null) {
            $flash->set('error', 'The requested review does not exist or you do not have permission to delete it.');
            return $this->redirect('/sys-yii3/reviews');
        }

        $success = $this->reviewRepo->delete($id, $context);
        if ($success) {
            $flash->set('success', 'Review deleted successfully.');
        } else {
            $flash->set('error', 'Unable to delete review (database is currently in read-only mode).');
        }

        return $this->redirect('/sys-yii3/reviews');
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
