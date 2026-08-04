<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\FeedbackRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\SettingRepositoryInterface;
use App\Repositories\Contracts\AdminRepositoryInterface;
use App\Repositories\Postgres\PostgresFeedbackRepository;
use App\Repositories\Postgres\PostgresNotificationRepository;
use App\Repositories\Postgres\PostgresUserRepository;


class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FeedbackRepositoryInterface::class, PostgresFeedbackRepository::class);
        $this->app->bind(NotificationRepositoryInterface::class, PostgresNotificationRepository::class);
        $this->app->bind(UserRepositoryInterface::class, PostgresUserRepository::class);
        
        $this->app->bind(SettingRepositoryInterface::class, \App\Repositories\Postgres\PostgresSettingRepository::class);
    }
}
