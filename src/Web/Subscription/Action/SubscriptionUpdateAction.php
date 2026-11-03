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

final class SubscriptionUpdateAction
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
            return $this->redirect('/sys-yii3/login');
        }

        $currentUser = $this->userRepo->findById($context->getUserId(), $context);
        if ($currentUser === null || !$currentUser->isAdmin()) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: Only platform administrators can manage pricing plans.');
            return $response;
        }

        $id = (int) ($request->getQueryParams()['id'] ?? 0);
        $plan = $this->subRepo->findPlanById($id);
        if ($plan === null) {
            (new Flash($this->session))->set('error', 'Plan not found.');
            return $this->redirect('/sys-yii3/subscription');
        }

        $flash = new Flash($this->session);

        if ($request->getMethod() === 'POST') {
            $body = (array) $request->getParsedBody();
            $name = trim((string) ($body['plan_name'] ?? ''));
            $price = trim(str_replace('$', '', (string) ($body['plan_price'] ?? '0')));
            $limit = trim((string) ($body['plan_limit'] ?? ''));
            $features = trim((string) ($body['plan_features'] ?? ''));
            $status = (string) ($body['status'] ?? '1');

            $updated = $this->subRepo->updatePlan($id, [
                'plan_name' => $name,
                'plan_price' => $price,
                'plan_limit' => $limit,
                'plan_features' => $features,
                'status' => $status,
            ]);

            if ($updated) {
                $flash->set('success', 'Plan updated successfully.');
                return $this->redirect('/sys-yii3/subscription');
            }

            $flash->set('error', 'Notice: Database is in read-only mode (rs_readonly). In live production, changes will be saved.');
        }

        return $this->viewRenderer
            ->withViewPath('@src/Web/Subscription/views')
            ->render('update', [
                'model' => $plan,
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
