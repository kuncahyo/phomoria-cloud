<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Frame;
use App\Models\FramePlacement;
use App\Models\User;
use App\Services\FramePlacementDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
            'printer_profile' => 'PROFILE_1',
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
                // New uploads start with a one-to-one mapping. Admin can
                // assign the same source_slot to multiple physical holes.
                'source_slot' => (int) $placement['slot'],
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
            ->with('success', 'Frame berhasil diupload. Periksa dan sesuaikan layout sebelum digunakan.');
    }

    public function edit(Request $request, Frame $frame)
    {
        $targetUser = $this->targetUser($request);
        $frame = $targetUser->frames()
            ->with('placements')
            ->whereKey($frame->id)
            ->firstOrFail();

        $initialPlacements = $frame->placements->map(static function ($placement) {
            return [
                'slot' => (int) $placement->slot,
                'source_slot' => (int) ($placement->source_slot ?? $placement->slot),
                'x' => (int) $placement->x,
                'y' => (int) $placement->y,
                'width' => (int) $placement->width,
                'height' => (int) $placement->height,
                'rotation' => (float) $placement->rotation,
            ];
        })->values();

        return view('admin.frames.edit', [
            'frame' => $frame,
            'initialPlacements' => $initialPlacements,
            'targetUser' => $targetUser,
            'isSuperAdmin' => $request->user()->isSuperAdmin(),
        ]);
    }

    public function update(Request $request, Frame $frame)
    {
        $targetUser = $this->targetUser($request);
        $frame = $targetUser->frames()
            ->whereKey($frame->id)
            ->firstOrFail();

        $data = $request->validate([
            'printer_profile' => ['required', 'in:PROFILE_1,PROFILE_2'],
            'placements' => ['required', 'array', 'min:1', 'max:50'],
            'placements.*.slot' => ['required', 'integer', 'min:1', 'distinct'],
            'placements.*.source_slot' => ['required', 'integer', 'min:1'],
            'placements.*.x' => ['required', 'integer', 'min:0'],
            'placements.*.y' => ['required', 'integer', 'min:0'],
            'placements.*.width' => ['required', 'integer', 'min:1'],
            'placements.*.height' => ['required', 'integer', 'min:1'],
            'placements.*.rotation' => ['required', 'numeric', 'between:-360,360'],
        ]);

        foreach ($data['placements'] as $index => $placement) {
            if (
                $placement['x'] + $placement['width'] > (int) $frame->width ||
                $placement['y'] + $placement['height'] > (int) $frame->height
            ) {
                throw ValidationException::withMessages([
                    "placements.$index.width" =>
                        "Area lubang foto #".($index + 1)." melewati batas gambar frame.",
                ]);
            }
        }

        DB::transaction(function () use ($frame, $data) {
            $frame->placements()->delete();

            foreach ($data['placements'] as $placement) {
                $frame->placements()->create([
                    'slot' => (int) $placement['slot'],
                    'source_slot' => (int) $placement['source_slot'],
                    'x' => (int) $placement['x'],
                    'y' => (int) $placement['y'],
                    'width' => (int) $placement['width'],
                    'height' => (int) $placement['height'],
                    'rotation' => (float) $placement['rotation'],
                ]);
            }

            $frame->printer_profile = $data['printer_profile'];
            $frame->version = ((int) $frame->version) + 1;
            $frame->save();
        });

        $query = $request->user()->isSuperAdmin()
            ? ['user_id' => $targetUser->id]
            : [];

        return redirect()
            ->route('admin.frames.index', $query)
            ->with('success', 'Layout frame berhasil disimpan. Versi frame telah diperbarui.');
    }

    public function destroy(Request $request, Frame $frame)
    {
        $targetUser = $this->targetUser($request);
        $frame = $targetUser->frames()
            ->whereKey($frame->id)
            ->firstOrFail();

        $imagePath = $frame->image_path;

        // Foreign-key ON DELETE CASCADE handles frame placements and device assignments.
        $frame->delete();

        // Keep the PNG if another frame still references the same stored path.
        if (
            $imagePath &&
            !Frame::where('image_path', $imagePath)->exists()
        ) {
            Storage::disk('public')->delete($imagePath);
        }

        $query = $request->user()->isSuperAdmin()
            ? ['user_id' => $targetUser->id]
            : [];

        return redirect()
            ->route('admin.frames.index', $query)
            ->with('success', 'Frame berhasil dihapus.');
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
