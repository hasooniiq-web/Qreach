<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PasswordController;
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
                    'users' => 'المستخدمون', 'vehicles' => 'المركبات', 'qr' => 'رموز QR',
                    'nfc' => 'رموز NFC', 'batches' => 'دفعات الرموز', 'contact-requests' => 'طلبات التواصل',
                    'notifications' => 'التنبيهات والإشعارات', 'reports' => 'التقارير والتحليلات',
                    'security' => 'الأمان', 'audit' => 'سجلات التدقيق', 'admins' => 'المسؤولون والصلاحيات',
                    'settings' => 'الإعدادات',
                ];

                abort_unless(isset($titles[$section]), 404);

                return view('admin.coming-soon', ['title' => $titles[$section]]);
            })->name('coming-soon');
        });
    });
});
