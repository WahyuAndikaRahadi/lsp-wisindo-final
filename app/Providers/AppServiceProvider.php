<?php

namespace App\Providers;

use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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

        // Named Rate Limiters optimized for high-concurrency School / TUK Lab (NAT IP sharing)
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email', '');
            $throttleKey = Str::transliterate(Str::lower($email).'|'.$request->ip());

            return [
                // Anti-bruteforce per email account
                Limit::perMinute(5)->by($throttleKey)->response(function () {
                    abort(429, 'Terlalu banyak percobaan login untuk akun ini. Silakan tunggu 1 menit.');
                }),
                // Generous network-level limit so 50+ students in one lab can log in at the same minute
                Limit::perMinute(app()->environment('testing') ? 100 : 500)->by($request->ip())->response(function () {
                    abort(429, 'Terlalu banyak permintaan login dari jaringan ini. Silakan tunggu 1 menit.');
                }),
            ];
        });

        RateLimiter::for('register', function (Request $request) {
            return [
                Limit::perMinute(app()->environment('testing') ? 5 : 120)->by($request->ip())->response(function () {
                    abort(429, 'Terlalu banyak pendaftaran dari jaringan ini. Silakan tunggu 1 menit.');
                }),
            ];
        });

        RateLimiter::for('join-schedule', function (Request $request) {
            return [
                // Per individual user account limit
                Limit::perMinute(10)->by($request->user()?->id ?? $request->ip())->response(function () {
                    abort(429, 'Terlalu banyak percobaan bergabung jadwal. Silakan tunggu 1 menit.');
                }),
                // Generous network-level limit for shared TUK IP
                Limit::perMinute(app()->environment('testing') ? 20 : 300)->by($request->ip())->response(function () {
                    abort(429, 'Terlalu banyak permintaan bergabung dari jaringan ini. Silakan tunggu 1 menit.');
                }),
            ];
        });

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
