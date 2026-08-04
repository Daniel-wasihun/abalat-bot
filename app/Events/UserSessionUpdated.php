<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserSessionUpdated implements \Illuminate\Contracts\Broadcasting\ShouldBroadcastNow {
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $session;

    /**
     * Create a new event instance.
     */
    public function __construct($userId, $session) {
        $this->userId = $userId;
        $this->session = $session;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array {
        return [
            new PrivateChannel('user.' . $this->userId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string {
        return 'session.updated';
    }
}
