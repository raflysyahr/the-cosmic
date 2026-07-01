<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\KomikcastService;

class GenreController extends Controller
{
    public function index(KomikcastService $svc)
    {
        return response()->json($svc->getGenres());
    }
}
