<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;



Route::get('/', function () {
    return Inertia::render('Home');
});

Route::get('/series', function () {
    return Inertia::render('AllComics');
});

Route::get('/series/{slug}', function (string $slug) {
    return Inertia::render('Detail', ['slug' => $slug]);
});

Route::get('/series/{slug}/chapter/{chapterSlug}', function (string $slug, string $chapterSlug) {
    return Inertia::render('Reader', [
        'slug' => $slug,
        'chapterSlug' => $chapterSlug,
    ]);
});

Route::get('/comics/{genre}', function (string $genre) {
    return Inertia::render('GenrePage', ['genre' => $genre]);
});

Route::get('/search', function () {
    return Inertia::render('Search');
});

Route::get('/bookmarks', function () {
    return Inertia::render('Bookmarks');
});

Route::get('/about', function () {
    return Inertia::render('About');
});

Route::get('/privacy', function () {
    return Inertia::render('Privacy');
});

Route::get('/dmca', function () {
    return Inertia::render('DMCA');
});

Route::get('/contact', function () {
    return Inertia::render('Contact');
});

Route::get('/login', function () {
    return Inertia::render('Login');
})->name('login');

Route::get('/register', function () {
    return Inertia::render('Register');
});

Route::get('/500', function () {
    return Inertia::render('ServerError');
});

Route::get('/session-expired', function () {
    return Inertia::render('SessionExpired');
});

Route::get('/maintenance', function () {
    return Inertia::render('Maintenance');
});

Route::get('/403', function () {
    return Inertia::render('AccessDenied');
});

Route::fallback(function () {
    return Inertia::render('NotFound');
});

use App\Modules\Discuss\Http\Controllers\PageController;

Route::middleware(['auth'])->prefix('discuss')->name('discuss.')->group(function () {
    Route::get('/', [PageController::class, 'index'])->name('index');
    Route::get('/{slug}', [PageController::class, 'room'])->name('room');
});

Route::middleware('auth')->get('/profile', [\App\Modules\Auth\Http\Controllers\ProfileController::class, 'edit']);

Route::get('/images/avatar-border.png', function () {
    return response()->file(storage_path('app/private/border/default-border.png'));
})->name('avatar.border');
