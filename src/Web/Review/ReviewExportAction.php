<?php

declare(strict_types=1);

namespace App\Web\Review;

use App\Domain\Review\ReviewRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\Authorization\AccessDecisionManager;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * ReviewExportAction.
 * Handles the Export configuration form and generates streaming CSV downloads.
 * Strictly read-only, includes UTF-8 BOM, and streams directly to output without temporary disk files.
 */
final class ReviewExportAction
{
    public function __construct(
        private ReviewRepositoryInterface $reviewRepo,
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private AccessDecisionManager $accessDecision,
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

        $currentUserId = $context->getUserId();
        $currentUser = $this->userRepo->findById($currentUserId, $context);
        if ($currentUser === null) {
            return $this->redirect(\App\Environment::url('/login'));
        }

        // RBAC Authorization
        if (
            !$currentUser->isAdmin() &&
            !$currentUser->isAccountAdmin() &&
            !$this->accessDecision->getRbac()->can($currentUser, '/reviews/*')
        ) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: You are not authorized to export review data.');
            return $response;
        }

        // If POST request, process and stream the CSV download
        if (strtoupper($request->getMethod()) === 'POST') {
            $parsedBody = (array) ($request->getParsedBody() ?? []);
            
            // Extract date range params
            $nestedParams = isset($parsedBody['ReviewSearchExport']) && is_array($parsedBody['ReviewSearchExport'])
                ? $parsedBody['ReviewSearchExport']
                : [];

            $dateRange = (string) ($nestedParams['date_range'] ?? $parsedBody['date_range'] ?? 'all');
            $dateMin = (string) ($nestedParams['review_date_min'] ?? $parsedBody['review_date_min'] ?? '');
            $dateMax = (string) ($nestedParams['review_date_max'] ?? $parsedBody['review_date_max'] ?? '');

            $exportParams = [
                'date_range' => $dateRange,
                'review_date_min' => $dateMin !== '' ? $dateMin : null,
                'review_date_max' => $dateMax !== '' ? $dateMax : null,
            ];

            // Create temporary memory stream handle
            $outputStream = fopen('php://temp', 'r+');
            if ($outputStream === false) {
                $response = $this->responseFactory->createResponse(500);
                $response->getBody()->write('Internal Server Error: Unable to allocate export stream.');
                return $response;
            }

            // Write UTF-8 BOM for Microsoft Excel compatibility
            fwrite($outputStream, "\xEF\xBB\xBF");

            // CSV Column Headers matching Yii 2
            fputcsv($outputStream, ['Date', 'Customer', 'Page', 'Rating', 'Status', 'Text']);

            // Stream rows directly from database cursor generator into the stream
            foreach ($this->reviewRepo->getExportData($exportParams, $context) as $row) {
                fputcsv($outputStream, [
                    $row['date'],
                    $row['customer'],
                    $row['page'],
                    $row['rating'],
                    $row['status'],
                    $row['text'],
                ]);
            }

            rewind($outputStream);
            $bodyStream = new \HttpSoft\Message\Stream($outputStream);

            $filename = 'reviews_' . date('Y-m-d_His') . '.csv';

            return $this->responseFactory->createResponse(200)
                ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
                ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->withHeader('Pragma', 'no-cache')
                ->withHeader('Expires', '0')
                ->withBody($bodyStream);
        }

        // GET request: render the export options form
        return $this->viewRenderer
            ->render(__DIR__ . '/views/export', [
                'currentUser' => $currentUser,
                'context' => $context,
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
