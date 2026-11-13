<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameStateUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $lobbyCode,
        public array  $state,
        public string $triggeredBy,
        public string $action,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("lobby.{$this->lobbyCode}");
    }

    public function broadcastAs(): string
    {
        return 'game.state.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'state'       => $this->state,
            'triggeredBy' => $this->triggeredBy,
            'action'      => $this->action,
            'revision'    => $this->state['revision'] ?? 0,
        ];
    }
}