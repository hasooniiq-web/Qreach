<?php

use App\Http\Controllers\Api\V1\Auth\OtpAuthController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\PublicContactController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\V1\VehicleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('otp/request', [OtpAuthController::class, 'requestOtp'])
            ->middleware('throttle:otp-request')->name('otp.request');
        Route::post('otp/verify', [OtpAuthController::class, 'verifyOtp'])
            ->middleware('throttle:otp-verify')->name('otp.verify');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [OtpAuthController::class, 'logout'])->name('logout');
            Route::post('logout-all', [OtpAuthController::class, 'logoutAllDevices'])->name('logout-all');
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('vehicles', VehicleController::class)->parameters(['vehicles' => 'vehicle']);

        Route::post('tags/activate', [TagController::class, 'activate'])
            ->middleware('throttle:qr-activation')->name('tags.activate');
        Route::get('tags/{token}', [TagController::class, 'show'])->name('tags.show');

        Route::put('devices/push-token', [DeviceController::class, 'updatePushToken'])->name('devices.push-token');

        Route::get('subscription', [SubscriptionController::class, 'show'])->name('subscription.show');
        Route::post('subscription/upgrade-requests', [SubscriptionController::class, 'requestUpgrade'])
            ->middleware('throttle:5,1')->name('subscription.upgrade');

        Route::get('public-contact', [PublicContactController::class, 'show'])->name('public-contact.show');
        Route::put('public-contact', [PublicContactController::class, 'update'])->name('public-contact.update');
    });
});
