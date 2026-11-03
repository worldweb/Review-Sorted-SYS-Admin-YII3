<?php

declare(strict_types=1);

namespace App\Web\Subscription\Action;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * StripeWebhookAction.
 * Receives incoming asynchronous webhook events from Stripe for invoice payment,
 * subscription renewal, and checkout completion.
 */
final class StripeWebhookAction
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'application/json');

        $body = (string) $request->getBody();
        $payload = json_decode($body, true);

        if (!is_array($payload)) {
            $response->getBody()->write(json_encode(['status' => 'received', 'message' => 'Empty or non-JSON webhook ping']));
            return $response;
        }

        $eventType = $payload['type'] ?? 'unknown';

        // Replicate legacy switch behavior
        switch ($eventType) {
            case 'invoice.payment_succeeded':
            case 'invoice.payment_failed':
            case 'customer.subscription.updated':
            case 'customer.subscription.deleted':
                // Webhook event received and acknowledged
                break;
            default:
                break;
        }

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'event' => $eventType,
            'received_at' => time(),
        ]));

        return $response;
    }
}
