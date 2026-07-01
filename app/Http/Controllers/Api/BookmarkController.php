<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $bookmarks = Bookmark::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 200,
            'data' => $bookmarks,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => 'required|string',
            'title' => 'required|string',
            'cover_image' => 'nullable|string',
            'format' => 'nullable|string',
        ]);

        $bookmark = Bookmark::firstOrCreate([
            'user_id' => $request->user()->id,
            'slug' => $validated['slug'],
        ], $validated);

        return response()->json([
            'status' => 200,
            'message' => 'Bookmark added',
            'data' => $bookmark,
        ]);
    }

    public function destroy(Request $request, string $slug): JsonResponse
    {
        Bookmark::where('user_id', $request->user()->id)
            ->where('slug', $slug)
            ->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Bookmark removed',
        ]);
    }
}
