<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function phomoria()
    {
        $filename = config('downloads.phomoria_file');

        if (!$filename) {
            abort(404, 'File Phomoria belum dikonfigurasi.');
        }

        $path = 'downloads/' . $filename;

        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'Installer Phomoria belum tersedia.');
        }

        return Storage::disk('public')->download(
            $path,
            $filename,
            [
                'Content-Type' => 'application/octet-stream',
            ]
        );
    }

    public function zadig()
    {
        $filename = config('downloads.zadig_file');

        if (!$filename) {
            abort(404, 'File Zadig belum dikonfigurasi.');
        }

        $path = 'downloads/' . $filename;

        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'Installer Zadig belum tersedia.');
        }

        return Storage::disk('public')->download(
            $path,
            $filename,
            [
                'Content-Type' => 'application/octet-stream',
            ]
        );
    }
}