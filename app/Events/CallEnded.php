<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallEnded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public array $callData
    ) {}

    public function broadcastOn(): array
    {
        $domainUuid = $this->callData['domain_uuid'] ?? 'default';
        return [
            new Channel('calls.' . $domainUuid),
        ];
    }

    public function broadcastAs(): string
    {
        return 'call.ended';
    }
}
