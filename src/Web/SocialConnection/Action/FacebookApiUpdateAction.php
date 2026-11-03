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

final class FacebookApiUpdateAction
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
            return $this->redirect('/sys-yii3/settings/facebook');
        }

        $id = (int) ($request->getQueryParams()['id'] ?? 0);
        $api = $this->connectionRepo->findApiById($id);
        if ($api === null || $api->getServiceId() !== Api::SERVICE_FACEBOOK) {
            return $this->redirect('/sys-yii3/settings/facebook-api');
        }

        $flash = new Flash($this->session);
        $errors = [];

        if ($request->getMethod() === 'POST') {
            $body = (array) ($request->getParsedBody() ?? []);
            $label = trim((string) ($body['label'] ?? ''));
            $clientId = trim((string) ($body['client_id'] ?? $api->getClientId()));
            $apiKey = trim((string) ($body['api_key'] ?? $api->getApiKey()));

            if ($label === '') {
                $errors['label'] = 'Application Name is required.';
            }

            if (empty($errors)) {
                $saved = $this->connectionRepo->saveApi([
                    'service_id' => Api::SERVICE_FACEBOOK,
                    'label' => $label,
                    'client_id' => $clientId,
                    'api_key' => $apiKey,
                ], $id);

                if ($saved !== null) {
                    $flash->add('success', 'Facebook Application successfully updated.');
                    return $this->redirect('/sys-yii3/settings/facebook-api/view?id=' . $id);
                } else {
                    $flash->add('error', 'Failed to update Facebook Application.');
                }
            }
        }

        return $this->viewRenderer
            ->withViewPath('@src/Web/SocialConnection/views')
            ->render('facebook-api/update', [
                'api' => $api,
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
