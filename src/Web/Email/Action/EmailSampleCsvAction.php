<?php

declare(strict_types=1);

namespace App\Web\Email\Action;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * EmailSampleCsvAction.
 * Serves a downloadable sample CSV template for email contact import.
 * Replicates frontend\controllers\EmailImporterController::actionTemplate in Yii 3.
 */
final class EmailSampleCsvAction
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $filename = 'reviews-sorted-email-import-template.csv';
        $content = "name,email,type\n"
            . "John Doe,john@example.com,to\n"
            . "Jane Smith,jane@example.com,cc\n"
            . "Support Desk,support@example.com,bcc\n";

        $response = $this->responseFactory
            ->createResponse(200)
            ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->withHeader('Content-Disposition', sprintf('attachment; filename="%s"', $filename));

        $response->getBody()->write($content);
        return $response;
    }
}
