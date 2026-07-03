<?php

namespace App\Modules\Discuss\tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Discuss\Models\Member;
use App\Modules\Discuss\Models\Message;
use App\Modules\Discuss\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditMessageTest extends TestCase
{
    use RefreshDatabase;

    private function makeMember(Room $room, User $user): void
    {
        Member::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'role' => 'member',
            'is_banned' => false,
            'joined_at' => now(),
            'last_read_at' => now(),
        ]);
    }

    public function test_owner_can_edit_own_message(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $this->makeMember($room, $user);

        $message = Message::factory()->create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'body' => 'Original',
        ]);

        $response = $this->actingAs($user)->putJson(
            "/api/rooms/{$room->slug}/messages/{$message->id}",
            ['body' => 'Updated body']
        );

        $response->assertStatus(200);
        $this->assertEquals('Updated body', $message->fresh()->body);
        $this->assertTrue($message->fresh()->is_edited);
    }

    public function test_other_user_cannot_edit_message(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $room = Room::factory()->create();
        $this->makeMember($room, $owner);
        $this->makeMember($room, $attacker);

        $message = Message::factory()->create([
            'room_id' => $room->id,
            'user_id' => $owner->id,
            'body' => 'Original',
        ]);

        $response = $this->actingAs($attacker)->putJson(
            "/api/rooms/{$room->slug}/messages/{$message->id}",
            ['body' => 'Hacked body']
        );

        $response->assertStatus(422);
        $this->assertEquals('Original', $message->fresh()->body);
        $this->assertFalse($message->fresh()->is_edited);
    }

    public function test_guest_cannot_edit_message(): void
    {
        $owner = User::factory()->create();
        $room = Room::factory()->create();
        $this->makeMember($room, $owner);

        $message = Message::factory()->create([
            'room_id' => $room->id,
            'user_id' => $owner->id,
            'body' => 'Original',
        ]);

        $response = $this->putJson(
            "/api/rooms/{$room->slug}/messages/{$message->id}",
            ['body' => 'Hacked body']
        );

        $response->assertStatus(401);
        $this->assertEquals('Original', $message->fresh()->body);
    }
}
