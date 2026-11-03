<?php

declare(strict_types=1);

namespace App\Web\Hooks\Action;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * HooksMessagesAction.
 * Handles incoming webhooks for Facebook Messenger and Review feeds at /hooks/messages.
 * Supports hub.challenge handshake for GET requests and entry processing for POST requests.
 */
final class HooksMessagesAction
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === 'GET') {
            $queryParams = $request->getQueryParams();
            $challenge = (string) ($queryParams['hub_challenge'] ?? $queryParams['hub.challenge'] ?? '');

            $response = $this->responseFactory->createResponse(200)
                ->withHeader('Content-Type', 'text/plain');
            $response->getBody()->write($challenge);
            return $response;
        }

        // POST webhook processing
        $body = (string) $request->getBody();
        $data = json_decode($body, true);

        $response = $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'application/json');

        if (is_array($data) && isset($data['object']) && $data['object'] === 'page') {
            $response->getBody()->write(json_encode(['status' => 'EVENT_RECEIVED']));
            return $response;
        }

        $response->getBody()->write(json_encode(['status' => 'IGNORED']));
        return $response;
    }
}
