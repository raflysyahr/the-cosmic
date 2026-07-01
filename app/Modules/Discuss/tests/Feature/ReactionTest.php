<?php

namespace App\Modules\Discuss\tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Discuss\Models\Emote;
use App\Modules\Discuss\Models\Member;
use App\Modules\Discuss\Models\Message;
use App\Modules\Discuss\Models\Reaction;
use App\Modules\Discuss\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_add_reaction(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $emote = Emote::create(['code' => ':like:', 'name' => 'Like', 'image_url' => '/emotes/like.png']);

        Member::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'role' => 'member',
            'is_banned' => false,
            'joined_at' => now(),
            'last_read_at' => now(),
        ]);

        $message = Message::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'type' => 'text',
            'body' => 'Test',
        ]);

        $response = $this->actingAs($user)->postJson(
            "/api/rooms/{$room->slug}/messages/{$message->id}/reactions",
            ['emote_id' => $emote->id],
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('discuss_reactions', [
            'message_id' => $message->id,
            'user_id' => $user->id,
            'emote_id' => $emote->id,
        ]);
    }

    public function test_can_remove_reaction(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $emote = Emote::create(['code' => ':like:', 'name' => 'Like', 'image_url' => '/emotes/like.png']);

        Member::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'role' => 'member',
            'is_banned' => false,
            'joined_at' => now(),
            'last_read_at' => now(),
        ]);

        $message = Message::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'type' => 'text',
            'body' => 'Test',
        ]);

        Reaction::create([
            'message_id' => $message->id,
            'user_id' => $user->id,
            'emote_id' => $emote->id,
        ]);

        $response = $this->actingAs($user)->postJson(
            "/api/rooms/{$room->slug}/messages/{$message->id}/reactions",
            ['emote_id' => $emote->id],
        );

        $response->assertStatus(200);

        $this->assertDatabaseMissing('discuss_reactions', [
            'message_id' => $message->id,
            'user_id' => $user->id,
            'emote_id' => $emote->id,
        ]);
    }
}
