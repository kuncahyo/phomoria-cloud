<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Models\Device;
use App\Models\PhotoSession;
use App\Models\PhotoSessionAsset;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;

class SessionUploadController extends Controller
{
    public function reserve(Request $request)
    {
        $data = $request->validate([
            'device_uuid' => ['required', 'uuid'],
            'client_session_id' => ['required', 'uuid'],
            'frame_name' => ['required', 'string', 'max:255'],
            'assets' => ['required', 'array', 'min:1'],
            'assets.*.asset_key' => ['required', 'string', 'max:100'],
            'assets.*.asset_type' => ['required', 'in:result,gif,live_photo,photo'],
            'assets.*.filename' => ['required', 'string', 'max:255'],
            'assets.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'assets.*.size' => ['nullable', 'integer', 'min:0'],
        ]);

        $device = Device::where('device_uuid', $data['device_uuid'])
            ->where('user_id', $request->user()->id)->first();
        if (!$device) return response()->json(['success' => false, 'message' => 'Device tidak terdaftar untuk akun ini.'], 404);

        $keys = array_column($data['assets'], 'asset_key');
        if (count($keys) !== count(array_unique($keys)) ||
            !collect($data['assets'])->contains(fn ($a) => $a['asset_type'] === 'result')) {
            return response()->json(['success' => false, 'message' => 'Manifest aset tidak valid atau tidak memiliki result.'], 422);
        }

        $session = DB::transaction(function () use ($device, $data) {
            // Serialize daily sequence allocation for this device. Different
            // devices have independent sequences and cannot collide.
            $lockedDevice = Device::whereKey($device->id)->lockForUpdate()->firstOrFail();
            $session = PhotoSession::where('device_id', $lockedDevice->id)
                ->where('client_session_id', $data['client_session_id'])->lockForUpdate()->first();

            if (!$session) {
                $now = now();
                $number = PhotoSession::where('device_id', $lockedDevice->id)
                    ->whereDate('created_at', $now->toDateString())->count() + 1;
                $deviceHash = substr(hash('sha256', strtolower($lockedDevice->device_uuid)), 0, 8);
                $session = PhotoSession::create([
                    'user_id' => $lockedDevice->user_id,
                    'device_id' => $lockedDevice->id,
                    'client_session_id' => $data['client_session_id'],
                    'session_code' => sprintf('%s-%s-%06d', $deviceHash, $now->format('Ymd'), $number),
                    'frame_name' => $data['frame_name'],
                    'photo_count' => collect($data['assets'])->where('asset_type', 'photo')->count(),
                    'status' => 'UPLOADING',
                    'expired_at' => now()->addDays(30),
                ]);
            }

            foreach ($data['assets'] as $asset) {
                PhotoSessionAsset::firstOrCreate(
                    ['photo_session_id' => $session->id, 'asset_key' => $asset['asset_key']],
                    [
                        'asset_type' => $asset['asset_type'],
                        'filename' => basename($asset['filename']),
                        'sort_order' => $asset['sort_order'] ?? 0,
                        'expected_size' => $asset['size'] ?? null,
                        'status' => 'PENDING',
                    ]
                );
            }
            return $session->load('assets');
        });

        return response()->json(['success' => true, 'session' => [
            'id' => $session->id,
            'session_code' => $session->session_code,
            'gallery_url' => $this->galleryUrlForSession($session),
            'assets' => $session->assets->map(fn ($a) => [
                'asset_key' => $a->asset_key, 'asset_type' => $a->asset_type,
                'filename' => $a->filename, 'status' => $a->status,
            ]),
        ]]);
    }

    public function uploadAsset(Request $request)
    {
        $data = $request->validate([
            'device_uuid' => ['required', 'uuid'],
            'client_session_id' => ['required', 'uuid'],
            'asset_key' => ['required', 'string', 'max:100'],
            'file' => ['required', 'file', 'max:102400'],
        ]);
        $device = Device::where('device_uuid', $data['device_uuid'])
            ->where('user_id', $request->user()->id)->firstOrFail();
        $session = PhotoSession::where('device_id', $device->id)
            ->where('client_session_id', $data['client_session_id'])->firstOrFail();
        $asset = PhotoSessionAsset::where('photo_session_id', $session->id)
            ->where('asset_key', $data['asset_key'])->firstOrFail();

        if ($asset->status === 'UPLOADED') {
            return response()->json(['success' => true, 'already_uploaded' => true]);
        }

        $file = $request->file('file');
        if ($asset->expected_size !== null && (int) $file->getSize() !== (int) $asset->expected_size) {
            return response()->json([
                'success' => false,
                'message' => 'Ukuran file tidak sesuai dengan manifest sesi.',
                'expected_size' => (int) $asset->expected_size,
                'received_size' => (int) $file->getSize(),
            ], 422);
        }

        $folder = 'sessions/' . $session->created_at->format('Y') . '/'
            . $session->created_at->format('m') . '/' . $session->created_at->format('d') . '/' . $session->session_code;
        if (!Storage::disk('public')->putFileAs($folder, $file, $asset->filename)) {
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan aset.'], 500);
        }

        DB::transaction(function () use ($asset, $session, $file) {
            // Serialize completion checks per session so simultaneous asset
            // uploads cannot leave the session stuck in UPLOADING.
            $lockedSession = PhotoSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            $locked = PhotoSessionAsset::whereKey($asset->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'UPLOADED') {
                Photo::updateOrCreate(
                    ['photo_session_id' => $lockedSession->id, 'filename' => $locked->filename],
                    [
                        'original_name' => $file->getClientOriginalName(),
                        'is_result' => $locked->asset_type === 'result',
                        'is_live_photo' => $locked->asset_type === 'live_photo',
                        'sort_order' => $locked->sort_order,
                        'file_size' => $file->getSize(),
                    ]
                );
                $locked->status = 'UPLOADED';
                $locked->original_name = $file->getClientOriginalName();
                $locked->file_size = $file->getSize();
                $locked->save();
            }
            $pending = PhotoSessionAsset::where('photo_session_id', $lockedSession->id)
                ->where('status', 'PENDING')->exists();
            $lockedSession->status = $pending ? 'UPLOADING' : 'COMPLETED';
            $lockedSession->save();
        });

        return response()->json(['success' => true, 'already_uploaded' => false]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'device_uuid' => 'required',
            'frame_name' => 'required',
            // Optional only for compatibility with older desktop releases.
            'client_session_id' => 'nullable|uuid',
            'live_photo' => 'nullable|file|mimetypes:video/mp4|max:102400',
            'gif' => 'nullable|file|mimetypes:image/gif|max:51200',
        ]);

        $files = $request->allFiles();
        if (count($files) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada foto yang diupload.',
            ], 422);
        }

        $device = Device::where('device_uuid', $request->device_uuid)->first();
        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device tidak terdaftar.',
            ], 404);
        }

        $clientSessionId = $request->input('client_session_id');

        // A retry after a lost HTTP response returns the original gallery.
        if ($clientSessionId) {
            $existing = PhotoSession::where('device_id', $device->id)
                ->where('client_session_id', $clientSessionId)
                ->first();

            if ($existing && $existing->status === 'COMPLETED') {
                return $this->successResponse($existing);
            }

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesi dengan ID tersebut sedang diproses. Silakan coba lagi.',
                    'session_code' => $existing->session_code,
                ], 409);
            }
        }

        $folder = null;

        DB::beginTransaction();

        try {
            $lockedDevice = Device::whereKey($device->id)->lockForUpdate()->firstOrFail();
            $today = Carbon::now();
            $date = $today->format('Ymd');
            $countToday = PhotoSession::where('device_id', $lockedDevice->id)
                ->whereDate('created_at', $today->toDateString())->count() + 1;
            $deviceHash = substr(hash('sha256', strtolower($lockedDevice->device_uuid)), 0, 8);
            $sessionCode = sprintf('%s-%s-%06d', $deviceHash, $date, $countToday);

            $photoKeys = array_filter(
                array_keys($files),
                fn ($key) => preg_match('/^photo\d+$/', (string) $key)
            );

            $session = PhotoSession::create([
                'user_id' => $request->user()->id,
                'device_id' => $device->id,
                'client_session_id' => $clientSessionId,
                'session_code' => $sessionCode,
                'frame_name' => $request->frame_name,
                'photo_count' => count($photoKeys),
                'status' => 'UPLOADING',
                'expired_at' => now()->addDays(30),
            ]);

            $folder = 'sessions/' . $today->format('Y') . '/' . $today->format('m') . '/' . $today->format('d') . '/' . $sessionCode;

            // Current desktop contract uses named multipart fields. Keep a positional
            // fallback for older clients that sent arbitrary file field names.
            if (isset($files['result'])) {
                $result = $files['result'];
                $this->storePhoto($session, $folder, $result, 'result.' . ($result->getClientOriginalExtension() ?: 'png'), true, false, 0);

                if (isset($files['gif'])) {
                    $gif = $files['gif'];
                    $this->storePhoto($session, $folder, $gif, 'animation.gif', false, false, 9000);
                }
                if (isset($files['live_photo'])) {
                    $livePhoto = $files['live_photo'];
                    $this->storePhoto($session, $folder, $livePhoto, 'live_photo.mp4', false, true, 10000);
                }

                $photoKeys = array_values(array_filter(
                    array_keys($files),
                    fn ($key) => preg_match('/^photo\d+$/', (string) $key)
                ));
                usort($photoKeys, fn ($a, $b) => (int) substr($a, 5) <=> (int) substr($b, 5));

                foreach ($photoKeys as $key) {
                    $index = (int) substr($key, 5);
                    $file = $files[$key];
                    $extension = $file->getClientOriginalExtension() ?: 'jpg';
                    $this->storePhoto($session, $folder, $file, 'photo' . $index . '.' . $extension, false, false, $index);
                }
            } else {
                foreach (array_values($files) as $index => $file) {
                    $extension = $file->getClientOriginalExtension() ?: 'jpg';
                    $filename = $index === 0 ? 'result.' . $extension : 'photo' . $index . '.' . $extension;
                    $this->storePhoto($session, $folder, $file, $filename, $index === 0, false, $index);
                }
                // Legacy clients have no distinct Live Photo field.
            }

            $session->status = 'COMPLETED';
            $session->save();
            DB::commit();

            return $this->successResponse($session);
        } catch (\Throwable $e) {
            DB::rollBack();

            // If a concurrent retry committed the same ID, return its gallery.
            if ($clientSessionId) {
                $committed = PhotoSession::where('device_id', $device->id)
                    ->where('client_session_id', $clientSessionId)
                    ->where('status', 'COMPLETED')
                    ->first();

                if ($committed) {
                    return $this->successResponse($committed);
                }
            }

            if ($folder) {
                Storage::disk('public')->deleteDirectory($folder);
            }

            if ($e instanceof QueryException && $clientSessionId) {
                $conflict = PhotoSession::where('device_id', $device->id)
                    ->where('client_session_id', $clientSessionId)
                    ->first();

                if ($conflict) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Sesi dengan ID tersebut sedang diproses. Silakan coba lagi.',
                        'session_code' => $conflict->session_code,
                    ], 409);
                }
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function galleryUrlForSession(PhotoSession $session): string
    {
        if (preg_match('/^([a-f0-9]{8})-(\\d{8})-(\\d{6})$/', $session->session_code, $matches)) {
            return url('/gallery/' . $matches[1] . '/' . $matches[2] . '/' . $matches[3]);
        }

        // Keep compatibility for historical session codes.
        return url('/gallery/' . $session->session_code);
    }

    private function successResponse(PhotoSession $session)
    {
        return response()->json([
            'success' => true,
            'session' => [
                'id' => $session->id,
                'session_code' => $session->session_code,
                'gallery_url' => $this->galleryUrlForSession($session),
            ],
        ]);
    }

    private function storePhoto(PhotoSession $session, string $folder, $file, string $filename, bool $isResult, bool $isLivePhoto, int $sortOrder): void
    {
        $storedPath = Storage::disk('public')->putFileAs($folder, $file, $filename);
        if ($storedPath === false) {
            throw new \RuntimeException('Gagal menyimpan file upload: ' . $filename);
        }

        Photo::create([
            'photo_session_id' => $session->id,
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'is_result' => $isResult,
            'is_live_photo' => $isLivePhoto,
            'sort_order' => $sortOrder,
            'file_size' => $file->getSize(),
        ]);
    }
}
