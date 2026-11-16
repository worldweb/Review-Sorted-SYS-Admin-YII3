<?php

declare(strict_types=1);

use App\Environment;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Yiisoft\Definitions\ReferencesArray;
use Yiisoft\Log\Logger;
use Yiisoft\Log\StreamTarget;

/** @var array $params */

return [
    StreamTarget::class => [
        'class' => StreamTarget::class,
        '__construct()' => [
            'stream' => (function (): string {
                $logDir = dirname(__DIR__, 3) . '/runtime/logs';
                if (!is_dir($logDir)) {
                    @mkdir($logDir, 0775, true);
                }
                return is_writable($logDir) ? $logDir . '/app.log' : 'php://stderr';
            })(),
            'levels' => Environment::appDebug()
                ? []
                : [
                    LogLevel::EMERGENCY,
                    LogLevel::ALERT,
                    LogLevel::CRITICAL,
                    LogLevel::ERROR,
                    LogLevel::WARNING,
                ],
        ],
    ],

    LoggerInterface::class => [
        'class' => Logger::class,
        '__construct()' => [
            'targets' => ReferencesArray::from([
                StreamTarget::class,
            ]),
        ],
    ],
];
