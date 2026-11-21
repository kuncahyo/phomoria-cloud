<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Photo;
use App\Models\PhotoSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SessionUploadController extends Controller
{
    /**
     * Reserve a gallery session before transferring its files.
     * The returned gallery URL is usable as soon as the reservation exists.
     */
    public function reserve(Request $request)
    {
        $validated = $request->validate([
            'device_uuid' => 'required|string|max:100',
            'client_session_id' => 'required|string|size:36',
            'frame_name' => 'nullable|string|max:255',
            'voucher_token' => 'nullable|string|max:255',
            'assets' => 'required|array|min:1',
            'assets.*.asset_key' => ['required', 'string', 'max:100', 'regex:/^(result|gif|live_photo|photo_\d+)$/'],
            'assets.*.asset_type' => 'required|string|in:result,photo,gif,live_photo',
            'assets.*.filename' => 'required|string|max:255',
            'assets.*.sort_order' => 'nullable|integer|min:0',
            'assets.*.size' => 'nullable|integer|min:0',
        ]);

        $device = $request->user()->devices()
            ->where('device_uuid', $validated['device_uuid'])
            ->first();

        if (!$device) {
            return response()->json(['success' => false, 'message' => 'Device tidak ditemukan.'], 404);
        }

        $assets = collect($validated['assets']);
        $keys = $assets->pluck('asset_key');
        if ($keys->unique()->count() !== $keys->count()) {
            throw ValidationException::withMessages(['assets' => 'Asset key tidak boleh duplikat.']);
        }
        if (!$assets->contains(fn ($asset) => $asset['asset_key'] === 'result'
            && $asset['asset_type'] === 'result')) {
            throw ValidationException::withMessages(['assets' => 'Manifest wajib menyertakan result.png.']);
        }

        foreach ($assets as $asset) {
            $key = $asset['asset_key'];
            $expectedType = $key === 'result' ? 'result'
                : ($key === 'gif' ? 'gif'
                : ($key === 'live_photo' ? 'live_photo' : 'photo'));
            if ($asset['asset_type'] !== $expectedType) {
                throw ValidationException::withMessages([
                    'assets' => "Jenis aset tidak cocok dengan asset key: {$key}.",
                ]);
            }
        }

        // Idempotent reservation: retries for the same device/session return
        // the original gallery URL instead of creating another gallery.
        $existing = PhotoSession::where('client_session_id', $validated['client_session_id'])\n            ->where('device_id', $device->id)\n            ->first();
        if ($existing) {
            if ((int) $existing->device_id !== (int) $device->id
                || (int) $existing->user_id !== (int) $request->user()->id) {
                return response()->json(['success' => false, 'message' => 'Client session ID sudah digunakan.'], 409);
            }
            return $this->reservationResponse($existing);
        }

        $session = DB::transaction(function () use ($request, $device, $validated, $assets) {
            $today = now();
            $date = $today->format('Ymd');
            $countToday = PhotoSession::whereDate('created_at', $today->toDateString())
                ->lockForUpdate()->count() + 1;

            do {
                $sessionCode = sprintf('PHO-%s-%06d', $date, $countToday++);
            } while (PhotoSession::where('session_code', $sessionCode)->exists());

            return PhotoSession::create([
                'user_id' => $request->user()->id,
                'device_id' => $device->id,
                'session_code' => $sessionCode,
                'client_session_id' => $validated['client_session_id'],
                'frame_name' => $validated['frame_name'] ?? '',
                'photo_count' => $assets->where('asset_type', 'photo')->count(),
                'expected_assets' => $assets->pluck('asset_key')->values()->all(),
                'status' => 'UPLOADING',
                'expired_at' => now()->addDays(30),
            ]);
        });

        return $this->reservationResponse($session);
    }

    /**
     * Receive one asset. Repeated requests for an already stored key are safe.
     * GIF and Live Photo may be sent after the session has become COMPLETED.
     */
    public function uploadAsset(Request $request)
    {
        $validated = $request->validate([
            'device_uuid' => 'required|string|max:100',
            'client_session_id' => 'required|string|size:36',
            'asset_key' => ['required', 'string', 'max:100', 'regex:/^(result|gif|live_photo|photo_\d+)$/'],
            'file' => 'required|file|max:102400',
        ]);

        $device = $request->user()->devices()
            ->where('device_uuid', $validated['device_uuid'])
            ->first();
        if (!$device) {
            return response()->json(['success' => false, 'message' => 'Device tidak ditemukan.'], 404);
        }

        $session = PhotoSession::where('client_session_id', $validated['client_session_id'])
            ->where('device_id', $device->id)
            ->where('user_id', $request->user()->id)
            ->first();
        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Reservasi sesi tidak ditemukan.'], 404);
        }

        $key = $validated['asset_key'];
        [$type, $filename, $sortOrder, $isResult, $allowedMimes] = $this->assetDetails($key);
        $file = $request->file('file');
        if (!$file->isValid() || !in_array($file->getMimeType(), $allowedMimes, true)) {
            throw ValidationException::withMessages(['file' => 'Format file tidak sesuai dengan jenis aset.']);
        }

        $existing = Photo::where('photo_session_id', $session->id)
            ->where('asset_key', $key)->first();
        if ($existing) {
            $existingPath = $this->sessionFolder($session) . '/' . $existing->filename;
            if (Storage::disk('public')->exists($existingPath)) {
                return response()->json(['success' => true, 'already_uploaded' => true]);
            }
            // Repair a stale DB record if the file was removed outside the app.
            $existing->delete();
        }

        $folder = $this->sessionFolder($session);
        $stored = Storage::disk('public')->putFileAs($folder, $file, $filename);
        if (!$stored) {
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan file aset.'], 500);
        }

        try {
            DB::transaction(function () use ($session, $key, $type, $filename, $file, $sortOrder, $isResult) {
                Photo::create([
                    'photo_session_id' => $session->id,
                    'asset_key' => $key,
                    'asset_type' => $type,
                    'filename' => $filename,
                    'original_name' => $file->getClientOriginalName(),
                    'is_result' => $isResult,
                    'sort_order' => $sortOrder,
                    'file_size' => $file->getSize(),
                ]);

                $expected = $session->expected_assets ?? [];
                $uploadedKeys = $session->photos()->pluck('asset_key')->filter()->all();
                $allExpectedUploaded = count(array_diff($expected, $uploadedKeys)) === 0;
                $session->status = $allExpectedUploaded ? 'COMPLETED' : 'UPLOADING';
                $session->save();
            });
        } catch (\Throwable $exception) {
            // Do not delete the shared destination when a concurrent retry has
            // already committed the same asset key and file path.
            $duplicate = Photo::where('photo_session_id', $session->id)
                ->where('asset_key', $key)->exists();
            if ($duplicate) {
                return response()->json(['success' => true, 'already_uploaded' => true]);
            }
            Storage::disk('public')->delete($stored);
            throw $exception;
        }

        return response()->json(['success' => true, 'already_uploaded' => false]);
    }

    /**
     * Legacy all-at-once endpoint retained for older desktop versions.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'device_uuid' => 'required',
            'frame_name' => 'required',
        ]);

        if (count($request->allFiles()) === 0) {
            return response()->json(['success' => false, 'message' => 'Tidak ada foto yang diupload.'], 422);
        }

        $device = Device::where('device_uuid', $request->device_uuid)->first();
        if (!$device) {
            return response()->json(['success' => false, 'message' => 'Device tidak terdaftar.'], 404);
        }

        DB::beginTransaction();
        try {
            $today = now();
            $countToday = PhotoSession::whereDate('created_at', $today->toDateString())->count() + 1;
            $sessionCode = sprintf('PHO-%s-%06d', $today->format('Ymd'), $countToday);
            $files = array_values($request->allFiles());

            $session = PhotoSession::create([
                'user_id' => $request->user()->id,
                'device_id' => $device->id,
                'session_code' => $sessionCode,
                'frame_name' => $request->frame_name,
                'photo_count' => max(0, count($files) - 1),
                'status' => 'UPLOADING',
                'expired_at' => now()->addDays(30),
            ]);

            $folder = $this->sessionFolder($session);
            foreach ($files as $index => $file) {
                $isResult = $index === 0;
                $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                $filename = $isResult ? 'result.' . $extension : 'photo' . $index . '.' . $extension;
                Storage::disk('public')->putFileAs($folder, $file, $filename);

                Photo::create([
                    'photo_session_id' => $session->id,
                    'asset_key' => $isResult ? 'result' : 'photo_' . $index,
                    'asset_type' => $isResult ? 'result' : 'photo',
                    'filename' => $filename,
                    'original_name' => $file->getClientOriginalName(),
                    'is_result' => $isResult,
                    'sort_order' => $index,
                    'file_size' => $file->getSize(),
                ]);
            }

            $session->status = 'COMPLETED';
            $session->save();
            DB::commit();

            return $this->reservationResponse($session);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 500);
        }
    }

    private function assetDetails(string $key): array
    {
        if ($key === 'result') {
            return ['result', 'result.png', 0, true, ['image/png']];
        }
        if ($key === 'gif') {
            return ['gif', 'animation.gif', 9000, false, ['image/gif']];
        }
        if ($key === 'live_photo') {
            return ['live_photo', 'live_photo.mp4', 10000, false, ['video/mp4']];
        }
        if (preg_match('/^photo_(\d+)$/', $key, $matches)) {
            return ['photo', 'photo' . $matches[1] . '.jpg', (int) $matches[1], false,
                ['image/jpeg', 'image/pjpeg']];
        }
        throw ValidationException::withMessages(['asset_key' => 'Asset key tidak dikenali.']);
    }

    private function sessionFolder(PhotoSession $session): string
    {
        return 'sessions/' . $session->created_at->format('Y') . '/'
            . $session->created_at->format('m') . '/' . $session->created_at->format('d')
            . '/' . $session->session_code;
    }

    private function reservationResponse(PhotoSession $session)
    {
        return response()->json([
            'success' => true,
            'session' => [
                'id' => (string) $session->id,
                'session_code' => $session->session_code,
                'gallery_url' => url('/gallery/' . $session->session_code),
            ],
        ]);
    }
}
