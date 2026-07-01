<?php

namespace App\Modules\Discuss\Services;

use App\Modules\Discuss\Events\ReactionToggled;
use App\Modules\Discuss\Models\Reaction;

class ReactionService
{
    public function toggle(string $messageId, string $userId, string $emoteId): void
    {
        $reaction = Reaction::where('message_id', $messageId)
            ->where('user_id', $userId)
            ->where('emote_id', $emoteId)
            ->first();

        if ($reaction) {
            $reaction->delete();
        } else {
            Reaction::create([
                'message_id' => $messageId,
                'user_id' => $userId,
                'emote_id' => $emoteId,
            ]);
        }

        event(new ReactionToggled($messageId, $userId, $emoteId, $reaction ? 'removed' : 'added'));
    }
}
