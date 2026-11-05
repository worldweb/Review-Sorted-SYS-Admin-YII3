<?php

declare(strict_types=1);

use App\Environment;

return [
    'yiisoft/session' => [
        'session' => [
            'options' => [
                'name' => 'RS_SESSID',
                'cookie_secure' => Environment::sessionCookieSecure() ? 1 : 0,
                'cookie_httponly' => Environment::sessionCookieHttpOnly() ? 1 : 0,
                'cookie_samesite' => ucfirst(Environment::sessionCookieSameSite()),
                'cookie_path' => '/',
            ],
            'handler' => null,
        ],
    ],
];
