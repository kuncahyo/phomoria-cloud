<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Photo;
use App\Models\PhotoSession;
use App\Models\PhotoSessionAsset;
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
        $devices = Device::query()->whereHas('photoSessions')->orderBy('computer_name')->get(['id', 'computer_name']);
        $sessions = PhotoSession::query()->with('device')
            ->when($selectedDeviceId !== '', fn ($q) => $q->where('device_id', $selectedDeviceId))
            ->when($from !== '', fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to !== '', fn ($q) => $q->where('created_at', '<=', $to))
            ->orderByDesc('created_at')->orderByDesc('id')->paginate(30)->withQueryString();
        return view('gallery-index', compact('sessions', 'devices', 'selectedDeviceId', 'from', 'to'));
    }

    private function publicSessionCode(string $deviceHash, string $sessionDate, string $sequence): string
    {
        return $deviceHash . '-' . $sessionDate . '-' . $sequence;
    }

    public function showByPublicId(string $deviceHash, string $sessionDate, string $sequence) { return $this->show($this->publicSessionCode($deviceHash, $sessionDate, $sequence)); }
    public function statusByPublicId(string $deviceHash, string $sessionDate, string $sequence) { return $this->status($this->publicSessionCode($deviceHash, $sessionDate, $sequence)); }
    public function downloadResultByPublicId(string $deviceHash, string $sessionDate, string $sequence) { return $this->downloadResult($this->publicSessionCode($deviceHash, $sessionDate, $sequence)); }
    public function downloadLivePhotoByPublicId(string $deviceHash, string $sessionDate, string $sequence) { return $this->downloadAsset($this->publicSessionCode($deviceHash, $sessionDate, $sequence), 'live_photo'); }
    public function downloadGifByPublicId(string $deviceHash, string $sessionDate, string $sequence) { return $this->downloadAsset($this->publicSessionCode($deviceHash, $sessionDate, $sequence), 'gif'); }
    public function downloadZipByPublicId(string $deviceHash, string $sessionDate, string $sequence) { return $this->downloadZip($this->publicSessionCode($deviceHash, $sessionDate, $sequence)); }
    public function downloadPhotoByPublicId(string $deviceHash, string $sessionDate, string $sequence, int $photoId) { return $this->downloadPhoto($this->publicSessionCode($deviceHash, $sessionDate, $sequence), $photoId); }

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
                'asset_key' => $asset->asset_key, 'asset_type' => $asset->asset_type,
                'filename' => $asset->filename, 'status' => $asset->status,
            ]),
        ]);
    }

    private function sessionFolder(PhotoSession $session): string
    {
        return 'sessions/' . $session->created_at->format('Y') . '/'
            . $session->created_at->format('m') . '/' . $session->created_at->format('d') . '/'
            . $session->session_code;
    }

    private function uploadedFile(PhotoSession $session, string $type): array
    {
        $asset = $session->assets->firstWhere('asset_type', $type);
        if ($asset && $asset->status === 'UPLOADED') {
            $filename = basename($asset->filename);
        } else {
            $legacy = $session->photos->first(function ($photo) use ($type) {
                return $type === 'result' ? $photo->is_result
                    : ($type === 'live_photo' ? $photo->is_live_photo
                    : strtolower(pathinfo($photo->filename, PATHINFO_EXTENSION)) === 'gif');
            });
            $filename = $legacy ? basename($legacy->filename) : null;
        }
        if (!$filename) abort(404, 'File belum tersedia.');
        $relative = $this->sessionFolder($session) . '/' . $filename;
        $disk = Storage::disk('public');
        if (!$disk->exists($relative)) abort(404, 'File tidak ditemukan.');
        return [$disk->path($relative), $filename];
    }

    public function downloadResult($sessionCode)
    {
        $session = PhotoSession::with(['photos', 'assets'])->where('session_code', $sessionCode)->firstOrFail();
        [$path] = $this->uploadedFile($session, 'result');
        return response()->download($path, 'result.' . (pathinfo($path, PATHINFO_EXTENSION) ?: 'png'));
    }

    public function downloadLivePhoto($sessionCode)
    {
        return $this->downloadAsset($sessionCode, 'live_photo');
    }

    public function downloadGif($sessionCode)
    {
        return $this->downloadAsset($sessionCode, 'gif');
    }

    public function downloadPhoto($sessionCode, int $photoId)
    {
        $session = PhotoSession::with(['photos', 'assets'])->where('session_code', $sessionCode)->firstOrFail();
        $photo = $session->photos->firstWhere('id', $photoId);
        if (!$photo || $photo->is_result || $photo->is_live_photo
            || strtolower(pathinfo($photo->filename, PATHINFO_EXTENSION)) === 'gif') {
            abort(404, 'Foto tidak ditemukan.');
        }

        if ($session->assets->isNotEmpty()) {
            $asset = $session->assets->first(fn ($item) => $item->asset_type === 'photo'
                && $item->filename === $photo->filename && $item->status === 'UPLOADED');
            if (!$asset) abort(404, 'Foto belum tersedia.');
        }

        $filename = basename($photo->filename);
        $relative = $this->sessionFolder($session) . '/' . $filename;
        $disk = Storage::disk('public');
        if (!$disk->exists($relative)) abort(404, 'File foto tidak ditemukan.');

        $downloadName = basename($photo->original_name ?: $filename);
        return response()->download($disk->path($relative), $downloadName);
    }

    private function downloadAsset($sessionCode, string $type)
    {
        $session = PhotoSession::with(['photos', 'assets'])->where('session_code', $sessionCode)->firstOrFail();
        [$path, $filename] = $this->uploadedFile($session, $type);
        $downloadName = $type === 'live_photo' ? 'live-photo.mp4' : 'animation.gif';
        return response()->download($path, $downloadName);
    }

    public function downloadZip($sessionCode)
    {
        $session = PhotoSession::with(['photos', 'assets'])->where('session_code', $sessionCode)->firstOrFail();
        $zipName = $session->session_code . '.zip';
        $tempDirectory = storage_path('app/temp');
        if (!is_dir($tempDirectory)) mkdir($tempDirectory, 0775, true);
        $zipPath = $tempDirectory . '/' . uniqid('gallery-', true) . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) abort(500, 'Gagal membuat file ZIP.');

        $folder = $this->sessionFolder($session);
        $disk = Storage::disk('public');
        $added = [];
        $add = function ($filename, $archiveName) use ($disk, $folder, $zip, &$added) {
            $filename = basename($filename);
            if (isset($added[$filename])) return;
            $relative = $folder . '/' . $filename;
            if ($disk->exists($relative)) {
                $zip->addFile($disk->path($relative), $archiveName);
                $added[$filename] = true;
            }
        };

        foreach ($session->assets->where('status', 'UPLOADED') as $asset) {
            $name = basename($asset->filename);
            $target = match ($asset->asset_type) {
                'result' => 'Result/' . $name,
                'live_photo' => 'Live Photo/' . $name,
                'gif' => 'Animation/' . $name,
                default => 'Originals/' . $name,
            };
            $add($name, $target);
        }
        foreach ($session->photos as $photo) {
            $name = basename($photo->filename);
            if ($photo->is_result) $target = 'Result/' . $name;
            elseif ($photo->is_live_photo) $target = 'Live Photo/' . $name;
            elseif (strtolower(pathinfo($name, PATHINFO_EXTENSION)) === 'gif') $target = 'Animation/' . $name;
            else $target = 'Originals/' . $name;
            $add($name, $target);
        }
        $zip->close();
        if (count($added) === 0) { @unlink($zipPath); abort(404, 'Belum ada file yang bisa diunduh.'); }
        return response()->download($zipPath, $zipName, ['Content-Type' => 'application/zip'])->deleteFileAfterSend(true);
    }
}
