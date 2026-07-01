<?php

namespace App\Modules\Discuss\Services;

use App\Modules\Auth\Models\User;
use App\Modules\Discuss\Data\CreateRoomData;
use App\Modules\Discuss\Enums\RoomType;
use App\Modules\Discuss\Models\Member;
use App\Modules\Discuss\Models\Message;
use App\Modules\Discuss\Models\Room;
use App\Services\KomikcastService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class RoomService
{
    public function __construct(
        private readonly KomikcastService $komikcast,
    ) {}
    public function create(CreateRoomData $data): Room
    {
        $room = Room::create([
            'slug' => $data->slug,
            'name' => $data->name,
            'description' => $data->description,
            'cover_url' => $data->coverUrl,
            'type' => $data->type,
            'owner_user_id' => $data->ownerUserId,
            'context_type' => $data->contextType,
            'context_id' => $data->contextId,
            'is_active' => true,
            'settings' => array_merge([
                'slow_mode_seconds' => 0,
                'max_members' => 500,
                'xp_per_message' => 5,
            ], $data->settings),
        ]);

        Member::create([
            'room_id' => $room->id,
            'user_id' => $data->ownerUserId,
            'role' => 'admin',
            'xp_points' => 0,
            'is_banned' => false,
            'joined_at' => now(),
            'last_read_at' => now(),
        ]);

        return $room;
    }

    public function archive(Room $room): void
    {
        $room->update(['is_active' => false]);
    }

    public function generateInviteLink(Room $room): string
    {
        $link = Str::random(16);

        $room->update([
            'settings' => array_merge($room->settings, ['invite_link' => $link]),
        ]);

        return $link;
    }

    public function findByContext(string $type, string $id): ?Room
    {
        return Room::where('context_type', $type)
            ->where('context_id', $id)
            ->first();
    }

    public function publicRooms(): Collection
    {
        return Room::where('is_active', true)
            ->where('type', 'public')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function roomsForUser(?string $userId): array
    {
        $rooms = Room::where('is_active', true)
            ->where(function ($q) use ($userId) {
                $q->where('type', 'public');
                if ($userId) {
                    $q->orWhereIn('id', function ($sub) use ($userId) {
                        $sub->select('room_id')
                            ->from('discuss_members')
                            ->where('user_id', $userId)
                            ->where('is_banned', false);
                    });
                }
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return $rooms->map(fn($room) => [
                'id' => $room->id,
                'name' => $room->name,
                'slug' => $room->slug,
                'cover_url' => $this->freshCover($room),
                'type' => $room->type->value,
                'context_type' => $room->context_type,
                'context_id' => $room->context_id,
                'member_count' => Member::where('room_id', $room->id)->count(),
                'last_message' => $this->getLastMessage($room->id),
                'created_at' => $room->created_at,
            ])
            ->values()
            ->all();
    }

    private function getLastMessage(string $roomId): ?array
    {
        $message = Message::where('room_id', $roomId)
            ->where('is_deleted', false)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$message) return null;

        $user = User::select('id', 'display_name')
            ->where('id', $message->user_id)
            ->first();

        return [
            'body' => $message->body,
            'created_at' => $message->created_at,
            'user' => $user ? [
                'id' => $user->id,
                'display_name' => $user->display_name,
            ] : null,
        ];
    }

    public function findBySlug(string $slug): ?Room
    {
        return Room::where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }

    public function findOrCreateForComic(string $comicSlug, string $title, string $userId): Room
    {
        $roomSlug = 'comic-' . $comicSlug;

        $room = Room::where('slug', $roomSlug)->first();
        if ($room) {
            return $room;
        }

        $data = new CreateRoomData(
            name: $title,
            slug: $roomSlug,
            description: null,
            coverUrl: null,
            type: RoomType::Public->value,
            ownerUserId: $userId,
            contextType: 'comic',
            contextId: $comicSlug,
            settings: [],
        );

        return $this->create($data);
    }

    public function freshCover(Room $room): ?string
    {
        if ($room->context_type !== 'comic' || !$room->context_id) {
            return $room->cover_url;
        }

        try {
            $detail = $this->komikcast->getDetail($room->context_id);
            return $detail['data']['cover'] ?? $room->cover_url;
        } catch (\Throwable) {
            return $room->cover_url;
        }
    }
}
