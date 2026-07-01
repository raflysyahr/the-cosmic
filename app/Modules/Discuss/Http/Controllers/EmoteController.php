<?php

namespace App\Modules\Discuss\Http\Controllers;

use App\Modules\Discuss\Http\Requests\UploadEmoteRequest;
use App\Modules\Discuss\Models\Emote;
use App\Modules\Discuss\Services\EmoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class EmoteController
{
    public function __construct(
        private readonly EmoteService $emoteService,
    ) {}

    public function index(Request $request): Collection
    {
        $roomId = $request->input('room_id');

        if ($roomId) {
            return $this->emoteService->availableFor($roomId);
        }

        return Emote::active()->get();
    }

    public function store(UploadEmoteRequest $request): JsonResponse
    {
        $emote = $this->emoteService->upload(
            file: $request->file('image'),
            code: $request->input('code'),
            name: $request->input('name'),
            roomId: $request->input('room_id'),
            userId: $request->user()->id,
        );

        return response()->json(['emote' => $emote], 201);
    }

    public function destroy(string $emoteId): JsonResponse
    {
        $emote = Emote::findOrFail($emoteId);

        $this->emoteService->deactivate($emote);

        return response()->json(['message' => 'Emote deactivated.']);
    }
}
