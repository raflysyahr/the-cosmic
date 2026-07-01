<?php

namespace App\Modules\Discuss\Http\Controllers;

use App\Modules\Discuss\Data\CreateRoomData;
use App\Modules\Discuss\Http\Requests\CreateRoomRequest;
use App\Modules\Discuss\Http\Requests\UpdateRoomSettingsRequest;
use App\Modules\Discuss\Http\Resources\RoomResource;
use App\Modules\Discuss\Models\Message;
use App\Modules\Discuss\Models\Room;
use App\Modules\Discuss\Services\RoomService;
use App\Modules\Auth\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class RoomController
{
    public function __construct(
        private readonly RoomService $roomService,
    ) {}

    public function index(Request $request): array
    {
        $rooms = Room::active()
            ->withCount('members')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $data = collect($rooms->items())->map(function ($room) {
            $lastMessage = Message::where('room_id', $room->id)
                ->where('is_deleted', false)
                ->orderBy('created_at', 'desc')
                ->first();

            $lastMessageData = null;
            if ($lastMessage) {
                $user = User::select('id', 'display_name')
                    ->where('id', $lastMessage->user_id)
                    ->first();

                $lastMessageData = [
                    'body' => $lastMessage->body,
                    'created_at' => $lastMessage->created_at,
                    'user' => $user ? [
                        'id' => $user->id,
                        'display_name' => $user->display_name,
                    ] : null,
                ];
            }

            return [
                'id' => $room->id,
                'name' => $room->name,
                'slug' => $room->slug,
                'cover_url' => $this->roomService->freshCover($room),
                'type' => $room->type->value,
                'context_type' => $room->context_type,
                'context_id' => $room->context_id,
                'member_count' => $room->members_count,
                'last_message' => $lastMessageData,
                'created_at' => $room->created_at,
            ];
        });

        return [
            'data' => $data->values()->all(),
            'meta' => [
                'current_page' => $rooms->currentPage(),
                'last_page' => $rooms->lastPage(),
                'per_page' => $rooms->perPage(),
                'total' => $rooms->total(),
            ],
        ];
    }

    public function store(CreateRoomRequest $request): JsonResponse
    {
        $data = new CreateRoomData(
            name: $request->input('name'),
            slug: $request->input('slug'),
            description: $request->input('description'),
            coverUrl: $request->input('cover_url'),
            type: $request->input('type', 'public'),
            ownerUserId: $request->user()->id,
            contextType: $request->input('context_type'),
            contextId: $request->input('context_id'),
            settings: $request->input('settings', []),
        );

        $room = $this->roomService->create($data);
        $room->cover_url = $this->roomService->freshCover($room);

        return response()->json([
            'room' => new RoomResource($room),
        ], 201);
    }

    public function show(string $slug): RoomResource
    {
        $room = Room::where('slug', $slug)
            ->withCount('members')
            ->firstOrFail();

        $room->cover_url = $this->roomService->freshCover($room);

        return new RoomResource($room);
    }

    public function update(UpdateRoomSettingsRequest $request, string $slug): JsonResponse
    {
        $room = Room::where('slug', $slug)->firstOrFail();

        $room->update($request->only([
            'name', 'description', 'cover_url', 'type', 'is_active', 'settings',
        ]));

        $room = $room->fresh();
        $room->cover_url = $this->roomService->freshCover($room);

        return response()->json([
            'room' => new RoomResource($room),
        ]);
    }

    public function destroy(string $slug): JsonResponse
    {
        $room = Room::where('slug', $slug)->firstOrFail();

        $this->roomService->archive($room);

        return response()->json(['message' => 'Room archived.']);
    }
}
