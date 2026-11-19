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
        if ($request->getMethod() !== 'POST') {
            return $this->responseFactory->createResponse(405)->withHeader('Allow', 'POST');
        }

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
        $lastChannelId = isset($body['last_channel_id']) ? (int) $body['last_channel_id'] : 0;
        $limit = isset($body['limit']) ? max(1, min(20, (int) $body['limit'])) : 10;
        $maxPages = isset($body['max_pages']) ? max(1, min(10, (int) $body['max_pages'])) : 2;

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
            @set_time_limit(0);

            // Resolve target account scope based on role and active workspace context
            if ($currentUser->isAdmin()) {
                // Super Admin / Platform Admin:
                // When explicitly switched into an account workspace, sync only that selected account;
                // when in the global workspace (scopedAccountId is null), sync all accounts globally.
                $targetAccountId = $context->getScopedAccountId();
            } else {
                // Account Admin: always strictly confined to their own tenant account
                $targetAccountId = $accountId;
            }

            if ($isAjax) {
                // Keyset-chunked synchronization for interactive browser requests
                $chunkData = $this->syncService->syncChunk(
                    accountId: $targetAccountId,
                    serviceConfigId: $channelId,
                    lastChannelId: $lastChannelId,
                    limit: $limit,
                    maxPages: $maxPages,
                );

                $response = $this->responseFactory->createResponse(200)
                    ->withHeader('Content-Type', 'application/json; charset=utf-8');
                $response->getBody()->write((string) json_encode([
                    'success' => true,
                    'total_channels' => $chunkData['total_channels'],
                    'channels_processed' => $chunkData['channels_processed'],
                    'last_channel_id' => $chunkData['last_channel_id'],
                    'has_more' => $chunkData['has_more'],
                    'reviews_fetched' => $chunkData['reviews_fetched'],
                    'reviews_inserted' => $chunkData['reviews_inserted'],
                    'reviews_updated' => $chunkData['reviews_updated'],
                    'errors' => $chunkData['errors'],
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

                return $response;
            }

            if ($targetAccountId === null && $channelId === null) {
                // Global synchronization across all accounts (both Google and Facebook)
                $result = $this->syncService->syncAll($maxPages);
            } else {
                // Account-scoped, workspace-scoped, or channel-specific synchronization
                $googleResult = $this->syncService->syncGoogleReviews($targetAccountId, $channelId, $maxPages);
                $facebookResult = $this->syncService->syncFacebookReviews($targetAccountId, $channelId, $maxPages);
                $result = $googleResult->merge($facebookResult);
            }

            $channelsProcessed = $result->getChannelsProcessed();
            $channelsFailed = $result->getChannelsFailed();
            $channelsSuccessful = $result->getChannelsSuccessful();
            $reviewsFetched = $result->getReviewsFetched();
            $reviewsInserted = $result->getReviewsInserted();
            $reviewsUpdated = $result->getReviewsUpdated();

            if ($channelsProcessed === 0) {
                $success = true;
                $message = 'No linked review channels found for synchronization.';
            } elseif ($result->isFullFailure()) {
                // All channels failed
                $success = false;
                $message = sprintf(
                    'Review synchronization failed (%d channel(s) failed): %s',
                    $channelsFailed,
                    implode(' | ', $result->getErrors())
                );
            } elseif ($result->isPartialSuccess()) {
                // Partial success: some channels succeeded, some failed
                $success = true;
                $message = sprintf(
                    'Review synchronization completed with warnings: %d of %d channel(s) synced (%d review(s) fetched, %d inserted, %d updated). Failed channel(s): %s',
                    $channelsSuccessful,
                    $channelsProcessed,
                    $reviewsFetched,
                    $reviewsInserted,
                    $reviewsUpdated,
                    implode(' | ', $result->getErrors())
                );
            } elseif ($reviewsFetched === 0) {
                // All channels successful, zero reviews
                $success = true;
                $message = sprintf(
                    'Review synchronization completed successfully: %d channel(s) processed. No reviews found on connected platforms.',
                    $channelsProcessed
                );
            } else {
                // All channels successful, reviews found
                $success = true;
                $message = sprintf(
                    'Review synchronization completed successfully: %d channel(s) processed, %d review(s) fetched, %d inserted, %d updated.',
                    $channelsProcessed,
                    $reviewsFetched,
                    $reviewsInserted,
                    $reviewsUpdated
                );
            }

            if ($isAjax) {
                $response = $this->responseFactory->createResponse(200)->withHeader('Content-Type', 'application/json');
                $response->getBody()->write((string) json_encode([
                    'success' => $success,
                    'message' => $message,
                    'error' => $success ? null : $message,
                    'stats' => $result->toArray(),
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                return $response;
            }

            $flash = new Flash($this->session);
            if ($success) {
                $flash->set('success', $message);
            } else {
                $flash->set('error', $message);
            }
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

