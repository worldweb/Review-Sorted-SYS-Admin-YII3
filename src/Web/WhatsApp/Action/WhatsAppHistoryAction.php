<?php

declare(strict_types=1);

namespace App\Web\WhatsApp\Action;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\WhatsApp\WhatsAppQueue;
use App\Domain\WhatsApp\WhatsAppRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * WhatsAppHistoryAction.
 * Displays paginated message history logs and summary KPIs.
 * Replicates frontend\controllers\WhatsappController::actionHistory in Yii 3.
 */
final class WhatsAppHistoryAction
{
    public function __construct(
        private WhatsAppRepositoryInterface $whatsappRepo,
        private AccountRepositoryInterface $accountRepo,
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
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

        $currentUser = $this->userRepo->findById($context->getUserId(), $context);
        if ($currentUser === null || (!$currentUser->isAdmin() && !$currentUser->isAccountAdmin())) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: You are not authorized to view WhatsApp history.');
            return $response;
        }

        $flash = new Flash($this->session);
        $queryParams = $request->getQueryParams();

        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $pageSize = 20;

        $accountId = $context->getScopedAccountId() ?? $currentUser->getAccountId();
        if ($context->isUnrestricted() && isset($queryParams['account_id']) && (int) $queryParams['account_id'] > 0) {
            $accountId = (int) $queryParams['account_id'];
        }

        $historyItems = $this->whatsappRepo->findHistoryByAccount($context, $accountId, $page, $pageSize);
        $totalItems = $this->whatsappRepo->countHistoryByAccount($context, $accountId);

        // Compute KPI counts across all items for this account
        $allAccountItems = $this->whatsappRepo->findHistoryByAccount($context, $accountId, 1, 1000);
        $sentCount = 0;
        $queuedCount = 0;
        $failedCount = 0;
        foreach ($allAccountItems as $item) {
            if ($item->isSent()) {
                $sentCount++;
            } elseif ($item->isQueued() || $item->isProcessing()) {
                $queuedCount++;
            } elseif ($item->isFailed()) {
                $failedCount++;
            }
        }

        return $this->viewRenderer
            ->render(__DIR__ . '/../views/history', [
                'historyItems' => $historyItems,
                'totalItems' => $totalItems,
                'page' => $page,
                'pageSize' => $pageSize,
                'sentCount' => $sentCount,
                'queuedCount' => $queuedCount,
                'failedCount' => $failedCount,
                'accountId' => $accountId,
                'currentUser' => $currentUser,
                'context' => $context,
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
