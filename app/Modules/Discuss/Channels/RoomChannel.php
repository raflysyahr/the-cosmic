<?php

use Illuminate\Support\Facades\Broadcast;
use App\Modules\Discuss\Models\Member;

Broadcast::channel('room.{roomId}', function ($user, $roomId) {
    $member = Member::where('room_id', $roomId)
        ->where('user_id', $user->id)
        ->where('is_banned', false)
        ->first();

    if (!$member) return false;

    return [
        'id'           => $user->id,
        'display_name' => $user->display_name,
        'avatar_url'   => $user->avatar_url,
        'role'         => $member->role,
        'rank'         => $member->rank_id ? [
            'name'  => optional($member->rank)->name,
            'color' => optional($member->rank)->label_color,
        ] : null,
    ];
});

Broadcast::channel('private-user.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId;
});
