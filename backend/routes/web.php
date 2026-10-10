<?php

use App\Http\Controllers\Admin\AdminManagementController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ContactRequestController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DevOtpController;
use App\Http\Controllers\Admin\EmergencyController;
use App\Http\Controllers\Admin\PasswordController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SearchController;
use App\Http\Controllers\Admin\SecurityEventController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\SystemSettingController;
use App\Http\Controllers\Admin\TagBatchController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VehicleController;
use App\Http\Controllers\Public\PaymentGatewayController as PublicPaymentGatewayController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\PaymentGatewayController as AdminPaymentGatewayController;
use App\Http\Controllers\Public\ScanController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/login');

// Pages a payment provider (or the customer's browser returning from it) lands on.
// Display only: a payment's outcome is only ever set from a verified notification.
Route::prefix('pay')->name('payments.')->group(function () {
    Route::get('return/{uuid}', [PublicPaymentGatewayController::class, 'return'])
        ->middleware('throttle:public-scan')->name('return');
    // The sandbox provider's own pages (non-production only; 404 in production).
    Route::get('sandbox/{reference}', [PublicPaymentGatewayController::class, 'sandboxShow'])->name('sandbox.show');
    Route::post('sandbox/{reference}', [PublicPaymentGatewayController::class, 'sandboxComplete'])
        ->middleware('throttle:public-action')->name('sandbox.complete');
});

// Public QR/NFC scan experience (section 13) — no auth, no app install.
Route::prefix('t')->name('public.scan.')->group(function () {
    Route::get('{token}', [ScanController::class, 'show'])
        ->middleware('throttle:public-scan')->name('show');
    Route::get('{token}/avatar', [ScanController::class, 'ownerAvatar'])
        ->middleware('throttle:public-scan')->name('avatar');
    Route::get('{token}/cover', [ScanController::class, 'ownerCover'])
        ->middleware('throttle:public-scan')->name('cover');

    Route::middleware('throttle:public-action')->group(function () {
        Route::post('{token}/vehicle-block', [ScanController::class, 'vehicleBlock'])->name('vehicle-block');
        Route::post('{token}/lights-on', [ScanController::class, 'lightsOn'])->name('lights-on');
        Route::post('{token}/message', [ScanController::class, 'importantMessage'])->name('message');
    });

    Route::post('{token}/emergency', [ScanController::class, 'emergency'])
        ->middleware('throttle:emergency-action')->name('emergency');

    Route::post('{token}/contact', [ScanController::class, 'contactOwner'])
        ->middleware('throttle:contact-request')->name('contact');

    Route::get('{token}/c/{uuid}', [ScanController::class, 'thread'])
        ->middleware('throttle:public-scan')->name('thread');
    Route::get('{token}/c/{uuid}/messages', [ScanController::class, 'threadMessages'])
        ->middleware('throttle:120,1')->name('thread.messages');
    Route::post('{token}/c/{uuid}', [ScanController::class, 'threadReply'])
        ->middleware('throttle:thread-reply')->name('thread.reply');
    Route::get('{token}/c/{uuid}/messages/{message}/image', [ScanController::class, 'threadImage'])
        ->middleware('throttle:public-scan')->name('thread.image');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:admin-login')->name('login.attempt');
    });

    Route::middleware('admin')->group(function () {
        Route::get('totp/challenge', [AuthController::class, 'showTotpChallenge'])->name('totp.challenge');
        Route::post('totp/verify', [AuthController::class, 'verifyTotp'])
            ->middleware('throttle:admin-login')->name('totp.verify');
        Route::get('totp/setup', [AuthController::class, 'showTotpSetup'])->name('totp.setup');
        Route::post('totp/setup', [AuthController::class, 'confirmTotpSetup'])
            ->middleware('throttle:admin-login')->name('totp.setup.confirm');

        Route::get('password/change', [PasswordController::class, 'edit'])->name('password.change');
        Route::put('password', [PasswordController::class, 'update'])->name('password.update');

        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::middleware('admin.2fa')->group(function () {
            Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
            Route::get('poll', [DashboardController::class, 'poll'])->name('poll');
            Route::get('search', SearchController::class)->name('search');
            Route::get('dev-otp', [DevOtpController::class, 'index'])
                ->middleware('admin.permission:settings.view')->name('dev-otp');

            // Own profile only — none of these take an admin id, so there is no
            // way to view or change someone else's profile through them.
            Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
            Route::get('profile/avatar', [ProfileController::class, 'avatar'])->name('profile.avatar');
            Route::post('profile/avatar', [ProfileController::class, 'storeAvatar'])->name('profile.avatar.store');
            Route::delete('profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');
            Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

            Route::get('sections/{section}', function (string $section) {
                $titles = ['notifications' => 'التنبيهات والإشعارات'];

                abort_unless(isset($titles[$section]), 404);

                return view('admin.coming-soon', ['title' => $titles[$section]]);
            })->name('coming-soon');

            Route::get('emergencies', [EmergencyController::class, 'index'])
                ->middleware('admin.permission:emergency.view')->name('emergencies.index');

            Route::get('reports', [ReportController::class, 'index'])
                ->middleware('admin.permission:reports.view')->name('reports.index');
            Route::get('reports/export', [ReportController::class, 'export'])
                ->middleware(['admin.permission:reports.export', 'throttle:report-export'])->name('reports.export');

            Route::get('analytics', [AnalyticsController::class, 'index'])
                ->middleware('admin.permission:analytics.view')->name('analytics.index');

            Route::middleware('admin.permission:settings.view')->group(function () {
                Route::get('settings', [SystemSettingController::class, 'index'])->name('settings.index');
            });
            Route::put('settings', [SystemSettingController::class, 'update'])
                ->middleware('admin.permission:settings.update')->name('settings.update');

            Route::middleware('admin.permission:users.view')->group(function () {
                Route::get('users', [UserController::class, 'index'])->name('users.index');
                Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
            });
            Route::post('users/{user}/suspend', [UserController::class, 'suspend'])
                ->middleware('admin.permission:users.suspend')->name('users.suspend');
            Route::post('users/{user}/activate', [UserController::class, 'activate'])
                ->middleware('admin.permission:users.suspend')->name('users.activate');

            // Electronic payments: records + refunds, and gateway settings (credentials).
            Route::middleware('admin.permission:payments.view')->group(function () {
                Route::get('payments', [AdminPaymentController::class, 'index'])->name('payments.index');
                Route::get('payments/{payment}', [AdminPaymentController::class, 'show'])->name('payments.show');
            });
            Route::post('payments/{payment}/refund', [AdminPaymentController::class, 'refund'])
                ->middleware('admin.permission:payments.refund')->name('payments.refund');
            Route::get('payment-gateways', [AdminPaymentGatewayController::class, 'index'])
                ->middleware('admin.permission:payment_gateways.view')->name('payment-gateways.index');
            Route::get('payment-gateways/{driver}', [AdminPaymentGatewayController::class, 'edit'])
                ->middleware('admin.permission:payment_gateways.view')->name('payment-gateways.edit');
            Route::put('payment-gateways/{driver}', [AdminPaymentGatewayController::class, 'update'])
                ->middleware('admin.permission:payment_gateways.manage')->name('payment-gateways.update');

            // Subscriptions & plans. Literal segments (requests) come before any wildcard.
            Route::middleware('admin.permission:subscriptions.view')->group(function () {
                Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
                Route::get('subscriptions/requests', [SubscriptionController::class, 'requests'])->name('subscriptions.requests');
                Route::get('plans', [PlanController::class, 'index'])->name('plans.index');
            });
            Route::middleware('admin.permission:subscriptions.manage')->group(function () {
                Route::post('subscriptions/requests/{upgradeRequest}/approve', [SubscriptionController::class, 'approve'])->name('subscriptions.requests.approve');
                Route::post('subscriptions/requests/{upgradeRequest}/reject', [SubscriptionController::class, 'reject'])->name('subscriptions.requests.reject');
                Route::post('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
                Route::post('users/{user}/subscription', [SubscriptionController::class, 'grant'])->name('users.subscription.grant');
                Route::put('plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
            });

            Route::middleware('admin.permission:vehicles.view')->group(function () {
                Route::get('vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
                Route::get('vehicles/{vehicle}', [VehicleController::class, 'show'])->name('vehicles.show');
            });

            Route::middleware('admin.permission:qr.view')->group(function () {
                Route::get('qr', [TagController::class, 'index'])->name('tags.index');
                Route::get('qr/{tag:public_token}', [TagController::class, 'show'])->name('tags.show');
                Route::get('qr/{tag:public_token}/image.{format}', [TagController::class, 'image'])->name('tags.image');
            });
            Route::post('qr/{tag:public_token}/revoke', [TagController::class, 'revoke'])
                ->middleware('admin.permission:qr.revoke,qr.cancel,nfc.revoke,nfc.cancel')->name('tags.revoke');
            Route::post('qr/{tag:public_token}/replace', [TagController::class, 'replace'])
                ->middleware('admin.permission:qr.replace,nfc.replace')->name('tags.replace');

            // Literal-segment routes (batches/create) must be registered before
            // the wildcard batches/{tagBatch} route, or Laravel's router matches
            // the wildcard first and tries to bind a TagBatch with id "create".
            Route::middleware('admin.permission:qr.generate_batch')->group(function () {
                Route::get('batches/create', [TagBatchController::class, 'create'])->name('tag-batches.create');
                Route::post('batches', [TagBatchController::class, 'store'])->name('tag-batches.store');
            });
            Route::middleware('admin.permission:qr.view,printing.view')->group(function () {
                Route::get('batches', [TagBatchController::class, 'index'])->name('tag-batches.index');
                Route::get('batches/{tagBatch}', [TagBatchController::class, 'show'])->name('tag-batches.show');
                Route::get('batches/{tagBatch}/print', [TagBatchController::class, 'print'])->name('tag-batches.print');
            });
            Route::get('batches/{tagBatch}/export', [TagBatchController::class, 'exportCsv'])
                ->middleware('admin.permission:qr.export,printing.export')->name('tag-batches.export');
            Route::middleware('admin.permission:printing.export')->group(function () {
                Route::get('batches/{tagBatch}/export/codes', [TagBatchController::class, 'exportCodes'])->name('tag-batches.export.codes');
                Route::get('batches/{tagBatch}/export/text', [TagBatchController::class, 'exportText'])->name('tag-batches.export.text');
            });
            Route::middleware('admin.permission:printing.create')->group(function () {
                Route::post('batches/{tagBatch}/send-to-printing', [TagBatchController::class, 'sendToPrinting'])->name('tag-batches.send-to-printing');
                Route::post('batches/{tagBatch}/mark-unassigned', [TagBatchController::class, 'markUnassigned'])->name('tag-batches.mark-unassigned');
            });

            Route::middleware('admin.permission:contact_requests.view')->group(function () {
                Route::get('contact-requests', [ContactRequestController::class, 'index'])->name('contact-requests.index');
                Route::get('contact-requests/{contactRequest}', [ContactRequestController::class, 'show'])->name('contact-requests.show');
                Route::get('contact-requests/{contactRequest}/messages/{message}/image', [ContactRequestController::class, 'messageImage'])->name('contact-requests.message-image');
            });
            Route::post('contact-requests/{contactRequest}/close', [ContactRequestController::class, 'close'])
                ->middleware('admin.permission:contact_requests.respond')->name('contact-requests.close');

            Route::middleware('admin.permission:security.view')->group(function () {
                Route::get('security', [SecurityEventController::class, 'index'])->name('security.index');
            });
            Route::post('security/{securityEvent}/resolve', [SecurityEventController::class, 'resolve'])
                ->middleware('admin.permission:security.resolve')->name('security.resolve');

            Route::get('audit', [AuditLogController::class, 'index'])
                ->middleware('admin.permission:audit.view,audit_logs.view')->name('audit.index');

            Route::middleware('admin.permission:roles.view')->group(function () {
                Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
                Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
            });
            Route::middleware('admin.permission:roles.create')->group(function () {
                Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create');
                Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
            });
            Route::put('roles/{role}', [RoleController::class, 'update'])
                ->middleware('admin.permission:roles.update')->name('roles.update');
            Route::delete('roles/{role}', [RoleController::class, 'destroy'])
                ->middleware('admin.permission:roles.delete')->name('roles.destroy');

            Route::get('permissions', [PermissionController::class, 'index'])
                ->middleware('admin.permission:permissions.view')->name('permissions.index');

            Route::middleware('admin.permission:admins.view')->group(function () {
                Route::get('admins', [AdminManagementController::class, 'index'])->name('admins.index');
                Route::get('admins/{admin}/edit', [AdminManagementController::class, 'edit'])->name('admins.edit');
            });
            Route::middleware('admin.permission:admins.create')->group(function () {
                Route::get('admins/create', [AdminManagementController::class, 'create'])->name('admins.create');
                Route::post('admins', [AdminManagementController::class, 'store'])->name('admins.store');
            });
            Route::put('admins/{admin}', [AdminManagementController::class, 'update'])
                ->middleware('admin.permission:admins.update')->name('admins.update');
            Route::post('admins/{admin}/totp/reset', [AdminManagementController::class, 'resetTotp'])
                ->middleware('admin.permission:admins.update')->name('admins.totp.reset');
            Route::post('admins/{admin}/suspend', [AdminManagementController::class, 'suspend'])
                ->middleware('admin.permission:admins.update')->name('admins.suspend');
            Route::post('admins/{admin}/activate', [AdminManagementController::class, 'activate'])
                ->middleware('admin.permission:admins.update')->name('admins.activate');
        });
    });
});
