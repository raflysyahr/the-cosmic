<?php

namespace App\Modules\Discuss\Http\Controllers;

use App\Modules\Discuss\Data\SendMessageData;
use App\Modules\Discuss\Http\Requests\SendMessageRequest;
use App\Modules\Discuss\Http\Resources\MessageResource;
use App\Modules\Discuss\Models\Message;
use App\Modules\Discuss\Models\Room;
use App\Modules\Discuss\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MessageController
{
    public function __construct(
        private readonly MessageService $messageService,
    ) {}

    public function index(Request $request, string $slug): ResourceCollection
    {
        $room = Room::where('slug', $slug)->firstOrFail();

        $messages = Message::where('room_id', $room->id)
            ->notDeleted()
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return MessageResource::collection($messages);
    }

    public function store(SendMessageRequest $request, string $slug): JsonResponse
    {
        $room = Room::where('slug', $slug)->firstOrFail();

        $data = new SendMessageData(
            roomId: $room->id,
            userId: $request->user()->id,
            replyToId: $request->input('reply_to_id'),
            type: $request->input('type', 'text'),
            body: $request->input('body'),
            attachments: $request->input('attachments', []),
            metadata: $request->input('metadata', []),
        );

        $message = $this->messageService->send($data);

        return response()->json([
            'message' => new MessageResource($message),
        ], 201);
    }

    public function update(Request $request, string $slug, string $messageId): JsonResponse
    {
        $request->validate(['body' => ['required', 'string']]);

        $message = Message::findOrFail($messageId);

        $updated = $this->messageService->edit($message, $request->input('body'), $request->user()->id);

        return response()->json([
            'message' => new MessageResource($updated),
        ]);
    }

    public function destroy(string $slug, string $messageId): JsonResponse
    {
        $message = Message::findOrFail($messageId);

        $this->messageService->delete($message, request()->user()->id);

        return response()->json(['message' => 'Message deleted.']);
    }
}
