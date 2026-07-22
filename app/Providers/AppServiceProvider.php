<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Firestore\FirestoreUserRepository;
use App\Repositories\Contracts\FeedbackRepositoryInterface;
use App\Repositories\Firestore\FirestoreFeedbackRepository;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Firestore\FirestoreNotificationRepository;
use App\Repositories\Contracts\AdminRepositoryInterface;
use App\Repositories\Firestore\FirestoreAdminRepository;
use App\Repositories\Contracts\SettingRepositoryInterface;
use App\Repositories\Firestore\FirestoreSettingRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(UserRepositoryInterface::class, FirestoreUserRepository::class);
        $this->app->singleton(FeedbackRepositoryInterface::class, FirestoreFeedbackRepository::class);
        $this->app->singleton(NotificationRepositoryInterface::class, FirestoreNotificationRepository::class);
        $this->app->singleton(AdminRepositoryInterface::class, FirestoreAdminRepository::class);
        $this->app->singleton(SettingRepositoryInterface::class, FirestoreSettingRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
