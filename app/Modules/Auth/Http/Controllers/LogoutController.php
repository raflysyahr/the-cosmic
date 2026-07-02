<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function destroy(Request $request): JsonResponse
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

}
