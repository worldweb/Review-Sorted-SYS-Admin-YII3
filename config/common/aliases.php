<?php

declare(strict_types=1);

use App\Environment;

$basePath = Environment::appBasePath();

return [
    '@root' => dirname(__DIR__, 2),
    '@src' => '@root/src',
    '@assets' => '@root/public/assets',
    '@assetsUrl' => ($basePath !== '' ? $basePath : '') . '/assets',
    '@assetsSource' => '@root/assets',
    '@baseUrl' => $basePath !== '' ? $basePath : '/',
    '@public' => '@root/public',
    '@runtime' => '@root/runtime',
    '@vendor' => '@root/vendor',
    '@themesUrl' => ($basePath !== '' ? $basePath : '') . '/themes/modern',
];
