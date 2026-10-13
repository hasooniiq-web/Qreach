<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Exceptions\AccountLockedException;
use App\Services\AuditLogger;
use App\Services\LoginLockout;
use App\Services\Recaptcha;
use App\Services\SecurityEventLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
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

        // Before any password is tried, so a bot is stopped without touching an account's failed-attempt counter.
        $recaptcha = app(Recaptcha::class);
        if (! $recaptcha->verify($request->input('g-recaptcha-response'), $request->ip())) {
            SecurityEventLogger::log(SecurityEventLogger::AUTH_FAILURE, 'info', 'admin', null, ['stage' => 'recaptcha'], $request);

            throw ValidationException::withMessages(['email' => 'أكّد أنك لست روبوتًا (reCAPTCHA) ثم حاول مرة أخرى.']);
        }

        $email = mb_strtolower(trim((string) $request->input('email')));
        $lockout = app(LoginLockout::class);

        // Before the password is tried: a locked account is refused even with the right password.
        try {
            $lockout->assertNotLocked(LoginLockout::ADMIN, $email);
        } catch (AccountLockedException $e) {
            SecurityEventLogger::log(
                SecurityEventLogger::AUTH_FAILURE, 'info', 'admin', null, ['email' => $email, 'stage' => 'locked'], $request
            );

            throw ValidationException::withMessages(['email' => $e->getMessage()]);
        }

        $admin = Admin::where('email', $email)->first();

        if (! $admin || ! Auth::guard('admin')->attempt(
            // Never "remembered": closing the browser ends the session and asks for a login again.
            ['email' => $admin->email, 'password' => $request->input('password')], false
        )) {
            // Counted the same way for an email that has no account.
            $state = $lockout->recordFailure(LoginLockout::ADMIN, $email);

            if ($admin) {
                $admin->increment('failed_login_attempts');
            }

            SecurityEventLogger::log(
                SecurityEventLogger::AUTH_FAILURE, 'info', 'admin', $admin?->id,
                ['email' => $email], $request
            );
            AuditLogger::log('admin.login_failed', 'admin', $admin?->id, 'Admin', $admin?->id, 'failure', [], $request);

            if ($state['locked']) {
                if ($admin) {
                    $admin->update(['locked_until' => $state['locked_until']]);
                }
                SecurityEventLogger::log(
                    SecurityEventLogger::ACCOUNT_LOCKED, 'warning', 'admin', $admin?->id,
                    ['email' => $email, 'retry_after' => $state['retry_after']], $request
                );

                throw ValidationException::withMessages([
                    'email' => (new AccountLockedException($state['locked_until'], $state['retry_after']))->getMessage(),
                ]);
            }

            throw ValidationException::withMessages([
                'email' => 'بيانات الدخول غير صحيحة.',
            ]);
        }

        $lockout->clear(LoginLockout::ADMIN, $email);

        $request->session()->regenerate();
        // The authenticator code is asked on every sign-in, never carried over from an earlier one.
        $request->session()->forget('admin_totp_verified_at');

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

        // No authenticator yet: the dashboard middleware sends the admin to enrollment.
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
        $lockout = app(LoginLockout::class);

        try {
            $lockout->assertNotLocked(LoginLockout::ADMIN_TOTP, (string) $admin->id);
        } catch (AccountLockedException $e) {
            throw ValidationException::withMessages(['code' => $e->getMessage()]);
        }

        if (! Google2FA::verifyKey($admin->totp_secret, preg_replace('/\s+/', '', (string) $request->input('code')))) {
            SecurityEventLogger::log(SecurityEventLogger::AUTH_FAILURE, 'warning', 'admin', $admin->id, ['stage' => 'totp'], $request);

            $state = $lockout->recordFailure(LoginLockout::ADMIN_TOTP, (string) $admin->id);
            if ($state['locked']) {
                SecurityEventLogger::log(
                    SecurityEventLogger::ACCOUNT_LOCKED, 'warning', 'admin', $admin->id,
                    ['stage' => 'totp', 'retry_after' => $state['retry_after']], $request
                );

                throw ValidationException::withMessages([
                    'code' => (new AccountLockedException($state['locked_until'], $state['retry_after']))->getMessage(),
                ]);
            }

            throw ValidationException::withMessages([
                'code' => 'رمز التحقق غير صحيح. بقيت لك '.$state['attempts_left'].' محاولة قبل الإيقاف المؤقت.',
            ]);
        }

        $lockout->clear(LoginLockout::ADMIN_TOTP, (string) $admin->id);

        $request->session()->put('admin_totp_verified_at', now()->toIso8601String());

        return redirect()->intended(route('admin.dashboard'));
    }

    /** First-time enrollment of an authenticator app (mandatory for every admin). */
    public function showTotpSetup(Request $request): View|RedirectResponse
    {
        $admin = auth('admin')->user();

        if ($admin->totp_enabled) {
            return redirect()->route('admin.dashboard');
        }

        // Kept in the session so a page refresh shows the same QR the admin may already have scanned.
        $secret = $request->session()->get('admin_totp_pending_secret');
        if (! $secret) {
            $secret = Google2FA::generateSecretKey();
            $request->session()->put('admin_totp_pending_secret', $secret);
        }

        $otpauth = Google2FA::getQRCodeUrl(config('app.name', 'Qreach'), $admin->email, $secret);
        $svg = (new \Endroid\QrCode\Builder\Builder(writer: new \Endroid\QrCode\Writer\SvgWriter))
            ->build(data: $otpauth, size: 220, margin: 6)->getString();

        return view('admin.auth.totp-setup', [
            'qr' => 'data:image/svg+xml;base64,'.base64_encode($svg),
            'secret' => trim(chunk_split($secret, 4, ' ')),
        ]);
    }

    public function confirmTotpSetup(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $admin = auth('admin')->user();
        $secret = $request->session()->get('admin_totp_pending_secret');

        if (! $secret || ! Google2FA::verifyKey($secret, preg_replace('/\s+/', '', $request->input('code')))) {
            SecurityEventLogger::log(SecurityEventLogger::AUTH_FAILURE, 'warning', 'admin', $admin->id, ['stage' => 'totp_setup'], $request);

            throw ValidationException::withMessages(['code' => 'رمز التحقق غير صحيح. تأكد من مسح الرمز في التطبيق وأن وقت هاتفك مضبوط تلقائيًا.']);
        }

        $admin->forceFill(['totp_secret' => $secret, 'totp_enabled' => true])->save();
        $request->session()->forget('admin_totp_pending_secret');
        $request->session()->put('admin_totp_verified_at', now()->toIso8601String());

        AuditLogger::log('admin.totp_enabled', 'admin', $admin->id, 'Admin', $admin->id, 'success', [], $request);

        return redirect()->intended(route('admin.dashboard'))->with('status', 'تم تفعيل التحقق بخطوتين.');
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
