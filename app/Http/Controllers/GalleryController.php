<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\PhotoSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'device_id' => ['nullable', 'integer', 'exists:devices,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $selectedDeviceId = $filters['device_id'] ?? '';
        $from = $filters['from'] ?? '';
        $to = $filters['to'] ?? '';

        $devices = Device::query()
            ->whereHas('photoSessions')
            ->orderBy('computer_name')
            ->get(['id', 'computer_name']);

        $sessions = PhotoSession::query()
            ->with('device')
            ->when($selectedDeviceId !== '', fn ($query) => $query->where('device_id', $selectedDeviceId))
            ->when($from !== '', fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to !== '', fn ($query) => $query->where('created_at', '<=', $to))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('gallery-index', compact(
            'sessions', 'devices', 'selectedDeviceId', 'from', 'to'
        ));
    }

    public function show($sessionCode)
    {
        $session = PhotoSession::with(['photos', 'assets'])->where('session_code', $sessionCode)->firstOrFail();
        return view('gallery', compact('session'));
    }

    public function status($sessionCode)
    {
        $session = PhotoSession::with('assets')->where('session_code', $sessionCode)->firstOrFail();
        return response()->json([
            'status' => $session->status,
            'assets' => $session->assets->map(fn ($asset) => [
                'asset_key' => $asset->asset_key,
                'asset_type' => $asset->asset_type,
                'filename' => $asset->filename,
                'status' => $asset->status,
            ]),
        ]);
    }

    public function downloadResult($sessionCode)
    {
        $session = PhotoSession::with('photos')->where('session_code', $sessionCode)->firstOrFail();
        $result = $session->photos->firstWhere('is_result', true);
        if (!$result) abort(404, 'Result photo tidak ditemukan.');

        $folder = 'sessions/' . $session->created_at->format('Y') . '/'
            . $session->created_at->format('m') . '/' . $session->created_at->format('d') . '/'
            . $session->session_code;
        $path = Storage::disk('public')->path($folder . '/' . $result->filename);
        if (!file_exists($path)) abort(404, 'File result tidak ditemukan.');
        return response()->download($path, 'result.png', ['Content-Type' => 'image/png']);
    }

    public function downloadZip($sessionCode)
    {
        $session = PhotoSession::with('photos')->where('session_code', $sessionCode)->firstOrFail();
        $zipName = $session->session_code . '.zip';
        $tempDirectory = storage_path('app/temp');
        if (!file_exists($tempDirectory)) mkdir($tempDirectory, 0777, true);
        $zipPath = $tempDirectory . '/' . $zipName;
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) abort(500, 'Gagal membuat file ZIP.');

        $folder = 'sessions/' . $session->created_at->format('Y') . '/'
            . $session->created_at->format('m') . '/' . $session->created_at->format('d') . '/'
            . $session->session_code;

        foreach ($session->photos as $photo) {
            $path = Storage::disk('public')->path($folder . '/' . $photo->filename);
            if (!file_exists($path)) continue;

            if ($photo->is_live_photo) {
                $zip->addFile($path, 'Live Photo/live_photo.mp4');
            } elseif ($photo->is_result) {
                $zip->addFile($path, 'Result/result.png');
            } elseif (strtolower(pathinfo($photo->filename, PATHINFO_EXTENSION)) === 'gif') {
                $zip->addFile($path, 'Animation/animation.gif');
            } else {
                $zip->addFile($path, 'Originals/' . $photo->filename);
            }
        }

        $zip->close();
        return response()->download($zipPath, $zipName, ['Content-Type' => 'application/zip'])->deleteFileAfterSend(true);
    }
}
