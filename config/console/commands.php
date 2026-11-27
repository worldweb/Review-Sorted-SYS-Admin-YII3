<?php

declare(strict_types=1);

use App\Console;

return [
    'hello' => Console\HelloCommand::class,
    'email/process-queue' => Console\EmailQueueProcessCommand::class,
    'email/update-status' => Console\EmailStatusUpdateCommand::class,
    'whatsapp/process-queue' => Console\WhatsAppQueueProcessCommand::class,
    'social-post/process-queue' => Console\SocialPostQueueProcessCommand::class,
    'subscription/expire' => Console\SubscriptionExpireCommand::class,
    'review/sync' => Console\ReviewSyncCommand::class,
    'review/sync-google' => Console\ReviewSyncGoogleCommand::class,
    'review/sync-facebook' => Console\ReviewSyncFacebookCommand::class,
    'review/sync-mentions' => Console\ReviewSyncMentionsCommand::class,
    'review/sync-facebook-replies' => Console\ReviewSyncFacebookRepliesCommand::class,
    'google/fetch-location-metadata' => Console\GoogleLocationMetadataCommand::class,
    'db/sync' => Console\DatabaseSyncCommand::class,
];
