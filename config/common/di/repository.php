<?php

declare(strict_types=1);

use App\Domain\Account\AccountRepository;
use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\Chat\ChatRepository;
use App\Domain\Chat\ChatRepositoryInterface;
use App\Domain\Email\EmailRepository;
use App\Domain\Email\EmailRepositoryInterface;
use App\Domain\LocationGroup\LocationGroupRepository;
use App\Domain\LocationGroup\LocationGroupRepositoryInterface;
use App\Domain\QrCode\QrCodeRepository;
use App\Domain\QrCode\QrCodeRepositoryInterface;
use App\Domain\QrCode\QrScanRepository;
use App\Domain\QrCode\QrScanRepositoryInterface;
use App\Domain\Review\ReviewRepository;
use App\Domain\Review\ReviewRepositoryInterface;
use App\Domain\Setting\SettingRepository;
use App\Domain\Setting\SettingRepositoryInterface;
use App\Domain\SocialConnection\SocialConnectionRepository;
use App\Domain\SocialConnection\SocialConnectionRepositoryInterface;
use App\Domain\SocialPost\SocialPostRepository;
use App\Domain\SocialPost\SocialPostRepositoryInterface;
use App\Domain\Subscription\Provider\StripeClient;
use App\Domain\Subscription\Provider\StripeClientInterface;
use App\Domain\Subscription\SubscriptionRepository;
use App\Domain\Subscription\SubscriptionRepositoryInterface;
use App\Domain\User\UserRepository;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\WhatsApp\WhatsAppRepository;
use App\Domain\WhatsApp\WhatsAppRepositoryInterface;
use App\Security\Middleware\AuthenticationMiddleware;
use App\Security\RememberMeService;
use App\Security\TenantContextInterface;
use App\Security\TenantContextProvider;
use App\Security\TenantContextProviderInterface;
use App\Security\UserAuthenticationRepository;
use App\Security\UserAuthenticationRepositoryInterface;

return [
    // Request-level Tenant Context Provider
    TenantContextProviderInterface::class => TenantContextProvider::class,
    TenantContextInterface::class => static fn (TenantContextProviderInterface $provider): TenantContextInterface => $provider->get(),

    // Core Domain Repositories
    AccountRepositoryInterface::class => AccountRepository::class,
    ChatRepositoryInterface::class => ChatRepository::class,
    LocationGroupRepositoryInterface::class => LocationGroupRepository::class,
    EmailRepositoryInterface::class => EmailRepository::class,
    WhatsAppRepositoryInterface::class => WhatsAppRepository::class,
    QrCodeRepositoryInterface::class => QrCodeRepository::class,
    QrScanRepositoryInterface::class => QrScanRepository::class,
    ReviewRepositoryInterface::class => ReviewRepository::class,
    UserRepositoryInterface::class => UserRepository::class,
    SettingRepositoryInterface::class => SettingRepository::class,
    SocialConnectionRepositoryInterface::class => SocialConnectionRepository::class,
    SocialPostRepositoryInterface::class => SocialPostRepository::class,
    SubscriptionRepositoryInterface::class => SubscriptionRepository::class,
    StripeClientInterface::class => StripeClient::class,

    // Authentication Credential Repository (isolated from domain DTOs)
    UserAuthenticationRepositoryInterface::class => UserAuthenticationRepository::class,

    // Remember Me Service
    RememberMeService::class => RememberMeService::class,

    // Authentication Middleware
    AuthenticationMiddleware::class => AuthenticationMiddleware::class,

    // RBAC and Access Authorization Services
    \App\Security\Authorization\RbacInterface::class => \App\Security\Authorization\DatabaseRbacManager::class,
    \App\Security\Authorization\DatabaseRbacManager::class => \App\Security\Authorization\DatabaseRbacManager::class,
    \App\Security\Authorization\AccessDecisionManager::class => \App\Security\Authorization\AccessDecisionManager::class,
    \App\Security\Authorization\RolePermissionService::class => \App\Security\Authorization\RolePermissionService::class,

    // Review Reply and Channel Dispatcher Services
    \App\Domain\Review\Channel\GoogleReplyClientInterface::class => \App\Domain\Review\Channel\GoogleReplyClient::class,
    \App\Domain\Review\Channel\FacebookReplyClientInterface::class => \App\Domain\Review\Channel\FacebookReplyClient::class,
    \App\Domain\Review\ChannelReplyDispatcherInterface::class => \App\Domain\Review\ChannelReplyDispatcher::class,
    \Yiisoft\Session\Flash\FlashInterface::class => \Yiisoft\Session\Flash\Flash::class,

    // Review Synchronization Clients and Services
    \App\Domain\Review\Sync\GoogleReviewSyncClientInterface::class => \App\Domain\Review\Sync\GoogleReviewSyncClient::class,
    \App\Domain\Review\Sync\GoogleReviewSyncClient::class => \App\Domain\Review\Sync\GoogleReviewSyncClient::class,
    \App\Domain\Review\Sync\FacebookReviewSyncClientInterface::class => \App\Domain\Review\Sync\FacebookReviewSyncClient::class,
    \App\Domain\Review\Sync\FacebookReviewSyncClient::class => \App\Domain\Review\Sync\FacebookReviewSyncClient::class,
    \App\Domain\Review\Sync\ReviewSyncServiceInterface::class => \App\Domain\Review\Sync\ReviewSyncService::class,
    \App\Domain\Review\Sync\ReviewSyncService::class => \App\Domain\Review\Sync\ReviewSyncService::class,

    // QR Code Generation and Image Processing Services
    \App\Domain\QrCode\Service\QrImageProcessorInterface::class => \App\Domain\QrCode\Service\QrImageProcessor::class,
    \App\Domain\QrCode\Service\QrCodeMonkeyClientInterface::class => \App\Domain\QrCode\Service\QrCodeMonkeyClient::class,
    \App\Domain\QrCode\Service\QrCodeServiceInterface::class => \App\Domain\QrCode\Service\QrCodeService::class,

    // Navigation and UI Layout Services
    \App\Web\Shared\Navigation\NavigationMenuProvider::class => \App\Web\Shared\Navigation\NavigationMenuProvider::class,

    // CSV Stream Importer Service
    \App\Shared\CsvImportService::class => \App\Shared\CsvImportService::class,

    // Template Placeholder Rendering Service
    \App\Shared\TemplateRendererService::class => \App\Shared\TemplateRendererService::class,

    // Outbound Message Providers
    \App\Domain\Email\Provider\MailgunClient::class => static function (
        \Psr\Log\LoggerInterface $logger,
    ): \App\Domain\Email\Provider\MailgunClient {
        $isProd = \App\Environment::isProd();
        $enabled = \App\Environment::mailgunEnabled();
        $apiKey = \App\Environment::mailgunApiKey();
        $domain = \App\Environment::mailgunDomain();
        $endpoint = \App\Environment::mailgunEndpoint();
        $defaultFrom = (string) \App\Environment::get('MAIL_FROM_ADDRESS', 'noreply@reviewssorted.com');

        // Production safety: fail fast if live sending is requested in production but credentials are missing
        if ($isProd && $enabled && (empty($apiKey) || $apiKey === 'disabled')) {
            throw new \RuntimeException(
                '[MailgunClient] Live email sending is enabled (MAILGUN_ENABLED=true) in production, but MAILGUN_API_KEY is missing or invalid.'
            );
        }

        return new \App\Domain\Email\Provider\MailgunClient(
            apiKey: $apiKey,
            domain: $domain,
            endpoint: $endpoint,
            defaultFrom: $defaultFrom,
            enabled: $enabled,
            logger: $logger,
        );
    },
    \App\Domain\Email\Provider\MailgunClientInterface::class => static fn (
        \App\Domain\Email\Provider\MailgunClient $client,
    ): \App\Domain\Email\Provider\MailgunClientInterface => $client,

    \App\Domain\WhatsApp\Provider\MetaWhatsAppClient::class => static function (
        \Psr\Log\LoggerInterface $logger,
    ): \App\Domain\WhatsApp\Provider\MetaWhatsAppClient {
        $isProd = \App\Environment::isProd();
        $enabled = \App\Environment::whatsappEnabled();
        $token = \App\Environment::whatsappToken();
        $phoneNumberId = \App\Environment::whatsappPhoneNumberId();
        $apiVersion = \App\Environment::whatsappApiVersion();

        // Production safety: fail fast if live messaging is requested in production but credentials are missing
        if ($isProd && $enabled && (empty($token) || empty($phoneNumberId))) {
            throw new \RuntimeException(
                '[MetaWhatsAppClient] Live WhatsApp messaging is enabled (WHATSAPP_ENABLED=true) in production, but WHATSAPP_TOKEN or WHATSAPP_PHONE_NUMBER_ID is missing.'
            );
        }

        return new \App\Domain\WhatsApp\Provider\MetaWhatsAppClient(
            token: $token,
            phoneNumberId: $phoneNumberId,
            apiVersion: $apiVersion,
            enabled: $enabled,
            logger: $logger,
        );
    },
    \App\Domain\WhatsApp\Provider\MetaWhatsAppClientInterface::class => static fn (
        \App\Domain\WhatsApp\Provider\MetaWhatsAppClient $client,
    ): \App\Domain\WhatsApp\Provider\MetaWhatsAppClientInterface => $client,

    // Outbound Queue Workers
    \App\Domain\Email\Service\EmailQueueWorkerService::class => \App\Domain\Email\Service\EmailQueueWorkerService::class,
    \App\Domain\WhatsApp\Service\WhatsAppQueueWorkerService::class => \App\Domain\WhatsApp\Service\WhatsAppQueueWorkerService::class,

    // Social Publishing & Queue Worker Services
    \App\Domain\SocialPost\Service\SocialMediaUrlResolverInterface::class => \App\Infrastructure\SocialPost\Service\SocialMediaUrlResolver::class,
    \App\Infrastructure\SocialPost\Service\SocialMediaUrlResolver::class => \App\Infrastructure\SocialPost\Service\SocialMediaUrlResolver::class,

    \App\Domain\SocialPost\Client\MetaGraphApiClientInterface::class => \App\Infrastructure\SocialPost\Client\MetaGraphApiClient::class,
    \App\Infrastructure\SocialPost\Client\MetaGraphApiClient::class => \App\Infrastructure\SocialPost\Client\MetaGraphApiClient::class,

    \App\Domain\SocialPost\Client\GoogleApiClientInterface::class => \App\Infrastructure\SocialPost\Client\GoogleApiClient::class,
    \App\Infrastructure\SocialPost\Client\GoogleApiClient::class => \App\Infrastructure\SocialPost\Client\GoogleApiClient::class,

    \App\Domain\SocialPost\Resolver\FacebookPlatformResolver::class => \App\Domain\SocialPost\Resolver\FacebookPlatformResolver::class,
    \App\Domain\SocialPost\Resolver\InstagramPlatformResolver::class => \App\Domain\SocialPost\Resolver\InstagramPlatformResolver::class,
    \App\Domain\SocialPost\Resolver\GooglePlatformResolver::class => \App\Domain\SocialPost\Resolver\GooglePlatformResolver::class,

    \App\Domain\SocialPost\Service\SocialPlatformResolverFactory::class => static fn (
        \App\Domain\SocialPost\Resolver\FacebookPlatformResolver $fbResolver,
        \App\Domain\SocialPost\Resolver\InstagramPlatformResolver $igResolver,
        \App\Domain\SocialPost\Resolver\GooglePlatformResolver $googleResolver,
    ): \App\Domain\SocialPost\Service\SocialPlatformResolverFactory => new \App\Domain\SocialPost\Service\SocialPlatformResolverFactory([
        $fbResolver,
        $igResolver,
        $googleResolver,
    ]),
    \App\Domain\SocialPost\Service\SocialPlatformResolverFactoryInterface::class => static fn (
        \App\Domain\SocialPost\Service\SocialPlatformResolverFactory $factory,
    ): \App\Domain\SocialPost\Service\SocialPlatformResolverFactoryInterface => $factory,

    \App\Domain\SocialPost\Service\SocialPostingServiceInterface::class => \App\Domain\SocialPost\Service\SocialPostingService::class,
    \App\Domain\SocialPost\Service\SocialPostingService::class => \App\Domain\SocialPost\Service\SocialPostingService::class,
    \App\Domain\SocialPost\Service\SocialPostQueueWorkerService::class => \App\Domain\SocialPost\Service\SocialPostQueueWorkerService::class,
];
