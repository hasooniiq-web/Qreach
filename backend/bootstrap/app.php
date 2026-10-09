<?php

use App\Exceptions\OtpCooldownException;
use App\Exceptions\OtpInvalidException;
use App\Exceptions\TagActivationException;
use App\Http\Middleware\EnsureAdminAuthenticated;
use App\Http\Middleware\EnsureAdminHasPermission;
use App\Http\Middleware\EnsureAdminTotpVerified;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind a tunnel or reverse proxy (Cloudflare, nginx) the proxy talks to us
        // over http from a local/private address and reports the real scheme in
        // X-Forwarded-*. Trusting those headers makes asset and form URLs https,
        // which is what stops the browser blocking them as mixed content.
        // Override with TRUSTED_PROXIES (comma separated, or "*") for a real deployment.
        $middleware->trustProxies(
            at: env('TRUSTED_PROXIES')
                ? (env('TRUSTED_PROXIES') === '*' ? '*' : array_map('trim', explode(',', env('TRUSTED_PROXIES'))))
                : ['127.0.0.1', '::1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'],
        );

        $middleware->append(SecurityHeaders::class);

        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->throttleApi('api');

        $middleware->alias([
            'admin' => EnsureAdminAuthenticated::class,
            'admin.permission' => EnsureAdminHasPermission::class,
            'admin.2fa' => EnsureAdminTotpVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*') || $request->expectsJson());

        // Structured error codes for the API (section 34) — never expose
        // stack traces, SQL, or internal paths regardless of APP_DEBUG.
        $exceptions->render(function (OtpInvalidException $e) {
            return response()->json(['error' => ['code' => 'OTP_INVALID', 'message' => $e->getMessage()]], 422);
        });

        $exceptions->render(function (TagActivationException $e) {
            return response()->json(['error' => ['code' => $e->errorCode, 'message' => $e->getMessage()]], 422);
        });

        $exceptions->render(function (OtpCooldownException $e) {
            return response()->json([
                'error' => ['code' => 'OTP_COOLDOWN', 'message' => $e->getMessage(), 'retry_after' => $e->secondsRemaining],
            ], 429);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                $error = \App\Services\SessionRegistry::wasReplaced($request)
                    ? ['code' => 'SESSION_REPLACED', 'message' => 'تم تسجيل الخروج لأن حسابك فُتح على جهاز آخر.']
                    : ['code' => 'UNAUTHENTICATED', 'message' => 'يجب تسجيل الدخول للوصول إلى هذا المورد.'];

                return response()->json(['error' => $error], 401);
            }
        });

        $exceptions->render(function (AuthorizationException|AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['error' => ['code' => 'FORBIDDEN', 'message' => 'ليست لديك صلاحية للوصول إلى هذا المورد.']], 403);
            }
        });

        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['error' => ['code' => 'NOT_FOUND', 'message' => 'المورد المطلوب غير موجود.']], 404);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'error' => ['code' => 'VALIDATION_FAILED', 'message' => 'بيانات غير صالحة.', 'fields' => $e->errors()],
                ], 422);
            }
        });
    })->create();
