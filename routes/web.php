<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GalleryController;

Route::get('/', function () {
    return view('welcome');
});

Route::get(
    "/gallery/{session_code}",
    [GalleryController::class,"show"]
);

Route::get(
    "/gallery/{session_code}/download",
    [GalleryController::class,"downloadZip"]
);