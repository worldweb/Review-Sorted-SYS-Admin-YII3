<?php

declare(strict_types=1);

namespace App\Web\Hooks\Action;

use App\Domain\Chat\ChatMessage;
use App\Domain\Chat\ChatRepositoryInterface;
use App\Domain\Review\Sync\ReviewSyncServiceInterface;
use App\Domain\SocialConnection\ServiceConfig;
use App\Domain\SocialConnection\SocialConnectionRepositoryInterface;
use App\Environment;
use App\Security\TenantContext;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * HooksMessagesAction.
 * Handles incoming webhooks for Facebook Messenger, Instagram Direct, and Page Review feeds at /hooks/messages.
 * Validates hub.mode and hub.verify_token against configured environment secrets on handshake.
 * Parses and ingests incoming Messenger payloads into domain Chat and ChatMessage tables with tenant isolation.
 */
final class HooksMessagesAction
{
    private LoggerInterface $logger;

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private ChatRepositoryInterface $chatRepository,
        private SocialConnectionRepositoryInterface $socialConnectionRepository,
        private ?ReviewSyncServiceInterface $reviewSyncService = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === 'GET') {
            return $this->handleHandshake($request);
        }

        return $this->handleWebhookPayload($request);
    }

    /**
     * Handles Meta verification handshake (GET).
     */
    private function handleHandshake(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $mode = (string) ($queryParams['hub_mode'] ?? $queryParams['hub.mode'] ?? '');
        $token = (string) ($queryParams['hub_verify_token'] ?? $queryParams['hub.verify_token'] ?? '');
        $challenge = (string) ($queryParams['hub_challenge'] ?? $queryParams['hub.challenge'] ?? '');
        $expectedToken = Environment::facebookVerifyToken();

        if ($expectedToken === '') {
            $expectedToken = Environment::facebookAppSecret();
        }

        $this->logger->info('Facebook Webhook Handshake verification requested', [
            'mode' => $mode,
            'challenge' => $challenge,
        ]);

        if ($mode === 'subscribe') {
            if ($expectedToken === '' || hash_equals($expectedToken, $token)) {
                $this->logger->info('Facebook Webhook handshake verified successfully');
                $response = $this->responseFactory->createResponse(200)
                    ->withHeader('Content-Type', 'text/plain');
                $response->getBody()->write($challenge);
                return $response;
            }

            $this->logger->warning('Facebook Webhook handshake failed: token mismatch', [
                'received_token_length' => strlen($token),
            ]);

            $response = $this->responseFactory->createResponse(403)
                ->withHeader('Content-Type', 'text/plain');
            $response->getBody()->write('Forbidden: Invalid verification token');
            return $response;
        }

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

    /**
     * Handles incoming Meta webhook event payload (POST).
     */
    private function handleWebhookPayload(ServerRequestInterface $request): ResponseInterface
    {
        $body = (string) $request->getBody();
        $data = json_decode($body, true);

        if (!is_array($data)) {
            $this->logger->warning('Facebook Webhook: received malformed non-JSON payload');
            $response = $this->responseFactory->createResponse(200)
                ->withHeader('Content-Type', 'text/plain');
            $response->getBody()->write('IGNORED');
            return $response;
        }

        $object = (string) ($data['object'] ?? '');
        if ($object !== 'page' && $object !== 'instagram') {
            $this->logger->info("Facebook Webhook: ignoring unsupported object type: {$object}");
            $response = $this->responseFactory->createResponse(200)
                ->withHeader('Content-Type', 'text/plain');
            $response->getBody()->write('IGNORED');
            return $response;
        }

        $entries = $data['entry'] ?? [];
        if (!is_array($entries)) {
            $response = $this->responseFactory->createResponse(200)
                ->withHeader('Content-Type', 'text/plain');
            $response->getBody()->write('EVENT_RECEIVED');
            return $response;
        }

        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $entryPageId = (string) ($entry['id'] ?? '');

            // 1. Messenger messages processing
            if (isset($entry['messaging']) && is_array($entry['messaging'])) {
                $this->processMessagingEvents($entry['messaging'], $entryPageId, (int) ($entry['time'] ?? time()));
            }

            // 2. Rating & Review events processing
            if (isset($entry['changes']) && is_array($entry['changes'])) {
                $this->processChangesEvents($entry['changes'], $entryPageId);
            }
        }

        $response = $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'text/plain');
        $response->getBody()->write('EVENT_RECEIVED');
        return $response;
    }

    /**
     * Processes inbound Facebook Messenger messages.
     *
     * @param array<int, array<string, mixed>> $messagingEvents
     */
    private function processMessagingEvents(array $messagingEvents, string $entryPageId, int $entryTime): void
    {
        foreach ($messagingEvents as $msgData) {
            if (!is_array($msgData)) {
                continue;
            }

            // Skip delivery receipts, read receipts, and non-message interactions
            if (!isset($msgData['message']) || !is_array($msgData['message'])) {
                $this->logger->debug('Facebook Webhook: Skipping non-message interaction receipt');
                continue;
            }

            $message = $msgData['message'];

            // Skip messages sent as echo by the page itself if flagged
            if (!empty($message['is_echo'])) {
                $this->logger->debug('Facebook Webhook: Skipping echo message');
                continue;
            }

            // Determine Facebook Page ID
            $recipientId = (string) ($msgData['recipient']['id'] ?? '');
            $targetPageId = $recipientId !== '' ? $recipientId : $entryPageId;

            if ($targetPageId === '') {
                $this->logger->warning('Facebook Webhook: Cannot determine Facebook Page ID from payload');
                continue;
            }

            // Resolve ServiceConfig across all accounts (unrestricted context)
            $config = $this->socialConnectionRepository->findConfigByPlatformPageId(
                ServiceConfig::SERVICE_FACEBOOK,
                $targetPageId,
                TenantContext::unrestricted()
            );

            if ($config === null) {
                $this->logger->warning("Facebook Webhook: No active ServiceConfig found for Facebook Page ID {$targetPageId}");
                continue;
            }

            $accountId = $config->getAccountId();
            if ($accountId === null || $accountId <= 0) {
                $this->logger->warning("Facebook Webhook: ServiceConfig {$config->getId()} is not associated with an account");
                continue;
            }

            // Sender customer identification
            $customerId = (string) ($msgData['sender']['id'] ?? '');
            if ($customerId === '') {
                $this->logger->warning('Facebook Webhook: Message missing sender customer ID');
                continue;
            }

            // Message deduplication by Meta Message ID (mid)
            $objId = isset($message['mid']) ? (string) $message['mid'] : null;
            if ($objId !== null && $objId !== '' && $this->chatRepository->isChatMessageExists($objId)) {
                $this->logger->info("Facebook Webhook: Duplicate message {$objId} already exists, skipping insert");
                continue;
            }

            // Timestamp parsing
            $rawTimestamp = $msgData['timestamp'] ?? ($entryTime * 1000);
            $timestampSec = (int) ($rawTimestamp / 1000);
            $chatDate = date('Y-m-d H:i:s', $timestampSec > 0 ? $timestampSec : time());

            // Message content & extra payload extraction
            $chatText = '';
            $chatExtra = null;

            if (isset($message['text'])) {
                $chatText = (string) $message['text'];
            } elseif (isset($message['attachments'])) {
                $chatText = 'attach';
                $chatExtra = json_encode($message['attachments'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } else {
                $chatText = '[interaction]';
                $chatExtra = json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }

            // Find or initialize Chat thread
            $chat = $this->chatRepository->findChatByServiceConfigAndCustomer(
                $config->getId(),
                $customerId,
                TenantContext::unrestricted()
            );

            $customerName = $chat?->getCustomerName();
            if ($customerName === null || $customerName === '' || $customerName === 'Facebook User') {
                $customerName = $this->resolveCustomerName($customerId, $config->getPageToken(), $customerName);
            }

            if ($chat === null) {
                $chatId = $this->chatRepository->createChat(
                    accountId: $accountId,
                    serviceConfigId: $config->getId(),
                    customerId: $customerId,
                    customerName: $customerName,
                    startDate: $chatDate
                );
                $this->logger->info("Facebook Webhook: Created new chat thread #{$chatId} for account #{$accountId}");
            } else {
                $chatId = $chat->getId();
                $this->chatRepository->markChatUnread($chatId);
                $this->logger->info("Facebook Webhook: Updated existing chat thread #{$chatId} to unread");
            }

            // Insert ChatMessage record
            $messageId = $this->chatRepository->insertChatMessage(
                accountId: $accountId,
                chatId: $chatId,
                serviceConfigId: $config->getId(),
                objId: $objId,
                customerId: $customerId,
                customerName: $customerName,
                text: $chatText,
                extra: $chatExtra,
                date: $chatDate,
                type: ChatMessage::TYPE_RECEIVED,
                userId: null
            );

            $this->logger->info("Facebook Webhook: Successfully saved chat message #{$messageId} for chat #{$chatId}");
        }
    }

    /**
     * Processes review and rating change events.
     *
     * @param array<int, array<string, mixed>> $changes
     */
    private function processChangesEvents(array $changes, string $entryPageId): void
    {
        foreach ($changes as $change) {
            if (!is_array($change)) {
                continue;
            }

            $field = (string) ($change['field'] ?? '');
            if ($field === 'ratings') {
                $config = $this->socialConnectionRepository->findConfigByPlatformPageId(
                    ServiceConfig::SERVICE_FACEBOOK,
                    $entryPageId,
                    TenantContext::unrestricted()
                );

                if ($config !== null) {
                    $this->logger->info("Facebook Webhook: Ratings/review change event received for Page {$entryPageId} (Account #{$config->getAccountId()})");
                    $this->dispatchBackgroundFacebookSync($config->getId(), $entryPageId);
                } else {
                    $this->logger->warning("Facebook Webhook: Ratings event received for unknown Page {$entryPageId}");
                }
            }
        }
    }

    /**
     * Dispatches Facebook review synchronization asynchronously in the background.
     * Acknowledges webhook to Meta immediately without risk of timeout or delivery retries.
     * If background dispatch fails, logs the error and defers processing to the scheduled review sync cron.
     */
    private function dispatchBackgroundFacebookSync(int $channelId, string $pageId): void
    {
        $php = (str_ends_with(strtolower(PHP_BINARY), 'php.exe') || str_ends_with(strtolower(PHP_BINARY), 'php'))
            ? PHP_BINARY
            : 'php';
        $yii = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'yii';

        if (file_exists($yii)) {
            $cmd = escapeshellarg($php) . ' ' . escapeshellarg($yii) . ' review/sync-facebook --channel-id=' . $channelId;

            try {
                if (DIRECTORY_SEPARATOR === '\\') {
                    // Windows background execution
                    pclose(popen('start /B ' . $cmd . ' > NUL 2>&1', 'r'));
                } else {
                    // Linux / Unix background execution
                    exec($cmd . ' > /dev/null 2>&1 &');
                }
                $this->logger->info("Facebook Webhook: Dispatched background review sync for Page {$pageId} (Channel #{$channelId})");
                return;
            } catch (Throwable $e) {
                $this->logger->error("Facebook Webhook: Background CLI dispatch failed for Page {$pageId} (Channel #{$channelId}): {$e->getMessage()}. Channel will be processed by scheduled review sync cron.");
                return;
            }
        }

        $this->logger->warning("Facebook Webhook: Yii console runner not found at {$yii} for Page {$pageId}. Channel #{$channelId} will be processed by scheduled review sync cron.");
    }

    /**
     * Resolves Facebook user's full name from Graph API or returns a safe fallback.
     */
    private function resolveCustomerName(string $customerId, ?string $pageToken, ?string $fallbackName = null): string
    {
        if ($fallbackName !== null && $fallbackName !== '' && $fallbackName !== 'Facebook User') {
            return $fallbackName;
        }

        if (empty($pageToken) || trim($customerId) === '') {
            return $fallbackName ?? 'Facebook User';
        }

        try {
            $client = new Client(['timeout' => 2.0, 'connect_timeout' => 1.5]);
            $url = 'https://graph.facebook.com/v21.0/' . urlencode($customerId) . '?fields=first_name,last_name&access_token=' . urlencode($pageToken);
            $res = $client->get($url);
            if ($res->getStatusCode() === 200) {
                $body = json_decode((string) $res->getBody(), true);
                if (is_array($body) && !empty($body['first_name'])) {
                    $fullName = trim(($body['first_name'] ?? '') . ' ' . ($body['last_name'] ?? ''));
                    if ($fullName !== '') {
                        return $fullName;
                    }
                }
            }
        } catch (Throwable $e) {
            $this->logger->debug('Failed to fetch Facebook customer name: ' . $e->getMessage());
        }

        return $fallbackName ?? 'Facebook User';
    }
}
