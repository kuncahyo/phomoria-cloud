<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\SessionUploadController;

Route::prefix("auth")->group(function () {

    Route::post("login", [AuthController::class, "login"]);

    Route::middleware("auth:sanctum")->group(function () {

        Route::get("me", [AuthController::class, "me"]);

        Route::post("logout", [AuthController::class, "logout"]);

    });

});

Route::middleware("auth:sanctum")->group(function () {

    Route::post(
        "device/register",
        [DeviceController::class,"register"]
    );

    Route::post(
        "session/upload",
        [SessionUploadController::class,"upload"]
    );

    Route::get(
        "device/frames",
        [DeviceController::class, "frames"]
    );

    Route::post(
        "device/frames/{frame}",
        [DeviceController::class, "attachFrame"]
    );

    Route::delete(
        "device/frames/{frame}",
        [DeviceController::class, "detachFrame"]
    );

    Route::get(
        "device/config",
        [DeviceController::class, "config"]
    );

});
