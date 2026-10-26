<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\SessionUploadController;
use App\Http\Controllers\Api\DokuWebhookController;
use App\Http\Controllers\Api\PaymentController;

Route::prefix("auth")->group(function () {
    Route::post("login", [AuthController::class, "login"]);
    Route::middleware("auth:sanctum")->group(function () {
        Route::get("me", [AuthController::class, "me"]);
        Route::post("logout", [AuthController::class, "logout"]);
    });
});

Route::middleware("auth:sanctum")->group(function () {
    Route::post("device/register", [DeviceController::class, "register"]);
    Route::put("device/price", [DeviceController::class, "updatePrice"]);
    Route::post("session/reserve", [SessionUploadController::class, "reserve"]);
    Route::post("session/asset", [SessionUploadController::class, "uploadAsset"]);
    Route::post("session/upload", [SessionUploadController::class, "upload"]);
    Route::get("device/frames", [DeviceController::class, "frames"]);
    Route::post("device/frames/{frame}", [DeviceController::class, "attachFrame"]);
    Route::delete("device/frames/{frame}", [DeviceController::class, "detachFrame"]);
    Route::get("device/frames/{frame}/download", [DeviceController::class, "downloadFrame"]);
    Route::get("device/config", [DeviceController::class, "config"]);

    // DOKU Checkout
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::get('/payments/{publicId}', [PaymentController::class, 'show']);
    Route::post('/payments/{publicId}/refresh', [PaymentController::class, 'refresh'])
        ->middleware('throttle:1,1');
});

// Webhook DOKU publik, tanpa auth:sanctum.
Route::post('/webhooks/doku/checkout', [DokuWebhookController::class, 'handle'])
    ->name('api.webhooks.doku.checkout');