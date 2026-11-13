<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $lobbyCode,
        public string $username,
        public string $message,
        public string $sentAt,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("lobby.{$this->lobbyCode}");
    }

    public function broadcastAs(): string
    {
        return 'chat.message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'username' => $this->username,
            'message'  => $this->message,
            'sentAt'   => $this->sentAt,
        ];
    }
}