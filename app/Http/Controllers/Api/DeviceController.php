<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Frame;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DeviceController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'device_uuid' => 'required',
            'computer_name' => 'required',
            'windows_user' => 'required',
            'operating_system' => 'required',
            'java_version' => 'required',
            'app_version' => 'required'
        ]);

        $device = Device::updateOrCreate(
            [
                'device_uuid' => $request->device_uuid
            ],
            [
                'user_id' => $request->user()->id,
                'computer_name' => $request->computer_name,
                'windows_user' => $request->windows_user,
                'operating_system' => $request->operating_system,
                'java_version' => $request->java_version,
                'app_version' => $request->app_version,
                'last_online' => now(),
                'status' => 'ACTIVE'
            ]
        );

        return response()->json([
            'success' => true,
            'device_id' => $device->id
        ]);
    }

    public function config(Request $request)
    {
        $request->validate([
            'device_uuid' => 'required|string',
        ]);

        $device = $request->user()
            ->devices()
            ->where('device_uuid', $request->device_uuid)
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device tidak ditemukan',
            ], 404);
        }

        $frames = $device->frames()
            ->where('frames.status', 'ACTIVE')
            ->with('placements')
            ->orderBy('frames.name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                ],

                'device' => [
                    'id' => $device->id,
                    'device_uuid' => $device->device_uuid,
                    'name' => $device->computer_name,
                    'status' => $device->status,
                ],

                'frames' => $frames,
            ],
        ]);
    }

    public function frames(Request $request)
    {
        $device = Device::where('id', $request->user()->devices()
            ->where('id', $request->device_id)
            ->value('id'))
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device tidak ditemukan'
            ], 404);
        }

        $frames = $device->frames()
            ->where('frames.status', 'ACTIVE')
            ->with('placements')
            ->orderBy('frames.name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'device' => [
                    'id' => $device->id,
                    'name' => $device->computer_name,
                    'status' => $device->status,
                ],
                'frames' => $frames,
            ],
        ]);
    }

    public function attachFrame(Request $request, Frame $frame)
    {
        $device = Device::where('id', $request->device_id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device tidak ditemukan'
            ], 404);
        }

        if ($frame->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Frame bukan milik user ini'
            ], 403);
        }

        if ($frame->status !== 'ACTIVE') {
            return response()->json([
                'success' => false,
                'message' => 'Frame tidak aktif'
            ], 422);
        }

        $device->frames()->syncWithoutDetaching([
            $frame->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Frame berhasil ditambahkan ke device'
        ]);
    }

    public function detachFrame(Request $request, Frame $frame)
    {
        $device = Device::where('id', $request->device_id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device tidak ditemukan'
            ], 404);
        }

        if ($frame->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Frame bukan milik user ini'
            ], 403);
        }

        $device->frames()->detach($frame->id);

        return response()->json([
            'success' => true,
            'message' => 'Frame berhasil dilepas dari device'
        ]);
    }
    public function downloadFrame(Request $request, Frame $frame)
    {
        $request->validate([
            'device_id' => 'required|integer',
        ]);

        $device = $request->user()
            ->devices()
            ->where('id', $request->device_id)
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device tidak ditemukan',
            ], 404);
        }

        if ((int) $frame->user_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Frame bukan milik user ini',
            ], 403);
        }

        $assigned = $device->frames()
            ->where('frames.id', $frame->id)
            ->exists();

        if (!$assigned) {
            return response()->json([
                'success' => false,
                'message' => 'Frame tidak diizinkan untuk device ini',
            ], 403);
        }

        if ($frame->status !== 'ACTIVE') {
            return response()->json([
                'success' => false,
                'message' => 'Frame tidak aktif',
            ], 422);
        }

        if (!Storage::disk('public')->exists($frame->image_path)) {
            return response()->json([
                'success' => false,
                'message' => 'File frame tidak ditemukan',
            ], 404);
        }

        return Storage::disk('public')->download(
            $frame->image_path,
            $frame->id . '.png',
            [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'private, max-age=0',
            ]
        );
    }
}