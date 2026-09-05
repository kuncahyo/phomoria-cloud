<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Models\Device;
use App\Models\PhotoSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class SessionUploadController extends Controller
{

    public function upload(Request $request)
    {
        $request->validate([

            'device_uuid' => 'required',

            'frame_name' => 'required'

        ]);

        if (count($request->allFiles()) == 0) {

            return response()->json([

                "success" => false,

                "message" => "Tidak ada foto yang diupload."

            ], 422);

        }

        $device = Device::where(
                'device_uuid',
                $request->device_uuid
                )->first();

        if (!$device) {

        return response()->json([

                "success" => false,

                "message" => "Device tidak terdaftar."

        ],404);

        }

        DB::beginTransaction();

        try {

            $today =
                Carbon::now();

            $date =
                $today->format("Ymd");

            $countToday =
                    PhotoSession::whereDate(
                            "created_at",
                            $today
                    )->count() + 1;

            $sessionCode =
                    sprintf(
                            "PHO-%s-%06d",
                            $date,
                            $countToday
                    );

            $session =
                    PhotoSession::create([

                        "user_id" =>
                                $request
                                        ->user()
                                        ->id,

                        "device_id"=>
                                $device->id,

                        "session_code" =>
                                $sessionCode,

                        "frame_name" =>
                                $request
                                        ->frame_name,

                        "photo_count" =>
                                count(
                                    $request->allFiles()
                                ),

                        "status" =>
                                "UPLOADING",

                        "expired_at" =>
                                now()->addDays(30)

                    ]);

            $folder =
                    "sessions/"
                    .$today->format("Y")
                    ."/"
                    .$today->format("m")
                    ."/"
                    .$today->format("d")
                    ."/"
                    .$sessionCode;

            $allFiles =
                array_values(
                    $request->allFiles()
                );

            foreach($allFiles as $index=>$file){

                $extension =
                        $file
                                ->getClientOriginalExtension();

                $filename =
                        $index==0
                        ? "result.".$extension
                        : "photo".$index.".".$extension;

                Storage::disk("public")
                        ->putFileAs(
                                $folder,
                                $file,
                                $filename
                        );

                Photo::create([
                    "photo_session_id" => $session->id,
                    "filename" => $filename,
                    "path" => $folder . "/" . $filename,
                    "original_name" => $file->getClientOriginalName(),
                    "is_result" => $index == 0,
                    "sort_order" => $index,
                    "file_size" => $file->getSize()
                ]);

            }

            $session->status =
                    "COMPLETED";

            $session->save();

            DB::commit();

            return response()->json([

                "success"=>true,

                "session"=>[

                    "id"=>$session->id,

                    "session_code"=>$session->session_code,

                    "gallery_url"=>
                            url(
                                "/gallery/"
                                .$session->session_code
                            )

                ]

            ]);

        }catch(\Exception $e){

            DB::rollBack();

            return response()->json([

                "success"=>false,

                "message"=>$e->getMessage()

            ],500);

        }

    }

}