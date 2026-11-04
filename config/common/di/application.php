<?php

declare(strict_types=1);

use App\Environment;
use App\Shared\ApplicationParams;

/** @var array $params */

return [
    ApplicationParams::class => [
        '__construct()' => [
            'name' => $params['application']['name'] ?? Environment::appName(),
            'charset' => $params['application']['charset'] ?? 'UTF-8',
            'locale' => $params['application']['locale'] ?? 'en',
            'baseUrl' => Environment::appUrl(),
            'basePath' => Environment::appBasePath(),
        ],
    ],
];
