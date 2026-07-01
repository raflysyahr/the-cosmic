<?php

namespace App\Modules\Discuss\Http\Controllers;

use App\Modules\Discuss\Http\Requests\CreateRankRequest;
use App\Modules\Discuss\Models\Rank;
use App\Modules\Discuss\Models\Room;
use App\Modules\Discuss\Services\RankService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RankController
{
    public function __construct(
        private readonly RankService $rankService,
    ) {}

    public function index(string $slug): JsonResponse
    {
        $room = Room::where('slug', $slug)->firstOrFail();

        $ranks = $this->rankService->ranksForRoom($room->id);

        return response()->json(['ranks' => $ranks]);
    }

    public function store(CreateRankRequest $request, string $slug): JsonResponse
    {
        $room = Room::where('slug', $slug)->firstOrFail();

        $rank = Rank::create([
            'room_id' => $room->id,
            'name' => $request->input('name'),
            'label_color' => $request->input('label_color'),
            'icon_url' => $request->input('icon_url'),
            'min_xp' => $request->input('min_xp'),
            'order' => $request->input('order'),
            'perks' => $request->input('perks', []),
        ]);

        return response()->json(['rank' => $rank], 201);
    }

    public function update(Request $request, string $slug, string $rankId): JsonResponse
    {
        $request->validate([
            'name' => ['sometimes', 'string', 'max:50'],
            'label_color' => ['sometimes', 'string', 'max:7'],
            'icon_url' => ['nullable', 'url'],
            'min_xp' => ['sometimes', 'integer', 'min:0'],
            'order' => ['sometimes', 'integer', 'min:1'],
            'perks' => ['nullable', 'array'],
        ]);

        $rank = Rank::findOrFail($rankId);
        $rank->update($request->only([
            'name', 'label_color', 'icon_url', 'min_xp', 'order', 'perks',
        ]));

        return response()->json(['rank' => $rank->fresh()]);
    }

    public function destroy(string $slug, string $rankId): JsonResponse
    {
        $rank = Rank::findOrFail($rankId);
        $rank->delete();

        return response()->json(['message' => 'Rank deleted.']);
    }
}
