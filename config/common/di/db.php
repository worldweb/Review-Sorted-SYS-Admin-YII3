<?php

declare(strict_types=1);

use Psr\SimpleCache\CacheInterface;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Mysql\Driver;
use Yiisoft\Definitions\Reference;

return [
    SchemaCache::class => [
        'class' => SchemaCache::class,
        '__construct()' => [
            'psrCache' => Reference::to(CacheInterface::class),
        ],
    ],
    ConnectionInterface::class => static function (SchemaCache $schemaCache): ConnectionInterface {
        $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
        $dbPort = $_ENV['DB_PORT'] ?? '3306';
        $dbName = $_ENV['DB_NAME'] ?? 'sortedreviews_sys';
        $dbUser = $_ENV['DB_USER'] ?? 'root';
        $dbPass = $_ENV['DB_PASSWORD'] ?? '';
        $dbCharset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

        $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset={$dbCharset}";
        $driver = new Driver($dsn, $dbUser, $dbPass);

        return new Connection($driver, $schemaCache);
    },
    Connection::class => Reference::to(ConnectionInterface::class),
];
