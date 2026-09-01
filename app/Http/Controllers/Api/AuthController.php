<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where(
            'email',
            $request->email
        )->first();

        if (!$user) {

            return response()->json([
                'success' => false,
                'message' => 'Email tidak ditemukan'
            ], 401);

        }

        if (!Hash::check(
                $request->password,
                $user->password
        )) {

            return response()->json([
                'success' => false,
                'message' => 'Password salah'
            ], 401);

        }

        $token =
            $user
                ->createToken("Desktop")
                ->plainTextToken;

        return response()->json([

            'success' => true,

            'message' => 'Login berhasil',

            'data' => [

                'token' => $token,

                'user' => [

                    'id' => $user->id,

                    'name' => $user->name,

                    'email' => $user->email

                ]

            ]

        ]);

    }

    public function me(Request $request)
    {

        return response()->json([

            "success" => true,

            "data" => [

                "id" => $request->user()->id,

                "name" => $request->user()->name,

                "email" => $request->user()->email

            ]

        ]);

    }

    public function logout(Request $request)
    {

        $request->user()->currentAccessToken()?->delete();

        return response()->json([

            "success" => true,

            "message" => "Logout berhasil"

        ]);

    }

}