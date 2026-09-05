<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\Auth\WebAuthController;
use App\Http\Controllers\Admin\FrameController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/gallery/{session_code}', [GalleryController::class, 'show']);

Route::get(
    '/gallery/{session_code}/download-result',
    [GalleryController::class, 'downloadResult']
)->name('gallery.download.result');

Route::get(
    '/gallery/{session_code}/download',
    [GalleryController::class, 'downloadZip']
)->name('gallery.download');


Route::get(
    '/login',
    [WebAuthController::class, 'showLogin']
)->name('login');

Route::post(
    '/login',
    [WebAuthController::class, 'login']
)->name('login.submit');

Route::post(
    '/logout',
    [WebAuthController::class, 'logout']
)->middleware('auth')
->name('logout');


Route::middleware('auth')->group(function () {

    Route::get('/admin', function () {
        return redirect()->route('admin.frames.index');
    })->name('admin');

    Route::get(
        '/admin/frames',
        [FrameController::class, 'index']
    )->name('admin.frames.index');

    Route::get(
        '/admin/frames/create',
        [FrameController::class, 'create']
    )->name('admin.frames.create');

    Route::post(
        '/admin/frames',
        [FrameController::class, 'store']
    )->name('admin.frames.store');
});