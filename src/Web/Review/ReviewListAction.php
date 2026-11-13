<?php

declare(strict_types=1);

namespace App\Web\Review;

use App\Domain\Review\ReviewRepositoryInterface;
use App\Domain\Review\ReviewSearchFilter;
use App\Domain\Setting\SettingRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\Authorization\AccessDecisionManager;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * ReviewListAction.
 * Renders the Reviews & Mentions listing grid with KPIs and filters.
 * Replicates frontend\controllers\ReviewsController::actionIndex in Yii 3.
 */
final class ReviewListAction
{
    public function __construct(
        private ReviewRepositoryInterface $reviewRepo,
        private SettingRepositoryInterface $settingRepo,
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private AccessDecisionManager $accessDecision,
        private SessionInterface $session,
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private ?CsrfTokenInterface $csrfToken = null,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $this->tenantContextProvider->get();
        if ($context->isGuest()) {
            return $this->redirect(\App\Environment::url('/login'));
        }

        $flash = new Flash($this->session);

        $currentUserId = $context->getUserId();
        $currentUser = $this->userRepo->findById($currentUserId, $context);
        if ($currentUser === null) {
            return $this->redirect(\App\Environment::url('/login'));
        }

        // Check RBAC permission for reviews
        if (
            !$currentUser->isAdmin() &&
            !$this->accessDecision->getRbac()->can($currentUser, '/reviews/*')
        ) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: You are not authorized to view reviews.');
            return $response;
        }

        $defaultPageSize = $this->settingRepo->getInt('review_default_page_size', 20);
        $autoRefreshInterval = $this->settingRepo->getInt('review_auto_refresh_interval', 10);

        $queryParams = $request->getQueryParams();
        $filter = ReviewSearchFilter::fromQueryParams($queryParams, $defaultPageSize);

        $searchResult = $this->reviewRepo->search($filter, $context);
        $reviews = $searchResult['reviews'];
        $total = $searchResult['total'];

        $metrics = $this->reviewRepo->getMetrics($context);
        $serviceConfigOptions = $this->reviewRepo->getServiceConfigOptions($context);
        $serviceOptions = $this->reviewRepo->getServiceOptions();

        $rawQuery = $request->getUri()->getQuery();
        $query64 = !empty($rawQuery) ? base64_encode($rawQuery) : '';

        // Also persist raw query to session
        if (!empty($query64)) {
            $this->session->set('reviews_q', $query64);
        }

        return $this->viewRenderer
            ->render(__DIR__ . '/views/index', [
                'reviews' => $reviews,
                'total' => $total,
                'metrics' => $metrics,
                'filter' => $filter,
                'serviceConfigOptions' => $serviceConfigOptions,
                'serviceOptions' => $serviceOptions,
                'autoRefreshInterval' => $autoRefreshInterval,
                'currentUser' => $currentUser,
                'context' => $context,
                'flash' => $flash,
                'query64' => $query64,
                'queryParams' => $queryParams,
                'csrfToken' => $this->csrfToken?->getValue(),
            ]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
