<?php

declare(strict_types=1);

use App\Console;

return [
    'hello' => Console\HelloCommand::class,
    'email/process-queue' => Console\EmailQueueProcessCommand::class,
    'whatsapp/process-queue' => Console\WhatsAppQueueProcessCommand::class,
];
