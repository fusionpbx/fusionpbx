<?php

namespace App\Jobs;

use App\Models\EmailQueue;
use App\Services\EmailQueueService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $emailQueueUuid) {}

    public $tries = 5;
    public $backoff = [10, 60, 120, 300, 600]; 

    public function handle(EmailQueueService $emailQueueService): void
    {
        $emailQueueService->processQueuedEmail($this->emailQueueUuid);
    }

    public function failed(\Throwable $exception): void
    {
        $item = EmailQueue::where('email_queue_uuid', $this->emailQueueUuid)->first();
        if ($item) {
            $item->update([
                'email_status' => 'failed',
                'email_response' => substr($exception->getMessage(), 0, 1000),
            ]);
        }
    }
}