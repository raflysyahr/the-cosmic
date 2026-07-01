<?php

namespace App\Modules\Discuss\Events;

use App\Modules\Discuss\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageEdited implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Message $message,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('room.' . $this->message->room_id);
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'body' => $this->message->body,
            'is_edited' => true,
            'updated_at' => $this->message->updated_at,
        ];
    }
}
