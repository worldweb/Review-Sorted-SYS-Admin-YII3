<?php

declare(strict_types=1);

namespace App\Web\Subscription\Action;

use App\Domain\Subscription\SubscriptionRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class SubscriptionCreateAction
{
    public function __construct(
        private SubscriptionRepositoryInterface $subRepo,
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
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

        $currentUser = $this->userRepo->findById($context->getUserId(), $context);
        if ($currentUser === null || !$currentUser->isAdmin()) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: Only platform administrators can manage pricing plans.');
            return $response;
        }

        $flash = new Flash($this->session);

        if ($request->getMethod() === 'POST') {
            $body = (array) $request->getParsedBody();
            $name = trim((string) ($body['plan_name'] ?? ''));
            $price = trim(str_replace('$', '', (string) ($body['plan_price'] ?? '0')));
            $limit = trim((string) ($body['plan_limit'] ?? ''));
            $features = trim((string) ($body['plan_features'] ?? ''));
            $status = (string) ($body['status'] ?? '1');

            if (empty($name) || empty($price)) {
                $flash->set('error', 'Plan name and price are required.');
            } else {
                $id = $this->subRepo->createPlan([
                    'plan_name' => $name,
                    'plan_price' => $price,
                    'plan_limit' => $limit,
                    'plan_features' => $features,
                    'status' => $status,
                ]);

                if ($id !== null) {
                    $flash->set('success', 'Plan created successfully.');
                    return $this->redirect(\App\Environment::url('/subscription'));
                }

                $flash->set('error', 'Failed to create plan.');
            }
        }

        return $this->viewRenderer
            ->withViewPath('@src/Web/Subscription/views')
            ->render('create', [
                'currentUser' => $currentUser,
                'flash' => $flash,
                'csrfToken' => $this->csrfToken?->getValue() ?? '',
            ]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
