<?php

declare(strict_types=1);

namespace App\Web\SocialConnection\Action;

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

final class FacebookUpdateAction
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

        $id = (int) ($request->getQueryParams()['id'] ?? $request->getAttribute('id') ?? (((array) $request->getParsedBody())['id'] ?? 0));
        $config = $this->connectionRepo->findById($id, $context);
        if ($config === null || !$config->isFacebook()) {
            (new Flash($this->session))->set('error', 'Facebook connection not found.');
            return $this->redirect(\App\Environment::url('/settings/facebook'));
        }

        $flash = new Flash($this->session);

        if ($request->getMethod() === 'POST') {
            $parsedBody = (array) $request->getParsedBody();
            $label = trim((string) ($parsedBody['label'] ?? $config->getLabel()));
            $emailLowReview = trim((string) ($parsedBody['email_low_review'] ?? ($config->getEmailLowReview() ?? '')));

            $updated = $this->connectionRepo->updateConfig($id, [
                'label' => $label,
                'email_low_review' => $emailLowReview,
            ], $context);

            if ($updated) {
                $flash->set('success', 'Facebook connection settings updated successfully.');
                return $this->redirect(\App\Environment::url('/settings/facebook/view?id=') . $id);
            }

            $flash->set('error', 'Failed to update Facebook settings (database is currently read-only).');
        }

        return $this->viewRenderer
            ->withViewPath('@src/Web/SocialConnection/views')
            ->render('facebook/update', [
                'model' => $config,
                'csrfToken' => $this->csrfToken?->getValue() ?? '',
                'flash' => $flash,
            ]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
