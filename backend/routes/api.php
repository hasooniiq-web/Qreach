<?php

use App\Http\Controllers\Api\V1\Auth\OtpAuthController;
use App\Http\Controllers\Api\V1\ContactRequestController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ProfileController;
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

        Route::delete('vehicles/{vehicle}/tag', [TagController::class, 'destroy'])->name('vehicles.tag.destroy');

        Route::post('tags/activate', [TagController::class, 'activate'])
            ->middleware('throttle:qr-activation')->name('tags.activate');
        Route::get('tags/{token}', [TagController::class, 'show'])->name('tags.show');

        Route::put('devices/push-token', [DeviceController::class, 'updatePushToken'])->name('devices.push-token');

        Route::get('contact-requests', [ContactRequestController::class, 'index'])->name('contact-requests.index');
        Route::get('contact-requests/{uuid}', [ContactRequestController::class, 'show'])->name('contact-requests.show');
        Route::get('contact-requests/{uuid}/messages/{message}/image', [ContactRequestController::class, 'messageImage'])->name('contact-requests.message-image');
        Route::post('contact-requests/{uuid}/reply', [ContactRequestController::class, 'reply'])
            ->middleware('throttle:30,1')->name('contact-requests.reply');
        Route::put('contact-requests/{uuid}/read', [ContactRequestController::class, 'markRead'])->name('contact-requests.read');
        Route::put('contact-requests/{uuid}/responded', [ContactRequestController::class, 'markResponded'])->name('contact-requests.responded');

        Route::get('home', [HomeController::class, 'show'])->name('home');
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::put('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::put('notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

        Route::get('subscription', [SubscriptionController::class, 'show'])->name('subscription.show');
        Route::post('subscription/upgrade-requests', [SubscriptionController::class, 'requestUpgrade'])
            ->middleware('throttle:5,1')->name('subscription.upgrade');

        Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('profile', [ProfileController::class, 'updateName'])->name('profile.name');
        Route::get('profile/avatar', [ProfileController::class, 'avatar'])->name('profile.avatar');
        Route::post('profile/avatar', [ProfileController::class, 'storeAvatar'])->name('profile.avatar.store');
        Route::delete('profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');
        Route::get('profile/cover', [ProfileController::class, 'cover'])->name('profile.cover');
        Route::post('profile/cover', [ProfileController::class, 'storeCover'])->name('profile.cover.store');
        Route::delete('profile/cover', [ProfileController::class, 'destroyCover'])->name('profile.cover.destroy');
        Route::put('profile/contact', [ProfileController::class, 'updateContact'])->name('profile.contact');
        Route::put('profile/visibility', [ProfileController::class, 'updateVisibility'])->name('profile.visibility');
        Route::post('profile/methods', [ProfileController::class, 'storeMethod'])->name('profile.methods.store');
        Route::put('profile/methods/{method}', [ProfileController::class, 'updateMethod'])->name('profile.methods.update');
        Route::delete('profile/methods/{method}', [ProfileController::class, 'destroyMethod'])->name('profile.methods.destroy');
        Route::put('profile/emergency', [ProfileController::class, 'updateEmergency'])->name('profile.emergency');
        Route::delete('profile/emergency', [ProfileController::class, 'destroyEmergency'])->name('profile.emergency.destroy');
    });
});
