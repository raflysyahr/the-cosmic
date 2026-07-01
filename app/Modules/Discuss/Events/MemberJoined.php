<?php

namespace App\Modules\Discuss\Events;

use App\Modules\Discuss\Models\Member;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MemberJoined implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Member $member,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('room.' . $this->member->room_id);
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->member->user_id,
            'room_id' => $this->member->room_id,
            'role' => $this->member->role->value,
        ];
    }
}
