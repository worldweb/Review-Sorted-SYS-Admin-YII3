<?php

declare(strict_types=1);

namespace App\Web\Review;

use App\Domain\Review\Sync\ReviewSyncServiceInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;

/**
 * ReviewSyncAction.
 * Handles manual on-demand review synchronization triggered by authenticated account administrators.
 * Scoped strictly to the current tenant account boundary.
 */
final class ReviewSyncAction
{
    private LoggerInterface $logger;

    public function __construct(
        private ReviewSyncServiceInterface $syncService,
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private SessionInterface $session,
        private ResponseFactoryInterface $responseFactory,
        private ?ConnectionInterface $db = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $this->tenantContextProvider->get();
        $isAjax = strtolower($request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';

        if ($context->isGuest()) {
            return $this->errorResponse('Authentication required.', 401, $isAjax);
        }

        $userId = $context->getUserId();
        $currentUser = $this->userRepo->findById($userId, $context);
        if ($currentUser === null) {
            return $this->errorResponse('User not found.', 401, $isAjax);
        }

        // Only super admin and account admin are authorized to trigger live review synchronization
        if (!$currentUser->isAdmin() && !$currentUser->isAccountAdmin()) {
            return $this->errorResponse('Forbidden: You are not authorized to trigger review synchronization.', 403, $isAjax);
        }

        $accountId = $context->getAccountId();
        $body = (array) $request->getParsedBody();
        $channelId = !empty($body['channel_id']) ? (int) $body['channel_id'] : null;

        // Enforce strict tenant ownership if specific channel_id is supplied by non-superadmin
        if ($channelId !== null && !$currentUser->isAdmin()) {
            if ($accountId === null || $this->db === null) {
                return $this->errorResponse('Forbidden: Account context required to sync specific channel.', 403, $isAjax);
            }

            $ownerCheck = (new Query($this->db))
                ->select(['id'])
                ->from('{{%service_config}}')
                ->where(['id' => $channelId, 'account_id' => $accountId])
                ->one();

            if ($ownerCheck === false || $ownerCheck === null) {
                return $this->errorResponse('Forbidden: Channel does not belong to your account.', 403, $isAjax);
            }
        }

        try {
            if ($currentUser->isAdmin() && $accountId === null && $channelId === null) {
                // System administrator syncing all channels across all accounts
                $result = $this->syncService->syncAll();
            } else {
                // Account administrator or admin in account context syncing strictly within tenant boundary
                $targetAccountId = $currentUser->isAdmin() ? $accountId : $accountId;
                $googleResult = $this->syncService->syncGoogleReviews($targetAccountId, $channelId);
                $facebookResult = $this->syncService->syncFacebookReviews($targetAccountId, $channelId);
                $result = $googleResult->merge($facebookResult);
            }

            $message = sprintf(
                'Review synchronization completed successfully: %d channel(s) processed, %d review(s) fetched, %d inserted, %d updated.',
                $result->getChannelsProcessed(),
                $result->getReviewsFetched(),
                $result->getReviewsInserted(),
                $result->getReviewsUpdated()
            );

            if ($result->hasErrors()) {
                $message .= ' (Some channels reported warnings; check system logs for details.)';
            }

            if ($isAjax) {
                $response = $this->responseFactory->createResponse(200)->withHeader('Content-Type', 'application/json');
                $response->getBody()->write((string) json_encode([
                    'success' => true,
                    'message' => $message,
                    'stats' => $result->toArray(),
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                return $response;
            }

            $flash = new Flash($this->session);
            $flash->set('success', $message);
            return $this->responseFactory->createResponse(302)->withHeader('Location', \App\Environment::url('/reviews'));
        } catch (Throwable $e) {
            $this->logger->error('Manual review synchronization failed: ' . $e->getMessage());
            return $this->errorResponse('Synchronization failed: ' . $e->getMessage(), 500, $isAjax);
        }
    }

    private function errorResponse(string $message, int $statusCode, bool $isAjax): ResponseInterface
    {
        if ($isAjax) {
            $response = $this->responseFactory->createResponse($statusCode)->withHeader('Content-Type', 'application/json');
            $response->getBody()->write((string) json_encode([
                'success' => false,
                'error' => $message,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            return $response;
        }

        $flash = new Flash($this->session);
        $flash->set('error', $message);
        return $this->responseFactory->createResponse(302)->withHeader('Location', \App\Environment::url('/reviews'));
    }
}

