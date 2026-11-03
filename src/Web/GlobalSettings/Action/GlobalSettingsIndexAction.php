<?php

declare(strict_types=1);

namespace App\Web\GlobalSettings\Action;

use App\Domain\Setting\SettingRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class GlobalSettingsIndexAction
{
    public function __construct(
        private SettingRepositoryInterface $settingRepo,
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
            $response->getBody()->write('Forbidden: Only platform administrators can access Global Settings.');
            return $response;
        }

        $flash = new Flash($this->session);
        $queryParams = $request->getQueryParams();
        $section = (string) ($queryParams['section'] ?? 'general');
        $allowedSections = ['general', 'security', 'reviews', 'media', 'subscriptions', 'queue', 'roles', 'database_migration'];
        if (!in_array($section, $allowedSections, true)) {
            $section = 'general';
        }

        if ($request->getMethod() === 'POST') {
            $post = (array) $request->getParsedBody();
            $settingsData = (array) ($post['settings'] ?? []);

            $allSuccess = true;
            foreach ($settingsData as $key => $val) {
                if (!$this->settingRepo->set((string) $key, (string) $val)) {
                    $allSuccess = false;
                }
            }

            if ($allSuccess && !empty($settingsData)) {
                $flash->set('success', 'Global settings updated successfully.');
            } else {
                $flash->set('info', 'Notice: Database is in read-only mode (rs_readonly). In live production, settings changes will be persisted.');
            }

            return $this->redirect('/sys-yii3/global-settings?section=' . urlencode($section));
        }

        $allSettings = $this->settingRepo->getAll();

        return $this->viewRenderer
            ->withViewPath('@src/Web/GlobalSettings/views')
            ->render('index', [
                'section' => $section,
                'settings' => $allSettings,
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
