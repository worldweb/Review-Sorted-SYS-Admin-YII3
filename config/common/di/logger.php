<?php

declare(strict_types=1);

use App\Environment;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Yiisoft\Definitions\ReferencesArray;
use Yiisoft\Log\Logger;
use Yiisoft\Log\Message;
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
        'setFormat()' => [
            static function (Message $message): string {
                $timestamp = $message->time()
                    ->setTimezone(new DateTimeZone(date_default_timezone_get()))
                    ->format('Y-m-d H:i:s');
                $level = strtoupper($message->level());
                $category = $message->category();
                $text = trim((string) preg_replace('/\s+/', ' ', $message->message()));
                $line = "[{$timestamp}] [{$level}] [{$category}] {$text}";

                $exception = $message->context('exception');
                if ($exception instanceof Throwable) {
                    $exMessage = trim((string) preg_replace('/\s+/', ' ', $exception->getMessage()));
                    $line .= sprintf(
                        ' | Exception: %s: %s in %s:%d',
                        $exception::class,
                        $exMessage,
                        $exception->getFile(),
                        $exception->getLine(),
                    );
                    $trace = trim($exception->getTraceAsString());
                    if ($trace !== '') {
                        $line .= "\nStack trace:\n" . $trace;
                    }
                }

                return $line;
            },
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
