<?php

use App\Http\Controllers\Admin\AdminManagementController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ContactRequestController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PasswordController;
use App\Http\Controllers\Admin\SecurityEventController;
use App\Http\Controllers\Admin\TagBatchController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VehicleController;
use App\Http\Controllers\Public\ScanController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/login');

// Public QR/NFC scan experience (section 13) — no auth, no app install.
Route::prefix('t')->name('public.scan.')->group(function () {
    Route::get('{token}', [ScanController::class, 'show'])
        ->middleware('throttle:public-scan')->name('show');

    Route::middleware('throttle:public-action')->group(function () {
        Route::post('{token}/vehicle-block', [ScanController::class, 'vehicleBlock'])->name('vehicle-block');
        Route::post('{token}/lights-on', [ScanController::class, 'lightsOn'])->name('lights-on');
        Route::post('{token}/message', [ScanController::class, 'importantMessage'])->name('message');
    });

    Route::post('{token}/emergency', [ScanController::class, 'emergency'])
        ->middleware('throttle:emergency-action')->name('emergency');

    Route::post('{token}/contact', [ScanController::class, 'contactOwner'])
        ->middleware('throttle:contact-request')->name('contact');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:admin-login')->name('login.attempt');
    });

    Route::middleware('admin')->group(function () {
        Route::get('totp/challenge', [AuthController::class, 'showTotpChallenge'])->name('totp.challenge');
        Route::post('totp/verify', [AuthController::class, 'verifyTotp'])->name('totp.verify');

        Route::get('password/change', [PasswordController::class, 'edit'])->name('password.change');
        Route::put('password', [PasswordController::class, 'update'])->name('password.update');

        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::middleware('admin.2fa')->group(function () {
            Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

            Route::get('sections/{section}', function (string $section) {
                $titles = [
                    'notifications' => 'التنبيهات والإشعارات', 'reports' => 'التقارير والتحليلات',
                    'settings' => 'الإعدادات', 'nfc' => 'رموز NFC',
                ];

                abort_unless(isset($titles[$section]), 404);

                return view('admin.coming-soon', ['title' => $titles[$section]]);
            })->name('coming-soon');

            Route::middleware('admin.permission:users.view')->group(function () {
                Route::get('users', [UserController::class, 'index'])->name('users.index');
                Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
            });
            Route::post('users/{user}/suspend', [UserController::class, 'suspend'])
                ->middleware('admin.permission:users.suspend')->name('users.suspend');
            Route::post('users/{user}/activate', [UserController::class, 'activate'])
                ->middleware('admin.permission:users.suspend')->name('users.activate');

            Route::middleware('admin.permission:vehicles.view')->group(function () {
                Route::get('vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
                Route::get('vehicles/{vehicle}', [VehicleController::class, 'show'])->name('vehicles.show');
            });

            Route::middleware('admin.permission:qr.view')->group(function () {
                Route::get('qr', [TagController::class, 'index'])->name('tags.index');
                Route::get('qr/{tag:public_token}/image.{format}', [TagController::class, 'image'])->name('tags.image');
            });
            Route::post('qr/{tag:public_token}/revoke', [TagController::class, 'revoke'])
                ->middleware('admin.permission:qr.revoke')->name('tags.revoke');

            // Literal-segment routes (batches/create) must be registered before
            // the wildcard batches/{tagBatch} route, or Laravel's router matches
            // the wildcard first and tries to bind a TagBatch with id "create".
            Route::middleware('admin.permission:qr.generate_batch')->group(function () {
                Route::get('batches/create', [TagBatchController::class, 'create'])->name('tag-batches.create');
                Route::post('batches', [TagBatchController::class, 'store'])->name('tag-batches.store');
            });
            Route::middleware('admin.permission:qr.view')->group(function () {
                Route::get('batches', [TagBatchController::class, 'index'])->name('tag-batches.index');
                Route::get('batches/{tagBatch}', [TagBatchController::class, 'show'])->name('tag-batches.show');
                Route::get('batches/{tagBatch}/print', [TagBatchController::class, 'print'])->name('tag-batches.print');
            });
            Route::get('batches/{tagBatch}/export', [TagBatchController::class, 'exportCsv'])
                ->middleware('admin.permission:qr.export')->name('tag-batches.export');

            Route::middleware('admin.permission:contact_requests.view')->group(function () {
                Route::get('contact-requests', [ContactRequestController::class, 'index'])->name('contact-requests.index');
                Route::get('contact-requests/{contactRequest}', [ContactRequestController::class, 'show'])->name('contact-requests.show');
            });
            Route::post('contact-requests/{contactRequest}/close', [ContactRequestController::class, 'close'])
                ->middleware('admin.permission:contact_requests.respond')->name('contact-requests.close');

            Route::middleware('admin.permission:security.view')->group(function () {
                Route::get('security', [SecurityEventController::class, 'index'])->name('security.index');
            });
            Route::post('security/{securityEvent}/resolve', [SecurityEventController::class, 'resolve'])
                ->middleware('admin.permission:security.resolve')->name('security.resolve');

            Route::get('audit', [AuditLogController::class, 'index'])
                ->middleware('admin.permission:audit.view')->name('audit.index');

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
            Route::post('admins/{admin}/suspend', [AdminManagementController::class, 'suspend'])
                ->middleware('admin.permission:admins.update')->name('admins.suspend');
            Route::post('admins/{admin}/activate', [AdminManagementController::class, 'activate'])
                ->middleware('admin.permission:admins.update')->name('admins.activate');
        });
    });
});
