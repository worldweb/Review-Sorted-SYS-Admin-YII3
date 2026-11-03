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
    ChatRepositoryInterface::class => ChatRepository::class,

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

    // Review Reply and Channel Dispatcher Services
    \App\Domain\Review\Channel\GoogleReplyClientInterface::class => \App\Domain\Review\Channel\GoogleReplyClient::class,
    \App\Domain\Review\Channel\FacebookReplyClientInterface::class => \App\Domain\Review\Channel\FacebookReplyClient::class,
    \App\Domain\Review\ChannelReplyDispatcherInterface::class => \App\Domain\Review\ChannelReplyDispatcher::class,
    \Yiisoft\Session\Flash\FlashInterface::class => \Yiisoft\Session\Flash\Flash::class,

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
    \App\Domain\Email\Provider\MailgunClientInterface::class => \App\Domain\Email\Provider\MailgunClient::class,
    \App\Domain\Email\Provider\MailgunClient::class => \App\Domain\Email\Provider\MailgunClient::class,
    \App\Domain\WhatsApp\Provider\MetaWhatsAppClientInterface::class => \App\Domain\WhatsApp\Provider\MetaWhatsAppClient::class,
    \App\Domain\WhatsApp\Provider\MetaWhatsAppClient::class => \App\Domain\WhatsApp\Provider\MetaWhatsAppClient::class,

    // Outbound Queue Workers
    \App\Domain\Email\Service\EmailQueueWorkerService::class => \App\Domain\Email\Service\EmailQueueWorkerService::class,
    \App\Domain\WhatsApp\Service\WhatsAppQueueWorkerService::class => \App\Domain\WhatsApp\Service\WhatsAppQueueWorkerService::class,
];
