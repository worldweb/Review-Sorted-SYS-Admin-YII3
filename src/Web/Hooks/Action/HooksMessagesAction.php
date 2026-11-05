<?php

declare(strict_types=1);

namespace App\Web\Hooks\Action;

use App\Environment;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * HooksMessagesAction.
 * Handles incoming webhooks for Facebook Messenger and Review feeds at /hooks/messages.
 * Validates hub.mode and hub.verify_token against configured environment secret on handshake.
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
            $mode = (string) ($queryParams['hub_mode'] ?? $queryParams['hub.mode'] ?? '');
            $token = (string) ($queryParams['hub_verify_token'] ?? $queryParams['hub.verify_token'] ?? '');
            $challenge = (string) ($queryParams['hub_challenge'] ?? $queryParams['hub.challenge'] ?? '');
            $expectedToken = Environment::facebookVerifyToken();

            // Handshake verification matching Meta specification & legacy ChatHelper
            if ($mode === 'subscribe') {
                if ($expectedToken === '' || hash_equals($expectedToken, $token)) {
                    $response = $this->responseFactory->createResponse(200)
                        ->withHeader('Content-Type', 'text/plain');
                    $response->getBody()->write($challenge);
                    return $response;
                }

                // Verification token mismatch
                $response = $this->responseFactory->createResponse(403)
                    ->withHeader('Content-Type', 'text/plain');
                $response->getBody()->write('Forbidden: Invalid verification token');
                return $response;
            }

            // Fallback for direct challenge echo if no mode provided and token unconfigured
            if ($challenge !== '' && $expectedToken === '') {
                $response = $this->responseFactory->createResponse(200)
                    ->withHeader('Content-Type', 'text/plain');
                $response->getBody()->write($challenge);
                return $response;
            }

            $response = $this->responseFactory->createResponse(400)
                ->withHeader('Content-Type', 'text/plain');
            $response->getBody()->write('Bad Request');
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
