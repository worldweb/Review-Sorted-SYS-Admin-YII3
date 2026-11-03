<?php

declare(strict_types=1);

namespace App\Web\SocialPost\Action;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\LocationGroup\LocationGroupRepositoryInterface;
use App\Domain\SocialConnection\SocialConnectionRepositoryInterface;
use App\Domain\SocialPost\SocialPostRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class SocialPostCreateAction
{
    public function __construct(
        private SocialPostRepositoryInterface $postRepo,
        private SocialConnectionRepositoryInterface $connectionRepo,
        private LocationGroupRepositoryInterface $groupRepo,
        private AccountRepositoryInterface $accountRepo,
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
        if ($currentUser === null) {
            return $this->redirect('/sys-yii3/login');
        }

        $flash = new Flash($this->session);

        $fbConfigs = $this->connectionRepo->findFacebookConfigs($context, 100);
        $googleConfigs = $this->connectionRepo->findGoogleConfigs($context, 100);
        $groups = $this->groupRepo->findAllForAccount($currentUser->getAccountId() ?? 0, $context);

        if ($request->getMethod() === 'POST') {
            $parsedBody = (array) $request->getParsedBody();
            $content = trim((string) ($parsedBody['content'] ?? ''));
            $postType = ($parsedBody['post_type'] ?? 'immediate') === 'scheduled' ? 'scheduled' : 'immediate';
            $scheduledAt = $postType === 'scheduled' ? trim((string) ($parsedBody['scheduled_at'] ?? '')) : null;
            $selectedDestinations = (array) ($parsedBody['destinations'] ?? []);

            if (empty($content)) {
                $flash->set('error', 'Post content cannot be empty.');
            } elseif (empty($selectedDestinations)) {
                $flash->set('error', 'Please select at least one social destination.');
            } else {
                $destinationsToInsert = [];
                foreach ($selectedDestinations as $destStr) {
                    $parts = explode(':', (string) $destStr);
                    $platform = $parts[0] ?? 'facebook';
                    $targetId = (int) ($parts[1] ?? 0);

                    if ($platform === 'group') {
                        // Expand location group destinations
                        $group = $this->groupRepo->findById($targetId, $context);
                        if ($group !== null) {
                            foreach ($group->getDestinations() as $gd) {
                                $destinationsToInsert[] = [
                                    'platform' => $gd->getPlatform(),
                                    'service_config_id' => $gd->isFacebook() || $gd->isGoogle() ? $gd->getTargetId() : null,
                                    'sub_service_id' => $gd->isInstagram() ? $gd->getTargetId() : null,
                                ];
                            }
                        }
                    } else {
                        $destinationsToInsert[] = [
                            'platform' => $platform,
                            'service_config_id' => in_array($platform, ['facebook', 'google'], true) ? $targetId : null,
                            'sub_service_id' => $platform === 'instagram' ? $targetId : null,
                        ];
                    }
                }

                $createdId = $this->postRepo->createPost([
                    'account_id' => $currentUser->getAccountId() ?? 1,
                    'user_id' => $currentUser->getId(),
                    'content' => $content,
                    'media_type' => 'none',
                    'media_url' => null,
                    'post_type' => $postType,
                    'scheduled_at' => $scheduledAt ?: date('Y-m-d H:i:s'),
                ], $destinationsToInsert, $context);

                if ($createdId !== null) {
                    $flash->set('success', 'Social post created and scheduled successfully!');
                    return $this->redirect('/sys-yii3/social-post/view?id=' . $createdId);
                }

                $flash->set('error', 'Notice: Database is in read-only mode (rs_readonly). In live production, your post will be queued.');
            }
        }

        return $this->viewRenderer
            ->withViewPath('@src/Web/SocialPost/views')
            ->render('create', [
                'currentUser' => $currentUser,
                'fbConfigs' => $fbConfigs,
                'googleConfigs' => $googleConfigs,
                'groups' => $groups,
                'flash' => $flash,
                'csrfToken' => $this->csrfToken?->getValue() ?? '',
            ]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
