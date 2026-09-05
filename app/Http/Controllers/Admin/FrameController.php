<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Frame;
use App\Models\FramePlacement;
use App\Services\FramePlacementDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FrameController extends Controller
{
    public function index(Request $request)
    {
        $frames = $request->user()
            ->frames()
            ->with('placements')
            ->orderBy('name')
            ->get();

        return view('admin.frames.index', compact('frames'));
    }

    public function create()
    {
        return view('admin.frames.create');
    }

    public function store(
        Request $request,
        FramePlacementDetector $placementDetector
    ) {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'image' => ['required', 'file', 'mimes:png', 'max:10240'],
        ]);

        $image = $request->file('image');
        $realPath = $image->getRealPath();

        $contents = file_get_contents($realPath);
        if ($contents === false) {
            return back()
                ->withErrors(['image' => 'PNG tidak dapat dibaca.'])
                ->withInput();
        }

        $sha256 = hash('sha256', $contents);
        $imageInfo = getimagesize($realPath);

        if (
            $imageInfo === false ||
            ($imageInfo[2] ?? null) !== IMAGETYPE_PNG
        ) {
            return back()
                ->withErrors([
                    'image' => 'File harus berupa PNG yang valid.',
                ])
                ->withInput();
        }

        $filename = Str::uuid() . '.png';

        $path = $image->storeAs(
            'frames',
            $filename,
            'public'
        );

        try {
            $frame = DB::transaction(function () use (
                $request,
                $validated,
                $path,
                $sha256,
                $imageInfo,
                $placementDetector
            ) {
                $frame = Frame::create([
                    'user_id' => $request->user()->id,
                    'name' => $validated['name'],
                    'category' => $validated['category'] ?? null,
                    'image_path' => $path,
                    'version' => 1,
                    'sha256' => $sha256,
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1],
                    'status' => 'ACTIVE',
                ]);

                /*
                 * The detector is already proven independently. Store its
                 * exact output explicitly through FramePlacement::create()
                 * so this controller does not depend on relationship
                 * mass-assignment behavior or an intermediate transformation.
                 */
                $placements = $placementDetector->detect(
                    \Storage::disk('public')->path($path)
                );

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

                Log::info('Automatic frame placements created.', [
                    'frame_id' => $frame->id,
                    'placement_count' => count($placements),
                    'placements' => $placements,
                ]);

                return $frame;
            });

            $placementCount = $frame->placements()->count();

            if ($placementCount === 0) {
                return redirect()
                    ->route('admin.frames.index')
                    ->with(
                        'warning',
                        'Frame berhasil diupload, tetapi lubang foto tidak terdeteksi otomatis.'
                    );
            }

            return redirect()
                ->route('admin.frames.index')
                ->with(
                    'success',
                    "Frame berhasil diupload. {$placementCount} placement foto terdeteksi otomatis."
                );
        } catch (\Throwable $e) {
            /*
             * Keep the upload safe: remove the stored file if database
             * creation/detection fails, then return a useful error.
             */
            try {
                \Storage::disk('public')->delete($path);
            } catch (\Throwable $cleanupException) {
                Log::warning(
                    'Failed to remove frame file after upload failure.',
                    [
                        'path' => $path,
                        'error' => $cleanupException->getMessage(),
                    ]
                );
            }

            Log::error('Automatic frame placement failed.', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            return back()
                ->withErrors([
                    'image' =>
                        'Frame gagal diproses: ' . $e->getMessage(),
                ])
                ->withInput();
        }
    }
}
