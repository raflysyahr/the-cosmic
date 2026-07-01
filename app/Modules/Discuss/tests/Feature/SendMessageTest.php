<?php

namespace App\Modules\Discuss\tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Discuss\Models\Member;
use App\Modules\Discuss\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_send_message(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        Member::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'role' => 'member',
            'is_banned' => false,
            'joined_at' => now(),
            'last_read_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson("/api/rooms/{$room->slug}/messages", [
            'body' => 'Hello from test!',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['message']);
    }

    public function test_cannot_send_empty_message(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        Member::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'role' => 'member',
            'is_banned' => false,
            'joined_at' => now(),
            'last_read_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson("/api/rooms/{$room->slug}/messages", []);

        $response->assertStatus(422);
    }

    public function test_cannot_send_when_muted(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        Member::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'role' => 'member',
            'is_banned' => false,
            'muted_until' => now()->addDays(1),
            'joined_at' => now(),
            'last_read_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson("/api/rooms/{$room->slug}/messages", [
            'body' => 'Test message',
        ]);

        $response->assertStatus(422);
    }
}
