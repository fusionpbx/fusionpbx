<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Events\CallStarted;
use App\Events\CallEnded;

class ProcessCallEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array $eventData
    ) {}

    public function handle(): void
    {
        $eventName = $this->eventData['Event-Name'] ?? '';
        
        match ($eventName) {
            'CHANNEL_CREATE' => $this->handleCallStart(),
            'CHANNEL_HANGUP' => $this->handleCallEnd(),
            default => null,
        };
    }

    private function handleCallStart(): void
    {
        CallStarted::dispatch($this->eventData);
    }

    private function handleCallEnd(): void
    {
        CallEnded::dispatch($this->eventData);
    }
}
