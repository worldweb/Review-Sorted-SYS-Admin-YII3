<?php

declare(strict_types=1);

use App\Environment;
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
        $dbHost = Environment::dbHost();
        $dbPort = Environment::dbPort();
        $dbName = Environment::dbName();
        $dbUser = Environment::dbUser();
        $dbPass = Environment::dbPassword();
        $dbCharset = Environment::dbCharset();
        $tablePrefix = Environment::dbTablePrefix();

        $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset={$dbCharset}";
        $driver = new Driver($dsn, $dbUser, $dbPass);

        $connection = new Connection($driver, $schemaCache);
        if ($tablePrefix !== '') {
            $connection->setTablePrefix($tablePrefix);
        }

        return $connection;
    },
    Connection::class => Reference::to(ConnectionInterface::class),
];
