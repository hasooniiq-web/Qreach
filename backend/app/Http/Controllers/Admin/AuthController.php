<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\AuditLogger;
use App\Services\SecurityEventLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use PragmaRX\Google2FALaravel\Facade as Google2FA;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (auth('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $admin = Admin::where('email', $request->string('email'))->first();

        if (! $admin || $admin->isLocked() || ! Auth::guard('admin')->attempt(
            $request->only('email', 'password'), $request->boolean('remember')
        )) {
            if ($admin) {
                $admin->increment('failed_login_attempts');
                if ($admin->failed_login_attempts >= 5) {
                    $admin->update(['locked_until' => now()->addMinutes(15)]);
                    SecurityEventLogger::log(
                        SecurityEventLogger::ACCOUNT_LOCKED, 'warning', 'admin', $admin->id,
                        ['email' => $admin->email], $request
                    );
                }
            }

            SecurityEventLogger::log(
                SecurityEventLogger::AUTH_FAILURE, 'info', 'admin', $admin?->id,
                ['email' => $request->input('email')], $request
            );
            AuditLogger::log('admin.login_failed', 'admin', $admin?->id, 'Admin', $admin?->id, 'failure', [], $request);

            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => 'بيانات الدخول غير صحيحة.',
            ]);
        }

        $request->session()->regenerate();

        $admin->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        SecurityEventLogger::log(SecurityEventLogger::AUTH_SUCCESS, 'info', 'admin', $admin->id, [], $request);
        AuditLogger::log('admin.login', 'admin', $admin->id, 'Admin', $admin->id, 'success', [], $request);

        if ($admin->must_change_password) {
            return redirect()->route('admin.password.change');
        }

        if ($admin->totp_enabled) {
            return redirect()->route('admin.totp.challenge');
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function showTotpChallenge(): View
    {
        return view('admin.auth.totp-challenge');
    }

    public function verifyTotp(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $admin = auth('admin')->user();

        if (! Google2FA::verifyKey(decrypt($admin->totp_secret), $request->input('code'))) {
            SecurityEventLogger::log(SecurityEventLogger::AUTH_FAILURE, 'warning', 'admin', $admin->id, ['stage' => 'totp'], $request);

            throw \Illuminate\Validation\ValidationException::withMessages([
                'code' => 'رمز التحقق غير صحيح.',
            ]);
        }

        $request->session()->put('admin_totp_verified_at', now()->toIso8601String());

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $adminId = auth('admin')->id();
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        AuditLogger::log('admin.logout', 'admin', $adminId, 'Admin', $adminId, 'success', [], $request);

        return redirect()->route('admin.login');
    }
}
