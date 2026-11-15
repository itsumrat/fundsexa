<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

// Broadcast immediately (no queue worker needed on XAMPP)
class ChatMessageSent implements ShouldBroadcastNow
{
    use SerializesModels, BroadcastsSafely;

    public function __construct(public Message $message) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->message->user_id), // the customer
            new PrivateChannel('admin.chat'),                      // all admins (inbox + badges)
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return $this->message->toChatArray() + [
            'customer_name' => $this->message->user->name ?? 'Customer',
        ];
    }
}
