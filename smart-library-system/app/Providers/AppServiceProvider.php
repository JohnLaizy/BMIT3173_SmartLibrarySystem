<?php

namespace App\Providers;

use App\Models\Borrowing;
use App\Observers\BorrowingObserver;
use App\Models\Room;
use App\Observers\RoomObserver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use App\Contracts\UserManagementPort;
use App\Contracts\BookManagementPort;
use App\Integrations\UserManagement\JsonUserManagementAdapter;
use App\Integrations\BookManagement\JsonBookManagementAdapter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            UserManagementPort::class,
            JsonUserManagementAdapter::class
        );

        $this->app->bind(
            BookManagementPort::class,
            JsonBookManagementAdapter::class
        );
    }

    /**
     * Bootstrap any application services.
     */

    public function boot(): void
    {
       $this->configureDefaults();

       Room::observe(RoomObserver::class);
       Borrowing::observe(BorrowingObserver::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
