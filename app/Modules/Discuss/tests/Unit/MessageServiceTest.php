<?php

namespace App\Modules\Discuss\tests\Unit;

use App\Modules\Auth\Models\User;
use App\Modules\Discuss\Data\SendMessageData;
use App\Modules\Discuss\Models\Member;
use App\Modules\Discuss\Models\Message;
use App\Modules\Discuss\Models\Room;
use App\Modules\Discuss\Services\MessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageServiceTest extends TestCase
{
    use RefreshDatabase;

    private MessageService $messageService;
    private Room $room;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->messageService = app(MessageService::class);
        $this->user = User::factory()->create();
        $this->room = Room::factory()->create(['owner_user_id' => $this->user->id]);

        Member::create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'role' => 'member',
            'xp_points' => 0,
            'is_banned' => false,
            'joined_at' => now(),
            'last_read_at' => now(),
        ]);
    }

    public function test_can_send_message(): void
    {
        $data = new SendMessageData(
            roomId: $this->room->id,
            userId: $this->user->id,
            replyToId: null,
            type: 'text',
            body: 'Hello world!',
            attachments: [],
            metadata: [],
        );

        $message = $this->messageService->send($data);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals('Hello world!', $message->body);
        $this->assertEquals('text', $message->type->value);
        $this->assertFalse($message->is_deleted);
    }

    public function test_can_edit_message(): void
    {
        $data = new SendMessageData(
            roomId: $this->room->id,
            userId: $this->user->id,
            replyToId: null,
            type: 'text',
            body: 'Original',
            attachments: [],
            metadata: [],
        );

        $message = $this->messageService->send($data);
        $edited = $this->messageService->edit($message, 'Edited body');

        $this->assertEquals('Edited body', $edited->body);
        $this->assertTrue($edited->is_edited);
    }

    public function test_can_soft_delete_message(): void
    {
        $data = new SendMessageData(
            roomId: $this->room->id,
            userId: $this->user->id,
            replyToId: null,
            type: 'text',
            body: 'Will be deleted',
            attachments: [],
            metadata: [],
        );

        $message = $this->messageService->send($data);
        $this->messageService->delete($message, $this->user->id);

        $deleted = $message->fresh();

        $this->assertTrue($deleted->is_deleted);
        $this->assertEquals($this->user->id, $deleted->deleted_by_user_id);
    }
}
