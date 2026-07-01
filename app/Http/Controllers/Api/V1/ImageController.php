<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\KomikcastService;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    public function __invoke(Request $request, KomikcastService $svc)
    {
        $url = $request->query('url');

        if (!$url) {
            abort(400);
        }

        return $svc->proxyImage($url);
    }
}
