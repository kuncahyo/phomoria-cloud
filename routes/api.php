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

});