<?php

namespace App\Modules\Discuss\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Modules\Discuss\Http\Resources\MessageResource;
use App\Modules\Discuss\Services\EmoteService;
use App\Modules\Discuss\Services\MemberService;
use App\Modules\Discuss\Services\MessageService;
use App\Modules\Discuss\Services\NotificationService;
use App\Modules\Discuss\Services\RoomService;

class PageController
{
    public function __construct(
        private RoomService $roomService,
        private MessageService $messageService,
        private MemberService $memberService,
        private EmoteService $emoteService,
        private NotificationService $notificationService,
    ) {}

    public function index()
    {
        return Inertia::render('Discuss/Index', [
            'rooms' => $this->roomService->roomsForUser(auth()->id()),
        ]);
    }

    public function room(Request $request, string $slug)
    {
        $userId = auth()->id();

        $room = $this->roomService->findBySlug($slug);

        if (!$room && str_starts_with($slug, 'comic-')) {
            $comicSlug = substr($slug, 6);
            $title = $request->query('title', 'Comic Discuss');
            $room = $this->roomService->findOrCreateForComic($comicSlug, $title, $userId);
            return redirect()->route('discuss.room', ['slug' => $room->slug]);
        }

        abort_unless($room, 404);

        $room->cover_url = $this->roomService->freshCover($room);

        return Inertia::render('Discuss/Room', [
            'room'          => $room,
            'messages'      => MessageResource::collection(
                $this->messageService->paginate($room->id, limit: 50)
            )->toArray($request),
            'members'       => $this->memberService->listForRoom($room->id),
            'emotes'        => $this->emoteService->availableFor($room->id),
            'currentUserId' => $userId,
        ]);
    }
}
