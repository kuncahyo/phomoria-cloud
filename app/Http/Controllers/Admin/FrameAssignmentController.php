<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Frame;
use App\Models\FramePlacement;
use App\Models\User;
use App\Services\FramePlacementDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FrameAssignmentController extends Controller
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
        $frames = $targetUser->frames()
            ->with('placements')
            ->orderBy('name')
            ->get();

        return view('admin.frames.index', [
            'frames' => $frames,
            'users' => $this->accessibleUsers($request),
            'targetUser' => $targetUser,
            'isSuperAdmin' => $request->user()->isSuperAdmin(),
        ]);
    }

    public function create(Request $request)
    {
        $targetUser = $request->user()->isSuperAdmin()
            ? ($request->filled('user_id') ? User::findOrFail($request->integer('user_id')) : null)
            : $request->user();

        return view('admin.frames.create', [
            'users' => $this->accessibleUsers($request),
            'targetUser' => $targetUser,
            'isSuperAdmin' => $request->user()->isSuperAdmin(),
        ]);
    }

    public function store(Request $request, FramePlacementDetector $detector)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:standar,split'],
            'image' => ['required', 'file', 'mimes:png', 'max:10240'],
        ];

        if ($request->user()->isSuperAdmin()) {
            $rules['user_id'] = ['required', 'integer', 'exists:users,id'];
        }

        $data = $request->validate($rules);
        $targetUser = $this->targetUser($request);

        $image = $request->file('image');
        $path = $image->storeAs('frames', Str::uuid().'.png', 'public');
        $info = getimagesize($image->getRealPath());

        $frame = Frame::create([
            'user_id' => $targetUser->id,
            'name' => $data['name'],
            'category' => $data['category'],
            'image_path' => $path,
            'version' => 1,
            'sha256' => hash_file('sha256', $image->getRealPath()),
            'width' => $info[0],
            'height' => $info[1],
            'status' => 'ACTIVE',
        ]);

        $placements = $detector->detect(storage_path('app/public/'.$path));

        foreach ($placements as $placement) {
            FramePlacement::create([
                'frame_id' => $frame->id,
                'slot' => (int) $placement['slot'],
                'x' => (int) $placement['x'],
                'y' => (int) $placement['y'],
                'width' => (int) $placement['width'],
                'height' => (int) $placement['height'],
                'rotation' => (float) ($placement['rotation'] ?? 0),
            ]);
        }

        $query = $request->user()->isSuperAdmin()
            ? ['user_id' => $targetUser->id]
            : [];

        return redirect()
            ->route('admin.frames.index', $query)
            ->with('success', 'Frame berhasil diupload. Frame belum diberikan ke device mana pun.');
    }

    public function selectDevices(Request $request)
    {
        $targetUser = $this->targetUser($request);
        $frames = $targetUser->frames()
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->get();
        $devices = $targetUser->devices()
            ->orderBy('computer_name')
            ->get();

        $selectedDevice = $request->integer('device_id');
        $device = $selectedDevice
            ? $devices->firstWhere('id', $selectedDevice)
            : $devices->first();

        $assignedFrameIds = $device
            ? $device->frames()->pluck('frames.id')->map(fn ($id) => (int) $id)->all()
            : [];

        return view('admin.frames.select', [
            'users' => $this->accessibleUsers($request),
            'targetUser' => $targetUser,
            'devices' => $devices,
            'device' => $device,
            'frames' => $frames,
            'assignedFrameIds' => $assignedFrameIds,
            'isSuperAdmin' => $request->user()->isSuperAdmin(),
        ]);
    }

    public function saveDeviceFrames(Request $request)
    {
        $rules = [
            'device_id' => ['required', 'integer'],
            'assignments' => ['nullable', 'array'],
        ];

        if ($request->user()->isSuperAdmin()) {
            $rules['user_id'] = ['required', 'integer', 'exists:users,id'];
        }

        $data = $request->validate($rules);
        $targetUser = $this->targetUser($request);

        $device = $targetUser->devices()->whereKey($data['device_id'])->firstOrFail();
        $frames = $targetUser->frames()->where('status', 'ACTIVE')->get();
        $frameIds = $frames->pluck('id')->map(fn ($id) => (int) $id)->all();

        $selected = collect($data['assignments'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => in_array($id, $frameIds, true))
            ->unique()
            ->values()
            ->all();

        $device->frames()->sync($selected);

        $query = [
            'device_id' => $device->id,
        ];

        if ($request->user()->isSuperAdmin()) {
            $query['user_id'] = $targetUser->id;
        }

        return redirect()
            ->route('admin.frames.select', $query)
            ->with('success', 'Pengaturan frame berhasil disimpan.');
    }
}
