<?php

use App\Http\Controllers\Api\V1\SeriesController as V1SeriesController;
use App\Http\Controllers\Api\V1\GenreController as V1GenreController;
use App\Http\Controllers\Api\V1\AuthController as V1AuthController;
use App\Http\Controllers\Api\V1\ImageController as V1ImageController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/series', [V1SeriesController::class, 'index']);
    Route::get('/series/search', [V1SeriesController::class, 'search']);
    Route::get('/series/trending', [V1SeriesController::class, 'trending']);
    Route::get('/series/{slug}', [V1SeriesController::class, 'show']);
    Route::get('/series/{slug}/chapters', [V1SeriesController::class, 'chapters']);
    Route::get('/series/{slug}/chapters/{index}', [V1SeriesController::class, 'chapterPages']);
    Route::get('/genres', [V1GenreController::class, 'index']);
    Route::post('/auth/login', [V1AuthController::class, 'login']);
    Route::post('/auth/register', [V1AuthController::class, 'register']);
    Route::get('/image', V1ImageController::class);
});
