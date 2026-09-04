<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Frame;
use Illuminate\Http\Request;

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

        $device->frames()->detach($frame->id);

        return response()->json([
            'success' => true,
            'message' => 'Frame berhasil dilepas dari device'
        ]);
    }
}