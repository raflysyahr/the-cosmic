<?php

namespace App\Modules\Discuss\Http\Resources;

use App\Modules\Auth\Models\User;
use App\Modules\Discuss\Models\Message;
use App\Modules\Discuss\Models\Reaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = User::select('id', 'display_name', 'avatar_url')
            ->where('id', $this->user_id)
            ->first();

        $reactions = Reaction::where('message_id', $this->id)
            ->selectRaw('emote_id, COUNT(*) as count')
            ->groupBy('emote_id')
            ->pluck('count', 'emote_id');

        $replyTo = null;
        if ($this->reply_to_id) {
            $replyToMsg = Message::select('id', 'user_id', 'body')
                ->where('id', $this->reply_to_id)
                ->first();
            if ($replyToMsg) {
                $replyToUser = User::select('id', 'display_name', 'avatar_url')
                    ->where('id', $replyToMsg->user_id)
                    ->first();
                $replyTo = [
                    'id' => $replyToMsg->id,
                    'body' => $replyToMsg->body,
                    'user' => $replyToUser ? [
                        'id' => $replyToUser->id,
                        'display_name' => $replyToUser->display_name,
                        'avatar_url' => $replyToUser->avatar_url,
                    ] : ['display_name' => 'Unknown'],
                ];
            }
        }

        return [
            'id' => $this->id,
            'body' => $this->body,
            'type' => $this->type->value,
            'user' => $user ? [
                'id' => $user->id,
                'display_name' => $user->display_name,
                'avatar_url' => $user->avatar_url,
            ] : null,
            'attachments' => $this->attachments,
            'reply_to' => $replyTo,
            'reply_to_id' => $this->reply_to_id,
            'reply_count' => Message::where('reply_to_id', $this->id)
    ->distinct('user_id')
    ->count('user_id'),
            'reactions' => $reactions,
            'metadata' => $this->metadata,
            'is_edited' => $this->is_edited,
            'is_deleted' => $this->is_deleted,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
