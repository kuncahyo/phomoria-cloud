<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Frame;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FrameController extends Controller
{
    public function index(Request $request)
    {
        $frames = $request->user()
            ->frames()
            ->orderBy('name')
            ->get();

        return view('admin.frames.index', compact('frames'));
    }

    public function create()
    {
        return view('admin.frames.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'image' => ['required', 'file', 'mimes:png', 'max:10240'],
        ]);

        $image = $request->file('image');

        $contents = file_get_contents($image->getRealPath());
        $sha256 = hash('sha256', $contents);

        $imageInfo = getimagesize($image->getRealPath());

        if ($imageInfo === false || $imageInfo[2] !== IMAGETYPE_PNG) {
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

        return redirect()
            ->route('admin.frames.index')
            ->with('success', 'Frame berhasil diupload.');
    }
}