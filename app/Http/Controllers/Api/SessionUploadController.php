<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Models\Device;
use App\Models\PhotoSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;

class SessionUploadController extends Controller
{
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
            $today = Carbon::now();
            $date = $today->format('Ymd');
            $countToday = PhotoSession::whereDate('created_at', $today)->count() + 1;
            $sessionCode = sprintf('PHO-%s-%06d', $date, $countToday);

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

    private function successResponse(PhotoSession $session)
    {
        return response()->json([
            'success' => true,
            'session' => [
                'id' => $session->id,
                'session_code' => $session->session_code,
                'gallery_url' => url('/gallery/' . $session->session_code),
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
