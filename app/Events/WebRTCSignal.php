<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebRTCSignal implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $lobbyCode,
        public string $from,
        public ?string $to,
        public mixed $signal,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("lobby.{$this->lobbyCode}");
    }

    public function broadcastAs(): string { return 'webrtc.signal'; }

    public function broadcastWith(): array
    {
        return [
            'from'   => $this->from,
            'to'     => $this->to,
            'signal' => $this->signal,
        ];
    }
}