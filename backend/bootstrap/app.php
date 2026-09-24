<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->throttleApi('api');

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdminAuthenticated::class,
            'admin.permission' => \App\Http\Middleware\EnsureAdminHasPermission::class,
            'admin.2fa' => \App\Http\Middleware\EnsureAdminTotpVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*') || $request->expectsJson());

        // Structured error codes for the API (section 34) — never expose
        // stack traces, SQL, or internal paths regardless of APP_DEBUG.
        $exceptions->render(function (\App\Exceptions\OtpInvalidException $e) {
            return response()->json(['error' => ['code' => 'OTP_INVALID', 'message' => $e->getMessage()]], 422);
        });

        $exceptions->render(function (\App\Exceptions\TagActivationException $e) {
            return response()->json(['error' => ['code' => $e->errorCode, 'message' => $e->getMessage()]], 422);
        });

        $exceptions->render(function (\App\Exceptions\OtpCooldownException $e) {
            return response()->json([
                'error' => ['code' => 'OTP_COOLDOWN', 'message' => $e->getMessage(), 'retry_after' => $e->secondsRemaining],
            ], 429);
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['error' => ['code' => 'UNAUTHENTICATED', 'message' => 'يجب تسجيل الدخول للوصول إلى هذا المورد.']], 401);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException|\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['error' => ['code' => 'FORBIDDEN', 'message' => 'ليست لديك صلاحية للوصول إلى هذا المورد.']], 403);
            }
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['error' => ['code' => 'NOT_FOUND', 'message' => 'المورد المطلوب غير موجود.']], 404);
            }
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'error' => ['code' => 'VALIDATION_FAILED', 'message' => 'بيانات غير صالحة.', 'fields' => $e->errors()],
                ], 422);
            }
        });
    })->create();
