<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Services\AuthService;
use Illuminate\Http\JsonResponse;

class LogoutController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function destroy(): JsonResponse
    {
        $this->authService->logout();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
