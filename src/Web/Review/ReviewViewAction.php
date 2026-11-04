<?php

declare(strict_types=1);

namespace App\Web\Review;

use App\Domain\Review\ChannelReplyDispatcherInterface;
use App\Domain\Review\Review;
use App\Domain\Review\ReviewRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\Authorization\AccessDecisionManager;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * ReviewViewAction.
 * Renders the single Review detail page with insights, activity timeline, and reply composer.
 * Automatically marks unread reviews as read on view.
 * Handles reply submission with external channel dispatch (Google/Facebook), database persistence,
 * separated failure/success outcome reporting, and query parameter preservation.
 */
final class ReviewViewAction
{
    public function __construct(
        private ReviewRepositoryInterface $reviewRepo,
        private ChannelReplyDispatcherInterface $dispatcher,
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private AccessDecisionManager $accessDecision,
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

        $flash = new Flash($this->session);

        $currentUserId = $context->getUserId();
        $currentUser = $this->userRepo->findById($currentUserId, $context);
        if ($currentUser === null) {
            return $this->redirect(\App\Environment::url('/login'));
        }

        // Authorization check
        if (
            !$currentUser->isAdmin() &&
            !$currentUser->isAccountAdmin() &&
            !$this->accessDecision->getRbac()->can($currentUser, '/reviews/*')
        ) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: You are not authorized to view this review.');
            return $response;
        }

        $queryParams = $request->getQueryParams();
        $id = isset($queryParams['id']) ? (int) $queryParams['id'] : 0;

        // Save q param to session matching legacy behavior
        if (isset($queryParams['q']) && $queryParams['q'] !== '') {
            $this->session->set('reviews_q', (string) $queryParams['q']);
        }
        $qParam = (string) $this->session->get('reviews_q', '');

        if ($id <= 0) {
            $body = (array) ($request->getParsedBody() ?? []);
            $id = isset($body['id']) ? (int) $body['id'] : 0;
        }

        if ($id <= 0) {
            $response = $this->responseFactory->createResponse(404);
            $response->getBody()->write('Review not found.');
            return $response;
        }

        $review = $this->reviewRepo->findById($id, $context);
        if ($review === null) {
            $response = $this->responseFactory->createResponse(404);
            $response->getBody()->write('The requested review does not exist or you do not have permission to access it.');
            return $response;
        }

        // Automatically mark review as read if unread matching legacy Yii 2 behavior
        if (!$review->isRead()) {
            $this->reviewRepo->markAsRead($review->getId(), $context);
        }

        // Handle single review reply submission (POST)
        if ($request->getMethod() === 'POST') {
            $body = (array) ($request->getParsedBody() ?? []);
            $replyText = trim((string) ($body['reply_text'] ?? ''));

            if ($replyText === '') {
                $flash->set('error', 'Reply text cannot be empty.');
            } elseif ($review->getType() === Review::TYPE_MENTION) {
                $flash->set('error', 'Mentions cannot be replied to via automated API.');
            } elseif (!$review->isServiceReady()) {
                $flash->set('error', 'Connected service channel is not ready or missing authentication credentials.');
            } else {
                // Step 1: External Platform Dispatch
                $dispatchResult = $this->dispatcher->dispatch($review, $replyText);

                $redirectUrl = \App\Environment::url('/reviews');
                if (!empty($qParam)) {
                    $redirectUrl .= '?' . base64_decode($qParam);
                }

                if (!$dispatchResult->isSuccess()) {
                    // Case 1: External API failed. Do NOT insert local record.
                    $platform = $dispatchResult->getPlatform() ?? $review->getServiceLabel() ?? 'External platform';
                    $errorMsg = $dispatchResult->getErrorMessage() ?? 'Dispatch was rejected by the platform';
                    $flash->set('error', "{$platform} reply failed: {$errorMsg}");
                } else {
                    // External platform confirmed success!
                    $platformObjId = $dispatchResult->getObjId() ?? ('reply_' . $review->getId() . '_' . time());
                    $platformName = $dispatchResult->getPlatform() ?? $review->getServiceLabel() ?? 'Platform';

                    // Step 2: Attempt local database persistence
                    $savedReply = $this->reviewRepo->saveReply(
                        reviewId: $review->getId(),
                        userId: $currentUser->getId(),
                        message: $replyText,
                        objId: $platformObjId,
                        replyDate: $dispatchResult->getReplyDate() ?? date('Y-m-d H:i:s'),
                        replyExtra: $dispatchResult->getReplyExtra(),
                        context: $context,
                    );

                    if ($savedReply !== null) {
                        // Case 2: External API succeeded AND local database insert succeeded
                        $flash->set('success', 'Reply sent');
                        return $this->redirect($redirectUrl);
                    } else {
                        // Case 3 & 4: External API succeeded BUT local database insert failed (e.g. read-only DB)
                        // Report platform acceptance clearly so users do NOT retry and duplicate platform replies
                        $flash->set('warning', "Reply was successfully accepted by {$platformName} (ID: {$platformObjId}), but could not be saved to the local database because the database connection is currently read-only. Please do NOT resend this reply to avoid creating duplicates.");
                        return $this->redirect($redirectUrl);
                    }
                }
            }
        }

        // Fetch chronological conversation replies
        $replies = $this->reviewRepo->getRepliesForReview($review->getId(), $context);

        return $this->viewRenderer
            ->render(__DIR__ . '/views/view', [
                'review' => $review,
                'replies' => $replies,
                'currentUser' => $currentUser,
                'context' => $context,
                'flash' => $flash,
                'q' => $qParam,
                'csrfToken' => $this->csrfToken?->getValue(),
            ]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
