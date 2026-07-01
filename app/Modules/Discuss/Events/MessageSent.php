<?php

namespace App\Modules\Discuss\Events;

use App\Modules\Discuss\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
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
            'room_id' => $this->message->room_id,
            'user_id' => $this->message->user_id,
            'type' => $this->message->type->value,
            'body' => $this->message->body,
            'attachments' => $this->message->attachments,
            'reply_to_id' => $this->message->reply_to_id,
            'metadata' => $this->message->metadata,
            'created_at' => $this->message->created_at,
        ];
    }
}
