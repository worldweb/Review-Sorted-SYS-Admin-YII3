<?php

declare(strict_types=1);

namespace App\Web\WhatsApp\Action;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * WhatsAppSampleCsvAction.
 * Serves a downloadable sample CSV template for WhatsApp contact import.
 */
final class WhatsAppSampleCsvAction
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $filename = 'reviews-sorted-whatsapp-import-template.csv';
        $content = "name,phone\n"
            . "John Doe,+61400111222\n"
            . "Jane Smith,+61400333444\n"
            . "Alex Taylor,+14155552671\n";

        $response = $this->responseFactory
            ->createResponse(200)
            ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->withHeader('Content-Disposition', sprintf('attachment; filename="%s"', $filename));

        $response->getBody()->write($content);
        return $response;
    }
}
