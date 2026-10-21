<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\PhotoSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class GalleryController extends Controller
{
    /**
     * Display all photo sessions, newest first, with an optional device filter.
     */
    public function index(Request $request)
    {
        $selectedDeviceId = $request->query('device_id');

        $devices = Device::query()
            ->whereHas('photoSessions')
            ->orderBy('computer_name')
            ->get(['id', 'computer_name']);

        $sessions = PhotoSession::query()
            ->with('device')
            ->when(
                $selectedDeviceId,
                fn ($query) => $query->where('device_id', $selectedDeviceId)
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('gallery-index', compact(
            'sessions',
            'devices',
            'selectedDeviceId'
        ));
    }

    public function show($sessionCode)
    {
        $session = PhotoSession::with("photos")
            ->where("session_code", $sessionCode)
            ->firstOrFail();

        return view("gallery", compact("session"));
    }

    public function downloadResult($sessionCode)
    {
        $session = PhotoSession::with("photos")
            ->where("session_code", $sessionCode)
            ->firstOrFail();

        $result = $session->photos
            ->firstWhere("is_result", true);

        if (!$result) {
            abort(404, "Result photo tidak ditemukan.");
        }

        $folder =
            "sessions/" .
            $session->created_at->format("Y") . "/" .
            $session->created_at->format("m") . "/" .
            $session->created_at->format("d") . "/" .
            $session->session_code;

        $path = Storage::disk("public")->path(
            $folder . "/" . $result->filename
        );

        if (!file_exists($path)) {
            abort(404, "File result tidak ditemukan.");
        }

        return response()->download(
            $path,
            "result.png",
            [
                "Content-Type" => "image/png",
            ]
        );
    }

    public function downloadZip($sessionCode)
    {
        $session = PhotoSession::with("photos")
            ->where("session_code", $sessionCode)
            ->firstOrFail();

        $zipName = $session->session_code . ".zip";

        $tempDirectory = storage_path("app/temp");

        if (!file_exists($tempDirectory)) {
            mkdir($tempDirectory, 0777, true);
        }

        $zipPath = $tempDirectory . "/" . $zipName;

        $zip = new ZipArchive();

        if (
            $zip->open(
                $zipPath,
                ZipArchive::CREATE | ZipArchive::OVERWRITE
            ) !== true
        ) {
            abort(500, "Gagal membuat file ZIP.");
        }

        $folder =
            "sessions/" .
            $session->created_at->format("Y") . "/" .
            $session->created_at->format("m") . "/" .
            $session->created_at->format("d") . "/" .
            $session->session_code;

        foreach ($session->photos as $photo) {

            $path = Storage::disk("public")->path(
                $folder . "/" . $photo->filename
            );

            if (!file_exists($path)) {
                continue;
            }

            if ($photo->is_result) {

                $zip->addFile(
                    $path,
                    "Result/result.png"
                );

            } else {

                $zip->addFile(
                    $path,
                    "Originals/" . $photo->filename
                );
            }
        }

        $zip->close();

        return response()
            ->download(
                $zipPath,
                $zipName,
                [
                    "Content-Type" => "application/zip",
                ]
            )
            ->deleteFileAfterSend(true);
    }
}
