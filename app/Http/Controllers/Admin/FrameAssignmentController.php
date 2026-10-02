<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Frame;
use App\Models\FramePlacement;
use App\Services\FramePlacementDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FrameAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $frames = $user->isAdmin()
            ? Frame::with('placements')->orderBy('name')->get()
            : $user->frames()->with('placements')->orderBy('name')->get();

        return view('admin.frames.index', compact('frames'));
    }

    public function create()
    {
        return view('admin.frames.create');
    }

    public function store(Request $request, FramePlacementDetector $detector)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:standar,split'],
            'image' => ['required', 'file', 'mimes:png', 'max:10240'],
        ]);

        $image = $request->file('image');
        $path = $image->storeAs('frames', Str::uuid().'.png', 'public');
        $info = getimagesize($image->getRealPath());

        $frame = Frame::create([
            'user_id' => $request->user()->id,
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

        return redirect()
            ->route('admin.frames.index')
            ->with('success', 'Frame berhasil diupload.');
    }

    public function selectDevices(Request $request)
    {
        $user = $request->user();

        $frames = $user->isAdmin()
            ? Frame::where('status', 'ACTIVE')->orderBy('name')->get()
            : $user->frames()->where('status', 'ACTIVE')->orderBy('name')->get();

        $devices = $user->isAdmin()
            ? Device::orderBy('computer_name')->get()
            : $user->devices()->orderBy('computer_name')->get();

        $assignments = [];

        foreach ($devices as $device) {
            $assignments[$device->id] = $device->frames()
                ->pluck('frames.id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return view('admin.frames.select', [
            'frames' => $frames,
            'devices' => $devices,
            'assignments' => $assignments,
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    public function saveDeviceFrames(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'assignments' => ['nullable', 'array'],
        ]);

        $devices = $user->isAdmin()
            ? Device::all()
            : $user->devices()->get();

        $frames = $user->isAdmin()
            ? Frame::where('status', 'ACTIVE')->get()
            : $user->frames()->where('status', 'ACTIVE')->get();

        $frameIds = $frames->pluck('id')->map(fn ($id) => (int) $id)->all();

        foreach ($devices as $device) {
            $selected = collect($data['assignments'][$device->id] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => in_array($id, $frameIds, true))
                ->unique()
                ->values()
                ->all();

            $device->frames()->detach();

            if (count($selected) > 0) {
                $device->frames()->attach($selected);
            }
        }

        return redirect()
            ->route('admin.frames.select')
            ->with('success', 'Pengaturan frame berhasil disimpan.');
    }
}
