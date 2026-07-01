<?php

namespace App\Modules\Discuss\Services;

use App\Modules\Discuss\Enums\RoomType;
use App\Modules\Discuss\Events\MemberBanned;
use App\Modules\Discuss\Events\MemberJoined;
use App\Modules\Discuss\Events\MemberLeft;
use App\Modules\Discuss\Models\Member;
use App\Modules\Discuss\Models\Room;
use App\Modules\Auth\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class MemberService
{
    public function join(string $roomId, string $userId): Member
    {
        $room = Room::findOrFail($roomId);

        if ($room->type === RoomType::Private) {
            throw ValidationException::withMessages([
                'room' => ['This room is private. Join by invite only.'],
            ]);
        }

        $existing = Member::where('room_id', $roomId)
            ->where('user_id', $userId)
            ->first();

        if ($existing && $existing->is_banned) {
            throw ValidationException::withMessages([
                'room' => ['You are banned from this room.'],
            ]);
        }

        if ($existing) {
            return $existing;
        }

        $member = Member::create([
            'room_id' => $roomId,
            'user_id' => $userId,
            'role' => 'member',
            'xp_points' => 0,
            'is_banned' => false,
            'joined_at' => now(),
            'last_read_at' => now(),
        ]);

        event(new MemberJoined($member));

        return $member;
    }

    public function leave(string $roomId, string $userId): void
    {
        $member = Member::where('room_id', $roomId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $member->delete();

        event(new MemberLeft($member));
    }

    public function kick(Member $member, string $byUserId): void
    {
        $member->delete();
    }

    public function mute(Member $member, int $minutes): void
    {
        $member->update([
            'muted_until' => now()->addMinutes($minutes),
        ]);
    }

    public function ban(Member $member): void
    {
        $member->update([
            'is_banned' => true,
            'muted_until' => null,
        ]);

        event(new MemberBanned($member));
    }

    public function listForRoom(string $roomId): Collection
    {
        return Member::where('room_id', $roomId)
            ->where('is_banned', false)
            ->orderByRaw("FIELD(role, 'admin', 'moderator', 'member')")
            ->get()
            ->map(function (Member $member) {
                $user = User::select('id', 'display_name', 'avatar_url')
                    ->where('id', $member->user_id)
                    ->first();

                return [
                    'userId'      => $member->user_id,
                    'displayName' => $user?->display_name ?? 'Unknown',
                    'avatarUrl'   => $user?->avatar_url,
                    'role'        => $member->role,
                    'xpPoints'    => $member->xp_points,
                    'rank'        => $member->rank_id ? [
                        'name'  => optional($member->rank)->name,
                        'color' => optional($member->rank)->label_color,
                    ] : null,
                    'isOnline'    => false,
                ];
            });
    }

    public function isMember(string $roomId, string $userId): bool
    {
        return Member::where('room_id', $roomId)
            ->where('user_id', $userId)
            ->where('is_banned', false)
            ->exists();
    }

    public function roleOf(string $roomId, string $userId): ?string
    {
        $member = Member::where('room_id', $roomId)
            ->where('user_id', $userId)
            ->first();

        return $member?->role?->value;
    }
}
