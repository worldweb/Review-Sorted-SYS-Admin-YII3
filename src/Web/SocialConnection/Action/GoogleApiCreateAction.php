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

final class GoogleApiCreateAction
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
            return $this->redirect('/sys-yii3/login');
        }

        $currentUser = $this->userRepo->findById($context->getUserId(), $context);
        if ($currentUser === null || !$currentUser->isAdmin()) {
            return $this->redirect('/sys-yii3/settings/google');
        }

        $flash = new Flash($this->session);
        $errors = [];

        if ($request->getMethod() === 'POST') {
            $body = (array) ($request->getParsedBody() ?? []);
            $label = trim((string) ($body['label'] ?? ''));
            $clientJson = trim((string) ($body['client_json'] ?? ''));

            if ($label === '') {
                $errors['label'] = 'Google Project Label is required.';
            }
            if ($clientJson === '') {
                $errors['client_json'] = 'Client Credentials JSON is required.';
            } else {
                $decoded = json_decode($clientJson, true);
                if ($decoded === null) {
                    $errors['client_json'] = 'Invalid JSON format for Client Credentials.';
                }
            }

            if (empty($errors)) {
                $id = $this->connectionRepo->saveApi([
                    'service_id' => Api::SERVICE_GOOGLE,
                    'label' => $label,
                    'client_json' => $clientJson,
                ]);

                if ($id !== null) {
                    $flash->add('success', 'Google Project successfully added.');
                    return $this->redirect('/sys-yii3/settings/google-api');
                } else {
                    $flash->add('error', 'Failed to save Google Project.');
                }
            }
        }

        return $this->viewRenderer
            ->withViewPath('@src/Web/SocialConnection/views')
            ->render('google-api/create', [
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
