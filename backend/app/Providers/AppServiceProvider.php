<?php

namespace App\Providers;

use App\Contracts\SmsGatewayContract;
use App\Services\Sms\LogSmsGateway;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // No SMS provider is named in the spec — bind a dev-only log gateway
        // for now; swap this for a real provider before production (see
        // docs/ARCHITECTURE_DECISIONS.md and App\Services\Sms\LogSmsGateway).
        $this->app->bind(SmsGatewayContract::class, LogSmsGateway::class);
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        $this->registerRateLimiters();

        RedirectIfAuthenticated::redirectUsing(fn () => route('admin.dashboard'));
    }

    /**
     * Section 29: every sensitive/expensive endpoint is rate limited on
     * multiple dimensions (IP + account where applicable), never IP-only.
     */
    private function registerRateLimiters(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('otp-request', function (Request $request) {
            return [
                Limit::perMinute(1)->by('otp-phone:'.$request->input('phone')),
                Limit::perHour(5)->by('otp-phone-hour:'.$request->input('phone')),
                Limit::perHour(20)->by('otp-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('otp-verify', function (Request $request) {
            return [
                Limit::perMinute(10)->by('otp-verify-phone:'.$request->input('phone')),
                Limit::perHour(30)->by('otp-verify-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('admin-login', function (Request $request) {
            return [
                Limit::perMinute(5)->by('admin-login-email:'.strtolower((string) $request->input('email'))),
                Limit::perMinute(20)->by('admin-login-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('qr-activation', function (Request $request) {
            return [
                Limit::perMinute(10)->by('qr-activate-user:'.$request->user()?->id),
                Limit::perHour(60)->by('qr-activate-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('public-scan', function (Request $request) {
            return Limit::perMinute(30)->by('public-scan-ip:'.$request->ip());
        });

        RateLimiter::for('public-action', function (Request $request) {
            return [
                Limit::perMinute(6)->by('public-action-ip:'.$request->ip()),
                Limit::perMinute(3)->by('public-action-token:'.$request->route('token')),
            ];
        });

        RateLimiter::for('emergency-action', function (Request $request) {
            return [
                Limit::perHour(3)->by('emergency-ip:'.$request->ip()),
                Limit::perHour(3)->by('emergency-token:'.$request->route('token')),
            ];
        });

        RateLimiter::for('contact-request', function (Request $request) {
            return [
                Limit::perHour(20)->by('contact-ip:'.$request->ip()),
                Limit::perHour(15)->by('contact-token:'.$request->route('token')),
            ];
        });

        // Follow-ups inside an existing conversation are normal chat traffic,
        // so they get their own, roomier limit instead of sharing the new-report one.
        RateLimiter::for('thread-reply', function (Request $request) {
            return Limit::perMinute(20)->by('thread-reply:'.$request->ip().':'.$request->route('uuid'));
        });

        RateLimiter::for('report-export', function (Request $request) {
            return Limit::perHour(20)->by($request->user()?->id ?: $request->ip());
        });
    }
}
