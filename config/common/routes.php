<?php

declare(strict_types=1);

use App\Web;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

return [
    Group::create('/sys-yii3')
        ->routes(
            Route::get('[/]')
                ->action(Web\HomePage\Action::class)
                ->name('home'),
        ),
    Route::get('/')
        ->action(Web\HomePage\Action::class)
        ->name('root_home'),
];
