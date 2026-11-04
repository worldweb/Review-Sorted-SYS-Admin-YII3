<?php

declare(strict_types=1);

namespace App\Web\SocialConnection\Action;

use App\Domain\SocialConnection\Api;
use App\Domain\SocialConnection\SocialConnectionRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class FacebookApiCreateAction
{
    public function __construct(
        private SocialConnectionRepositoryInterface $connectionRepo,
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
            return $this->redirect(\App\Environment::url('/settings/facebook'));
        }

        $flash = new Flash($this->session);
        $errors = [];

        if ($request->getMethod() === 'POST') {
            $body = (array) ($request->getParsedBody() ?? []);
            $label = trim((string) ($body['label'] ?? ''));
            $clientId = trim((string) ($body['client_id'] ?? ''));
            $apiKey = trim((string) ($body['api_key'] ?? ''));

            if ($label === '') {
                $errors['label'] = 'Application Name is required.';
            }
            if ($clientId === '') {
                $errors['client_id'] = 'Facebook App ID is required.';
            }
            if ($apiKey === '') {
                $errors['api_key'] = 'Facebook App Secret is required.';
            }

            if (empty($errors)) {
                $id = $this->connectionRepo->saveApi([
                    'service_id' => Api::SERVICE_FACEBOOK,
                    'label' => $label,
                    'client_id' => $clientId,
                    'api_key' => $apiKey,
                ]);

                if ($id !== null) {
                    $flash->add('success', 'Facebook Application successfully added.');
                    return $this->redirect(\App\Environment::url('/settings/facebook-api'));
                } else {
                    $flash->add('error', 'Failed to save Facebook Application.');
                }
            }
        }

        return $this->viewRenderer
            ->withViewPath('@src/Web/SocialConnection/views')
            ->render('facebook-api/create', [
                'currentUser' => $currentUser,
                'flash' => $flash,
                'errors' => $errors,
                'csrfToken' => $this->csrfToken?->getValue() ?? '',
            ]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
