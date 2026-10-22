<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\User;
use Illuminate\Http\Request;

class DevicePricingController extends Controller
{
    private function targetUser(Request $request): User
    {
        $user = $request->user();

        if (!$user->isSuperAdmin()) {
            return $user;
        }

        $userId = $request->input('user_id');

        if (!$userId) {
            return User::orderBy('name')->firstOrFail();
        }

        return User::findOrFail($userId);
    }

    private function accessibleUsers(Request $request)
    {
        return $request->user()->isSuperAdmin()
            ? User::orderBy('name')->get()
            : collect([$request->user()]);
    }

    public function index(Request $request)
    {
        $targetUser = $this->targetUser($request);
        $devices = $targetUser->devices()
            ->orderBy('computer_name')
            ->get();

        return view('admin.devices.pricing', [
            'devices' => $devices,
            'users' => $this->accessibleUsers($request),
            'targetUser' => $targetUser,
            'isSuperAdmin' => $request->user()->isSuperAdmin(),
        ]);
    }

    public function update(Request $request, Device $device)
    {
        $targetUser = $this->targetUser($request);
        $device = $targetUser->devices()->whereKey($device->id)->firstOrFail();

        $data = $request->validate([
            'session_price' => ['nullable', 'integer', 'min:1'],
        ], [
            'session_price.integer' => 'Harga harus berupa angka bulat dalam rupiah.',
            'session_price.min' => 'Harga minimal Rp1. Kosongkan kolom untuk menonaktifkan harga.',
        ]);

        $device->session_price = $data['session_price'] ?? null;
        $device->save();

        $query = $request->user()->isSuperAdmin()
            ? ['user_id' => $targetUser->id]
            : [];

        return redirect()
            ->route('admin.devices.pricing', $query)
            ->with('success', 'Harga sesi perangkat berhasil diperbarui.');
    }
}
