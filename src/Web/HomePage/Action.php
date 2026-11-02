<?php

declare(strict_types=1);

namespace App\Web\HomePage;

use App\Environment;
use Composer\InstalledVersions;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class Action
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ConnectionInterface $db,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $dbConnected = false;
        $dbServerVersion = 'Unknown';
        $dbDatabaseName = 'Unknown';
        $dbDriverName = 'Unknown';
        $dbError = null;

        try {
            // Safe read-only check: verify connection and retrieve server version
            $this->db->open();
            $dbDriverName = $this->db->getDriverName();
            $result = $this->db->createCommand('SELECT VERSION() as version, DATABASE() as db_name')->queryOne();

            if ($result !== false && is_array($result)) {
                $dbConnected = true;
                $dbServerVersion = (string)($result['version'] ?? 'Connected');
                $dbDatabaseName = (string)($result['db_name'] ?? 'Connected');
            }
        } catch (Throwable $e) {
            $dbConnected = false;
            $dbError = $e->getMessage();
        }

        $frameworkVersion = 'Yii 3 (yiisoft/app 1.4.0, di: '
            . (InstalledVersions::getPrettyVersion('yiisoft/di') ?? '1.4.x')
            . ', runner: '
            . (InstalledVersions::getPrettyVersion('yiisoft/yii-runner-http') ?? '3.2.x')
            . ')';

        return $this->viewRenderer->render(__DIR__ . '/template', [
            'appName' => 'Reviews Sorted',
            'frameworkVersion' => $frameworkVersion,
            'environment' => Environment::appEnv(),
            'debug' => Environment::appDebug(),
            'phpVersion' => PHP_VERSION,
            'phpSapi' => PHP_SAPI,
            'dbConnected' => $dbConnected,
            'dbDriverName' => $dbDriverName,
            'dbServerVersion' => $dbServerVersion,
            'dbDatabaseName' => $dbDatabaseName,
            'dbError' => $dbError,
        ]);
    }
}
