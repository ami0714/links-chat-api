<?php

namespace Tests\Unit;

use App\Events\MessageSent;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\TestCase;

class MessageSentTest extends TestCase
{
    public function test_broadcast_uses_authorized_conversation_and_user_channels(): void
    {
        $event = new MessageSent(
            conversationId: 42,
            senderId: 7,
            recipientId: 9,
            messageId: '01JABCDEF0123456789ABCDEFG',
            body: 'Hello',
            createdAt: '2026-09-30T00:00:00+00:00',
        );

        $channels = array_map(
            fn (PrivateChannel $channel): string => $channel->name,
            $event->broadcastOn(),
        );

        $this->assertSame(['private-conversation.42', 'private-user.9'], $channels);
    }
}
