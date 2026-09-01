<?php

namespace App\Http\Controllers;

use App\Models\PhotoSession;

use ZipArchive;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{

    public function show($sessionCode)
    {

        $session =

            PhotoSession::with("photos")

            ->where(

                "session_code",

                $sessionCode

            )

            ->firstOrFail();

        return view(

            "gallery",

            compact("session")

        );

    }

    public function downloadZip($sessionCode)
    {

        $session =
            PhotoSession::with("photos")
            ->where(
                "session_code",
                $sessionCode
            )
            ->firstOrFail();

        $zipName =
            $session->session_code . ".zip";

        $zipPath =
            storage_path(
                "app/temp/".$zipName
            );

        if(!file_exists(storage_path("app/temp"))){

            mkdir(
                storage_path("app/temp"),
                0777,
                true
            );

        }

        $zip =
            new ZipArchive();

        $zip->open(
            $zipPath,
            ZipArchive::CREATE |
            ZipArchive::OVERWRITE
        );

        foreach($session->photos as $photo){

            $path =
                Storage::disk("public")
                    ->path(
                        $photo->path
                    );

            if(file_exists($path)){

                if($photo->is_result){

                    $zip->addFile(
                        $path,
                        "Result/result.png"
                    );

                }else{

                    $zip->addFile(
                        $path,
                        "Originals/".$photo->filename
                    );

                }

            }

        }

        $zip->close();

        return response()
                ->download($zipPath)
                ->deleteFileAfterSend(true);

    }

}