<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\KomikcastService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request, KomikcastService $svc)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        return response()->json($svc->login($request->email, $request->password));
    }

    public function register(Request $request, KomikcastService $svc)
    {
        $request->validate([
            'username' => 'required',
            'email' => 'required|email',
            'password' => 'required',
        ]);

        return response()->json($svc->register($request->username, $request->email, $request->password));
    }
}
