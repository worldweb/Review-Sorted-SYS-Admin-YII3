<?php

declare(strict_types=1);

namespace App\Web\SocialPost\Action;

use App\Domain\SocialPost\SocialPostRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;

final class SocialPostRetryAction
{
    public function __construct(
        private SocialPostRepositoryInterface $postRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private SessionInterface $session,
        private ResponseFactoryInterface $responseFactory,
        private ?LoggerInterface $logger = null,
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
        $parsedBody = (array) ($request->getParsedBody() ?? []);
        $queryParams = $request->getQueryParams();

        // 1. CSRF Validation on POST requests
        if ($request->getMethod() === 'POST' && $this->csrfToken !== null) {
            $token = (string) ($parsedBody['_csrf'] ?? '');
            if (!$this->csrfToken->validate($token)) {
                $flash->set('error', 'Invalid or expired CSRF token. Please refresh and try again.');
                $targetId = (int) ($parsedBody['id'] ?? $queryParams['id'] ?? 0);
                return $this->redirect($targetId > 0
                    ? \App\Environment::url('/social-post/view?id=' . $targetId)
                    : \App\Environment::url('/social-post'));
            }
        }

        $destinationId = (int) ($parsedBody['destination_id'] ?? $queryParams['destination_id'] ?? 0);
        $postId = (int) ($parsedBody['id'] ?? $queryParams['id'] ?? 0);
        $confirmRemoteAbsent = (string) ($parsedBody['confirm_remote_absent'] ?? '0');
        $clientIp = (string) ($request->getServerParams()['REMOTE_ADDR'] ?? 'unknown');

        // 2. Destination-Specific Retry Workflow
        if ($destinationId > 0) {
            $destination = $this->postRepo->findDestinationById($destinationId, $context);
            if ($destination === null) {
                $this->logger?->error('[SocialPostAudit] Ambiguous delivery override rejected/failed', [
                    'outcome' => 'REJECTED_OR_FAILED',
                    'reason' => 'destination_not_found_or_unauthorized',
                    'destination_id' => $destinationId,
                    'operator_id' => $context->getUserId(),
                    'client_ip' => $clientIp,
                    'timestamp' => date('Y-m-d H:i:s'),
                ]);
                $flash->set('error', 'Social post destination not found or access denied.');
                return $this->redirect(\App\Environment::url('/social-post'));
            }

            $redirectPostId = $destination->getSocialPostId() > 0 ? $destination->getSocialPostId() : $postId;
            $redirectUrl = \App\Environment::url('/social-post/view?id=' . $redirectPostId);

            if (!$destination->isFailed()) {
                $flash->set('error', 'Destination is not in a failed state.');
                return $this->redirect($redirectUrl);
            }

            $isAmbiguous = str_starts_with($destination->getErrorMessage() ?? '', 'Ambiguous delivery:');

            if ($isAmbiguous) {
                // Check 15-minute operational settling cooldown
                $elapsed = time() - $destination->getUpdatedAt();
                if ($elapsed < 900) {
                    $remainingMinutes = (int) ceil((900 - $elapsed) / 60);
                    $this->logger?->error('[SocialPostAudit] Ambiguous delivery override rejected/failed', [
                        'outcome' => 'REJECTED_OR_FAILED',
                        'reason' => 'cooldown_active',
                        'destination_id' => $destinationId,
                        'remaining_seconds' => 900 - $elapsed,
                        'operator_id' => $context->getUserId(),
                        'client_ip' => $clientIp,
                        'timestamp' => date('Y-m-d H:i:s'),
                    ]);
                    $flash->set('error', sprintf(
                        'Cooldown active: You must wait %d more minute(s) to allow remote platform queues to settle before overriding this ambiguous delivery.',
                        $remainingMinutes
                    ));
                    return $this->redirect($redirectUrl);
                }

                // Check explicit operator confirmation
                if ($confirmRemoteAbsent !== '1') {
                    $this->logger?->error('[SocialPostAudit] Ambiguous delivery override rejected/failed', [
                        'outcome' => 'REJECTED_OR_FAILED',
                        'reason' => 'confirmation_missing',
                        'destination_id' => $destinationId,
                        'operator_id' => $context->getUserId(),
                        'client_ip' => $clientIp,
                        'timestamp' => date('Y-m-d H:i:s'),
                    ]);
                    $flash->set('error', 'Confirmation required: You must explicitly verify your social channel and confirm the post is absent before overriding an ambiguous delivery.');
                    return $this->redirect($redirectUrl);
                }

                // Capture pre-override state for operational audit logging
                $originalError = $destination->getErrorMessage();
                $originalLockedAt = $destination->getLockedAt();
                $platform = $destination->getPlatform();

                // Execute fenced ambiguous override retry
                $success = $this->postRepo->retryDestination($destinationId, $context, isAmbiguousOverride: true);

                if ($success) {
                    $this->logger?->warning('[SocialPostAudit] Ambiguous delivery override succeeded', [
                        'outcome' => 'SUCCESS',
                        'destination_id' => $destinationId,
                        'social_post_id' => $redirectPostId,
                        'platform' => $platform,
                        'original_error' => $originalError,
                        'original_locked_at' => $originalLockedAt,
                        'operator_id' => $context->getUserId(),
                        'operator_account_id' => $context->getAccountId(),
                        'client_ip' => $clientIp,
                        'timestamp' => date('Y-m-d H:i:s'),
                    ]);
                    $flash->set('success', 'Ambiguous delivery verified and re-queued for delivery.');
                } else {
                    $this->logger?->error('[SocialPostAudit] Ambiguous delivery override rejected/failed', [
                        'outcome' => 'REJECTED_OR_FAILED',
                        'reason' => 'concurrency_fencing_failed_or_cooldown_invalid',
                        'destination_id' => $destinationId,
                        'operator_id' => $context->getUserId(),
                        'client_ip' => $clientIp,
                        'timestamp' => date('Y-m-d H:i:s'),
                    ]);
                    $flash->set('error', 'Unable to override destination. It may have been modified concurrently or the cooldown requirement was not met.');
                }

                return $this->redirect($redirectUrl);
            }

            // Standard (unambiguous) single destination retry
            $success = $this->postRepo->retryDestination($destinationId, $context, isAmbiguousOverride: false);
            if ($success) {
                $flash->set('success', 'Destination re-queued for delivery retry.');
            } else {
                $flash->set('error', 'Unable to retry destination. It may not be eligible or was modified concurrently.');
            }

            return $this->redirect($redirectUrl);
        }

        // 3. Post-Level Retry Workflow (Strictly retries non-ambiguous destinations)
        if ($postId > 0) {
            $this->postRepo->retryPost($postId, $context);
            $flash->set('success', 'Eligible failed destinations re-queued for delivery retry. Any ambiguous deliveries remain quarantined.');
            return $this->redirect(\App\Environment::url('/social-post/view?id=' . $postId));
        }

        $flash->set('error', 'Invalid retry request.');
        return $this->redirect(\App\Environment::url('/social-post'));
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
