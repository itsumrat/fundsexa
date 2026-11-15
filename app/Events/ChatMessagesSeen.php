<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

// Read receipt: tells the other side to switch its "✓ Sent" ticks to "✓✓ Seen"
class ChatMessagesSeen implements ShouldBroadcastNow
{
    use BroadcastsSafely;

    /** @param string $reader 'admin' or 'customer' — who just read the conversation */
    public function __construct(public int $userId, public string $reader) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->userId),
            new PrivateChannel('admin.chat'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'messages.seen';
    }

    public function broadcastWith(): array
    {
        return ['user_id' => $this->userId, 'reader' => $this->reader];
    }
}
