<?php

declare(strict_types=1);

use App\Domain\Account\AccountRepository;
use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\Setting\SettingRepository;
use App\Domain\Setting\SettingRepositoryInterface;
use App\Domain\User\UserRepository;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContext;
use App\Security\TenantContextInterface;

return [
    AccountRepositoryInterface::class => AccountRepository::class,
    UserRepositoryInterface::class => UserRepository::class,
    SettingRepositoryInterface::class => SettingRepository::class,
    TenantContextInterface::class => TenantContext::class,
];
