<?php

namespace App\Providers;

use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale('id');
        date_default_timezone_set(config('app.timezone', 'Asia/Jakarta'));

        // Gate: Asesor cannot enter schedule unless they have saved a digital signature in their profile
        Gate::define('enter-schedule', function (User $user, ?Schedule $schedule = null): bool {
            if ($user->role === 'ASESOR') {
                if (! $user->hasDigitalSignature()) {
                    return false;
                }

                if ($schedule !== null) {
                    return $schedule->relationLoaded('assessors')
                        ? $schedule->assessors->contains('id', $user->id)
                        : $schedule->assessors()->where('users.id', $user->id)->exists();
                }
            }

            return true;
        });

        Gate::define('access-schedule', function (User $user, ?Schedule $schedule = null): bool {
            return Gate::forUser($user)->allows('enter-schedule', $schedule);
        });
    }
}
