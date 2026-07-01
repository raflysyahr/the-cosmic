<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReadingHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $history = ReadingHistory::where('user_id', $request->user()->id)
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'status' => 200,
            'data' => $history,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => 'required|string',
            'title' => 'required|string',
            'cover_image' => 'nullable|string',
            'chapter_index' => 'required|integer',
            'chapter_title' => 'nullable|string',
            'chapter_url' => 'nullable|string',
        ]);

        $history = ReadingHistory::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'slug' => $validated['slug'],
                'chapter_index' => $validated['chapter_index'],
            ],
            $validated
        );

        return response()->json([
            'status' => 200,
            'data' => $history,
        ]);
    }
}
