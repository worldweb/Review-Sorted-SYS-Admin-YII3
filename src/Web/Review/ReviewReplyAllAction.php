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
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * ReviewReplyAllAction.
 * Implements bulk review replying matching legacy ReviewsController::actionReplyAll.
 * Supports template placeholder replacement ({{customer_name}}, {{review_text}}, etc.),
 * sequential live channel dispatch with 2-second rate-limiting delay, and separated external/local outcome reporting.
 */
final class ReviewReplyAllAction
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
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $this->tenantContextProvider->get();
        if ($context->isGuest()) {
            return $this->redirect('/sys-yii3/login');
        }

        $flash = new Flash($this->session);

        $currentUserId = $context->getUserId();
        $currentUser = $this->userRepo->findById($currentUserId, $context);
        if ($currentUser === null) {
            return $this->redirect('/sys-yii3/login');
        }

        // Authorization check
        if (
            !$currentUser->isAdmin() &&
            !$currentUser->isAccountAdmin() &&
            !$this->accessDecision->getRbac()->can($currentUser, '/reviews/*')
        ) {
            $flash->set('error', 'Forbidden: You do not have permission to reply to reviews.');
            return $this->redirect('/sys-yii3/reviews');
        }

        $queryParams = $request->getQueryParams();
        $parsedBody = (array) ($request->getParsedBody() ?? []);

        // Save q param to session matching legacy behavior
        if (isset($queryParams['q']) && $queryParams['q'] !== '') {
            $this->session->set('reviews_q', (string) $queryParams['q']);
        }
        $qParam = (string) $this->session->get('reviews_q', '');

        // Extract review IDs from query or post body
        $idsStr = (string) ($queryParams['ids'] ?? $parsedBody['ids'] ?? '');
        $ids = array_filter(
            array_map('intval', explode(',', $idsStr)),
            static fn (int $id): bool => $id > 0
        );

        if (empty($ids)) {
            $flash->set('error', 'Please select at least 1 review to reply.');
            return $this->redirect('/sys-yii3/reviews');
        }

        // Load reviews scoped by tenant context and TYPE_REVIEW
        $reviews = $this->reviewRepo->findReviewsByIds($ids, $context);

        if (empty($reviews)) {
            $flash->set('error', 'No matching valid reviews found for the specified IDs.');
            return $this->redirect('/sys-yii3/reviews');
        }

        // Handle POST submission
        if ($request->getMethod() === 'POST') {
            $replyText = trim((string) ($parsedBody['reply_text'] ?? ''));

            if ($replyText === '') {
                $flash->set('error', 'Message text cannot be empty.');
                return $this->viewRenderer->render(__DIR__ . '/views/reply-all', [
                    'reviews' => $reviews,
                    'idsStr' => implode(',', $ids),
                    'replyText' => $replyText,
                    'q' => $qParam,
                    'flash' => $flash,
                ]);
            }

            $results = [];
            $total = count($reviews);
            $platformAcceptedCount = 0;
            $localSavedCount = 0;
            $platformFailedCount = 0;

            foreach ($reviews as $review) {
                $message = $replyText;
                foreach ($review->toArray() as $field => $val) {
                    $message = str_replace('{{' . $field . '}}', (string) $val, $message);
                }

                $platformName = $review->getServiceLabel() ?? 'Channel';

                try {
                    // Step 1: External Platform API Dispatch
                    $dispatchResult = $this->dispatcher->dispatch($review, $message);
                    $platformName = $dispatchResult->getPlatform() ?? $platformName;

                    if (!$dispatchResult->isSuccess()) {
                        // Case 1: External API failed. Do NOT insert local record.
                        $platformFailedCount++;
                        $results[$review->getId()] = [
                            'external_success' => false,
                            'local_saved' => false,
                            'status' => 'FAILED_EXTERNAL',
                            'obj_id' => null,
                            'error' => $dispatchResult->getErrorMessage() ?? 'Dispatch rejected by platform',
                            'platform' => $platformName,
                        ];
                    } else {
                        // External API confirmed acceptance
                        $platformAcceptedCount++;
                        $platformObjId = $dispatchResult->getObjId() ?? ('reply_' . $review->getId() . '_' . time());

                        // Step 2: Attempt local database persistence
                        $saved = $this->reviewRepo->saveReply(
                            reviewId: $review->getId(),
                            userId: $currentUser->getId(),
                            message: $message,
                            objId: $platformObjId,
                            replyDate: $dispatchResult->getReplyDate() ?? date('Y-m-d H:i:s'),
                            replyExtra: $dispatchResult->getReplyExtra(),
                            context: $context,
                        );

                        if ($saved !== null) {
                            // Case 2: External succeeded AND local succeeded
                            $localSavedCount++;
                            $results[$review->getId()] = [
                                'external_success' => true,
                                'local_saved' => true,
                                'status' => 'SENT_AND_SAVED',
                                'obj_id' => $platformObjId,
                                'error' => null,
                                'platform' => $platformName,
                            ];
                        } else {
                            // Case 3 & 4: External succeeded BUT local insert failed (read-only DB)
                            $results[$review->getId()] = [
                                'external_success' => true,
                                'local_saved' => false,
                                'status' => 'ACCEPTED_UNSAVED',
                                'obj_id' => $platformObjId,
                                'error' => 'Accepted by platform, but local database write was prevented (read-only mode)',
                                'platform' => $platformName,
                            ];
                        }
                    }
                } catch (\Throwable $e) {
                    $platformFailedCount++;
                    $results[$review->getId()] = [
                        'external_success' => false,
                        'local_saved' => false,
                        'status' => 'FAILED_EXCEPTION',
                        'obj_id' => null,
                        'error' => $e->getMessage(),
                        'platform' => $platformName,
                    ];
                }

                // 2-second rate-limiting delay between dispatches matching legacy Yii 2 ReviewMultiReplyForm
                sleep(2);
            }

            return $this->viewRenderer->render(__DIR__ . '/views/reply-all-result', [
                'reviews' => $reviews,
                'results' => $results,
                'total' => $total,
                'platformAcceptedCount' => $platformAcceptedCount,
                'localSavedCount' => $localSavedCount,
                'platformFailedCount' => $platformFailedCount,
                'q' => !empty($qParam) ? base64_decode($qParam) : '',
                'flash' => $flash,
            ]);
        }

        // Render preview & reply compose form
        return $this->viewRenderer->render(__DIR__ . '/views/reply-all', [
            'reviews' => $reviews,
            'idsStr' => implode(',', $ids),
            'replyText' => '',
            'q' => $qParam,
            'flash' => $flash,
        ]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
