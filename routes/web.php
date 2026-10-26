<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\Auth\WebAuthController;
use App\Http\Controllers\Admin\FrameAssignmentController as FrameController;
use App\Http\Controllers\Admin\DevicePricingController;
use App\Http\Controllers\Admin\DokuPaymentController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/download', function () {
    return view('download');
})->name('download');

Route::get(
    '/download/phomoria',
    [DownloadController::class, 'phomoria']
)->name('download.phomoria');

Route::get(
    '/download/zadig',
    [DownloadController::class, 'zadig']
)->name('download.zadig');

// Daftar seluruh sesi harus didefinisikan sebelum route detail dinamis.
Route::get('/gallery', [GalleryController::class, 'index'])
    ->name('gallery.index');

// Public session URLs use a non-reversible device fingerprint, date, and daily sequence.
Route::get('/gallery/{device_hash}/{session_date}/{sequence}/status', [GalleryController::class, 'statusByPublicId'])
    ->where(['device_hash' => '[a-f0-9]{8}', 'session_date' => '[0-9]{8}', 'sequence' => '[0-9]{6}']);
Route::get('/gallery/{device_hash}/{session_date}/{sequence}/download-result', [GalleryController::class, 'downloadResultByPublicId'])
    ->where(['device_hash' => '[a-f0-9]{8}', 'session_date' => '[0-9]{8}', 'sequence' => '[0-9]{6}']);
Route::get('/gallery/{device_hash}/{session_date}/{sequence}/download', [GalleryController::class, 'downloadZipByPublicId'])
    ->where(['device_hash' => '[a-f0-9]{8}', 'session_date' => '[0-9]{8}', 'sequence' => '[0-9]{6}']);
Route::get('/gallery/{device_hash}/{session_date}/{sequence}', [GalleryController::class, 'showByPublicId'])
    ->where(['device_hash' => '[a-f0-9]{8}', 'session_date' => '[0-9]{8}', 'sequence' => '[0-9]{6}']);

Route::get('/gallery/{session_code}/status', [GalleryController::class, 'status'])->name('gallery.status');
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
        '/admin/devices/pricing',
        [DevicePricingController::class, 'index']
    )->name('admin.devices.pricing');

    Route::put(
        '/admin/devices/{device}/pricing',
        [DevicePricingController::class, 'update']
    )->name('admin.devices.pricing.update');

    Route::get(
        '/admin/frames',
        [FrameController::class, 'index']
    )->name('admin.frames.index');

    Route::get(
        '/admin/frames/select',
        [FrameController::class, 'selectDevices']
    )->name('admin.frames.select');

    Route::post(
        '/admin/frames/select',
        [FrameController::class, 'saveDeviceFrames']
    )->name('admin.frames.select.save');

    Route::get(
        '/admin/frames/create',
        [FrameController::class, 'create']
    )->name('admin.frames.create');

    Route::post(
        '/admin/frames',
        [FrameController::class, 'store']
    )->name('admin.frames.store');

    Route::get(
        '/admin/frames/{frame}/edit',
        [FrameController::class, 'edit']
    )->name('admin.frames.edit');

    Route::put(
        '/admin/frames/{frame}',
        [FrameController::class, 'update']
    )->name('admin.frames.update');

    Route::delete(
        '/admin/frames/{frame}',
        [FrameController::class, 'destroy']
    )->name('admin.frames.destroy');

// DOKU Checkout
    Route::get('/admin/doku', [DokuPaymentController::class, 'index'])
        ->name('admin.doku.index');

    Route::post('/admin/doku', [DokuPaymentController::class, 'save'])
        ->name('admin.doku.save');

    Route::post('/admin/doku/test/generate', [DokuPaymentController::class, 'testGenerate'])
        ->name('admin.doku.test.generate');

    Route::get('/admin/doku/test/{publicId}', [DokuPaymentController::class, 'showTest'])
        ->name('admin.doku.test');

    Route::get('/admin/doku/test/{publicId}/status', [DokuPaymentController::class, 'status'])
        ->middleware('throttle:1,1')
        ->name('admin.doku.status');
});
