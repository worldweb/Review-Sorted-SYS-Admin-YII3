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
use Psr\Http\Message\UploadedFileInterface;
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
            return $this->redirect(\App\Environment::url('/login'));
        }

        $currentUser = $this->userRepo->findById($context->getUserId(), $context);
        if ($currentUser === null) {
            return $this->redirect(\App\Environment::url('/login'));
        }

        $flash = new Flash($this->session);

        // Retrieve all accessible location groups
        $targetAccountId = ($currentUser->isAdmin() || $context->isUnrestricted()) ? null : $currentUser->getAccountId();
        $groups = $this->groupRepo->findAll($context, $targetAccountId, 1, 200);

        // Preload destination details for location groups
        $groupOptions = [];
        $groupData = [];
        foreach ($groups as $grp) {
            $destList = $this->groupRepo->findDestinationsByGroupId($grp->getId());
            $destCount = count($destList);
            $groupOptions[$grp->getId()] = $grp->getName() . " ({$destCount} " . ($destCount === 1 ? 'Destination' : 'Destinations') . ')';

            $destArray = [];
            $destKeys = [];
            foreach ($destList as $d) {
                $destKeys[] = $d->getKey();
                $destArray[] = [
                    'key' => $d->getKey(),
                    'config_id' => $d->getConfigId(),
                    'sub_id' => $d->getSubId(),
                    'platform' => $d->getPlatform(),
                    'label' => $d->getLabel(),
                    'account_id' => $d->getAccountId(),
                    'account_name' => $d->getAccountName() ?? '',
                ];
            }
            $groupData[$grp->getId()] = [
                'id' => $grp->getId(),
                'name' => $grp->getName(),
                'count' => $destCount,
                'keys' => $destKeys,
                'destinations' => $destArray,
            ];
        }

        // Retrieve accessible social connections
        $fbConfigs = $this->connectionRepo->findFacebookConfigs($context, 1000);
        $googleConfigs = $this->connectionRepo->findGoogleConfigs($context, 1000);

        // Prepare structured destination lists
        $googleItems = [];
        foreach ($googleConfigs as $gc) {
            $googleItems[] = [
                'key' => $gc->getId() . ':0',
                'config_id' => $gc->getId(),
                'sub_id' => 0,
                'label' => $gc->getDisplayName(),
                'account_id' => $gc->getAccountId(),
                'account_name' => $gc->getAccountBusinessName() ?? '',
                'platform' => 'google',
            ];
        }

        $facebookItems = [];
        $instagramItems = [];
        foreach ($fbConfigs as $fb) {
            $facebookItems[] = [
                'key' => $fb->getId() . ':0',
                'config_id' => $fb->getId(),
                'sub_id' => 0,
                'label' => $fb->getDisplayName(),
                'account_id' => $fb->getAccountId(),
                'account_name' => $fb->getAccountBusinessName() ?? '',
                'platform' => 'facebook',
            ];
            foreach ($fb->getSubServices() as $sub) {
                $instagramItems[] = [
                    'key' => $fb->getId() . ':' . $sub->getId(),
                    'config_id' => $fb->getId(),
                    'sub_id' => $sub->getId(),
                    'label' => '@' . ltrim($sub->getName(), '@'),
                    'account_id' => $fb->getAccountId(),
                    'account_name' => $fb->getDisplayName(),
                    'platform' => 'instagram',
                ];
            }
        }

        if ($request->getMethod() === 'POST') {
            $parsedBody = (array) $request->getParsedBody();
            $content = trim((string) ($parsedBody['content'] ?? ''));
            $postType = ($parsedBody['post_type'] ?? 'immediate') === 'scheduled' ? 'scheduled' : 'immediate';
            $scheduledAt = $postType === 'scheduled' ? trim((string) ($parsedBody['scheduled_at'] ?? '')) : null;
            $selectedDestinations = (array) ($parsedBody['destinations'] ?? $parsedBody['destination_keys'] ?? []);
            $selectedGroupIds = (array) ($parsedBody['group_ids'] ?? []);
            $selectedPlatforms = (array) ($parsedBody['platforms'] ?? []);

            $validationErrors = [];

            // 1. Content validation
            if (empty($content)) {
                $validationErrors[] = 'Post content cannot be empty.';
            }

            // 2. Destinations and groups validation
            if (empty($selectedDestinations) && empty($selectedGroupIds)) {
                $validationErrors[] = 'Please select at least one target location, destination, or location group.';
            }

            // 3. Scheduling validation matching Yii2 SocialPostForm
            if ($postType === 'scheduled') {
                if (empty($scheduledAt)) {
                    $validationErrors[] = 'Please select a scheduled date and time.';
                } else {
                    $timestamp = strtotime($scheduledAt);
                    if ($timestamp === false) {
                        $validationErrors[] = 'Invalid date/time format. Please select a valid date and time.';
                    } else {
                        // 60-second clock skew tolerance
                        if ($timestamp < time() - 60) {
                            $validationErrors[] = 'Scheduled date and time must be in the future.';
                        }
                        // 30-day maximum scheduling window
                        if ($timestamp > strtotime('+30 days 23:59:59')) {
                            $validationErrors[] = 'Scheduled date cannot be beyond the allowed 30-day scheduling window.';
                        }
                    }
                }
            }

            // 4. Handle file upload and media validation
            $mediaType = 'none';
            $mediaUrl = null;
            $uploadedFiles = $request->getUploadedFiles();
            $mediaFile = $uploadedFiles['media'] ?? $uploadedFiles['media_file'] ?? null;
            if ($mediaFile instanceof UploadedFileInterface && $mediaFile->getError() === UPLOAD_ERR_OK) {
                // File size limit: 50MB
                if ($mediaFile->getSize() > 50 * 1024 * 1024) {
                    $validationErrors[] = 'Uploaded media file exceeds the 50 MB limit.';
                }

                $origName = $mediaFile->getClientFilename() ?? 'media';
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $videoExts = ['mp4', 'mov', 'webm', 'ogg'];

                if (in_array($ext, $imageExts, true)) {
                    $mediaType = 'image';
                } elseif (in_array($ext, $videoExts, true)) {
                    $mediaType = 'video';
                } else {
                    $validationErrors[] = 'Invalid file extension. Allowed media extensions are: ' . implode(', ', array_merge($imageExts, $videoExts));
                }

                if ($mediaType !== 'none' && empty($validationErrors)) {
                    $uploadDir = dirname(__DIR__, 4) . '/public/uploads/social-post';
                    if (!is_dir($uploadDir)) {
                        @mkdir($uploadDir, 0775, true);
                    }
                    $safeFileName = 'sp_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $targetPath = $uploadDir . '/' . $safeFileName;
                    $mediaFile->moveTo($targetPath);
                    $mediaUrl = '/uploads/social-post/' . $safeFileName;
                }
            }

            // 5. Platform-specific character limits matching Yii2 SocialPostForm
            $platformLimits = [
                'facebook'  => 63206,
                'instagram' => 2200,
                'google'    => 1500,
            ];
            if (!empty($selectedPlatforms)) {
                $effectiveLimit = 63206;
                foreach ($selectedPlatforms as $plat) {
                    if (isset($platformLimits[$plat]) && $platformLimits[$plat] < $effectiveLimit) {
                        $effectiveLimit = $platformLimits[$plat];
                    }
                }
                $contentLen = mb_strlen($content);
                if ($contentLen > $effectiveLimit) {
                    $validationErrors[] = sprintf(
                        'Post content (%s characters) exceeds the %s character limit for the selected platforms.',
                        number_format($contentLen),
                        number_format($effectiveLimit)
                    );
                }
            }

            // 6. Platform-specific media restrictions matching Yii2 SocialPostForm
            if (in_array('instagram', $selectedPlatforms, true) && $mediaType === 'none') {
                $validationErrors[] = 'Instagram Business API requires an image or video media attachment.';
            }

            if (in_array('google', $selectedPlatforms, true) && $mediaType === 'video') {
                $validationErrors[] = 'Google Business Profile Local Posts only support static image attachments (JPG/PNG). Video uploads are not supported for Google Local Posts.';
            }

            if (!empty($validationErrors)) {
                $flash->set('error', implode(' ', $validationErrors));
            } else {
                $destinationsToInsert = [];
                $processedDests = [];

                // 1. Resolve Location Groups
                foreach ($selectedGroupIds as $gId) {
                    $groupId = (int) $gId;
                    if ($groupId <= 0) {
                        continue;
                    }
                    $groupDests = $this->groupRepo->findDestinationsByGroupId($groupId);
                    foreach ($groupDests as $gd) {
                        $plat = $gd->getPlatform();
                        if (!empty($selectedPlatforms) && !in_array($plat, $selectedPlatforms, true)) {
                            continue;
                        }
                        $dedupKey = $plat . '-' . $gd->getConfigId() . '-' . ($gd->getSubId() ?: 0);
                        if (isset($processedDests[$dedupKey])) {
                            continue;
                        }
                        $processedDests[$dedupKey] = true;
                        $destinationsToInsert[] = [
                            'account_id' => $gd->getAccountId(),
                            'platform' => $plat,
                            'service_config_id' => $gd->getConfigId(),
                            'sub_service_id' => $gd->getSubId(),
                        ];
                    }
                }

                // 2. Resolve Individual Destinations
                foreach ($selectedDestinations as $destStr) {
                    $destStr = trim((string) $destStr);
                    if (empty($destStr)) {
                        continue;
                    }

                    $configId = null;
                    $subId = null;
                    $plat = null;
                    $destAccountId = $currentUser->getAccountId() ?? 1;

                    if (str_contains($destStr, ':')) {
                        [$first, $second] = explode(':', $destStr, 2);
                        if (is_numeric($first)) {
                            $configId = (int) $first;
                            $subId = (int) $second;

                            foreach ($googleItems as $gi) {
                                if ($gi['config_id'] === $configId) {
                                    $plat = 'google';
                                    $destAccountId = $gi['account_id'];
                                    break;
                                }
                            }
                            if ($plat === null) {
                                if ($subId > 0) {
                                    foreach ($instagramItems as $ii) {
                                        if ($ii['config_id'] === $configId && $ii['sub_id'] === $subId) {
                                            $plat = 'instagram';
                                            $destAccountId = $ii['account_id'];
                                            break;
                                        }
                                    }
                                } else {
                                    foreach ($facebookItems as $fi) {
                                        if ($fi['config_id'] === $configId) {
                                            $plat = 'facebook';
                                            $destAccountId = $fi['account_id'];
                                            break;
                                        }
                                    }
                                }
                            }
                        } else {
                            $plat = strtolower($first);
                            $targetId = (int) $second;
                            if ($plat === 'google' || $plat === 'facebook') {
                                $configId = $targetId;
                                $subId = null;
                                foreach ($plat === 'google' ? $googleItems : $facebookItems as $item) {
                                    if ($item['config_id'] === $targetId) {
                                        $destAccountId = $item['account_id'];
                                        break;
                                    }
                                }
                            } elseif ($plat === 'instagram') {
                                $subId = $targetId;
                                foreach ($instagramItems as $item) {
                                    if ($item['sub_id'] === $targetId) {
                                        $configId = $item['config_id'];
                                        $destAccountId = $item['account_id'];
                                        break;
                                    }
                                }
                            }
                        }
                    }

                    if ($plat !== null) {
                        if (!empty($selectedPlatforms) && !in_array($plat, $selectedPlatforms, true)) {
                            continue;
                        }
                        $dedupKey = $plat . '-' . ($configId ?: 0) . '-' . ($subId ?: 0);
                        if (isset($processedDests[$dedupKey])) {
                            continue;
                        }
                        $processedDests[$dedupKey] = true;
                        $destinationsToInsert[] = [
                            'account_id' => $destAccountId,
                            'platform' => $plat,
                            'service_config_id' => $configId,
                            'sub_service_id' => $subId,
                        ];
                    }
                }

                if (empty($destinationsToInsert)) {
                    $flash->set('error', 'No valid connected social destinations found for the selected targets and platforms.');
                } else {
                    $primaryAccountId = !empty($destinationsToInsert[0]['account_id'])
                        ? (int) $destinationsToInsert[0]['account_id']
                        : ($currentUser->getAccountId() ?? 1);

                    $createdId = $this->postRepo->createPost([
                        'account_id' => ($currentUser->isAdmin() || $context->isUnrestricted()) ? $primaryAccountId : ($currentUser->getAccountId() ?? 1),
                        'user_id' => $currentUser->getId(),
                        'content' => $content,
                        'media_type' => $mediaType,
                        'media_url' => $mediaUrl,
                        'post_type' => $postType,
                        'scheduled_at' => $scheduledAt ?: date('Y-m-d H:i:s'),
                    ], $destinationsToInsert, $context);

                    if ($createdId !== null) {
                        $flash->set('success', 'Social post created and scheduled successfully!');
                        return $this->redirect(\App\Environment::url('/social-post/view?id=' . $createdId));
                    }

                    $flash->set('error', 'Failed to create social post. Please check the logs.');
                }
            }
        }

        return $this->viewRenderer
            ->withViewPath('@src/Web/SocialPost/views')
            ->render('create', [
                'currentUser' => $currentUser,
                'groups' => $groups,
                'groupOptions' => $groupOptions,
                'groupData' => $groupData,
                'googleItems' => $googleItems,
                'facebookItems' => $facebookItems,
                'instagramItems' => $instagramItems,
                'flash' => $flash,
                'csrfToken' => $this->csrfToken?->getValue() ?? '',
            ]);
    }

    private function redirect(string $url): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(302);
        return $response->withHeader('Location', $url);
    }
}
