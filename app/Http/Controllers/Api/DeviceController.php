<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;

class DeviceController extends Controller
{

    public function register(Request $request)
    {

        $request->validate([

            'device_uuid'=>'required',

            'computer_name'=>'required',

            'windows_user'=>'required',

            'operating_system'=>'required',

            'java_version'=>'required',

            'app_version'=>'required'

        ]);

        $device =
                Device::updateOrCreate(

                    [

                        'device_uuid'=>$request->device_uuid

                    ],

                    [

                        'user_id'=>$request->user()->id,

                        'computer_name'=>$request->computer_name,

                        'windows_user'=>$request->windows_user,

                        'operating_system'=>$request->operating_system,

                        'java_version'=>$request->java_version,

                        'app_version'=>$request->app_version,

                        'last_online'=>now(),

                        'status'=>'ACTIVE'

                    ]

                );

        return response()->json([

            "success"=>true,

            "device_id"=>$device->id

        ]);

    }

}