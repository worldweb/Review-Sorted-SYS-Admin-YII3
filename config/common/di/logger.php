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

$logDir = dirname(__DIR__, 3) . '/runtime/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
$isLogDirWritable = is_writable($logDir);

return [
    StreamTarget::class => [
        'class' => StreamTarget::class,
        '__construct()' => [
            'stream' => $isLogDirWritable ? $logDir . '/app.log' : 'php://stderr',
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
        'setEnabled()' => [
            static function () use ($isLogDirWritable): bool {
                if ($isLogDirWritable || PHP_SAPI !== 'cli' || Environment::cronLogEnabled()) {
                    return true;
                }

                $isInteractive = function_exists('stream_isatty') && defined('STDOUT') && @stream_isatty(STDOUT);
                if ($isInteractive) {
                    return true;
                }

                foreach ($_SERVER['argv'] ?? [] as $arg) {
                    if ($arg === '--verbose' || $arg === '-v' || $arg === '-vv' || $arg === '-vvv' || str_starts_with((string) $arg, '--verbose=')) {
                        return true;
                    }
                }

                return false;
            },
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
