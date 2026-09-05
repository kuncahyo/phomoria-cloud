<?php

namespace App\Http\Controllers;

use App\Models\PhotoSession;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class GalleryController extends Controller
{
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