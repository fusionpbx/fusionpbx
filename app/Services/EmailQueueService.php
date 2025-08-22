<?php

namespace App\Services;

use App\Jobs\SendEmail;
use App\Models\EmailQueue;
use App\Repositories\EmailQueueRepository;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailQueueService
{
    protected $emailQueueRepository;

    public function __construct(EmailQueueRepository $emailQueueRepository)
    {
        $this->emailQueueRepository = $emailQueueRepository;
    }

    public function processQueuedEmail(string $emailQueueUuid): void
    {
        $item = $this->emailQueueRepository->findByUuid($emailQueueUuid);
        
        if (!$item) {
            Log::warning("Email queue item not found: {$emailQueueUuid}");
            return;
        }

        $this->emailQueueRepository->updateStatus($emailQueueUuid, 'trying');

        try {
            $this->sendEmail($item);
            
            $this->emailQueueRepository->updateStatus($emailQueueUuid, 'sent', [
                'email_date' => now(),
                'email_response' => 'Email sent successfully'
            ]);

            Log::info("Email sent successfully: {$emailQueueUuid}");

        } catch (\Throwable $e) {
            $this->handleEmailFailure($item, $e);
            throw $e; 
        }
    }

    protected function sendEmail(EmailQueue $item): void
    {
        $to = $this->parseEmails($item->email_to);
        
        Mail::send([], [], function ($message) use ($item, $to) {
            $message->to($to)
                   ->subject($item->email_subject)
                   ->from($item->email_from);

            if (!empty($item->email_body)) {
                if (strip_tags($item->email_body) != $item->email_body) {
                    $message->setBody($item->email_body, 'text/html');
                } else {
                    $message->setBody($item->email_body, 'text/plain');
                }
            }

            if ($item->attachments && $item->attachments->count() > 0) {
                foreach ($item->attachments as $attachment) {
                    $this->attachFile($message, $attachment);
                }
            }
        });
    }

    protected function attachFile($message, $attachment): void
    {
        if (isset($attachment->file_path) && file_exists($attachment->file_path)) {
            $message->attach($attachment->file_path, [
                'as' => $attachment->file_name ?? null,
                'mime' => $attachment->mime_type ?? null
            ]);
        }
    }

    protected function parseEmails(string $emails): array
    {
        return array_map('trim', explode(',', $emails));
    }

    protected function handleEmailFailure(EmailQueue $item, \Throwable $e): void
    {
        $this->emailQueueRepository->incrementRetryCount($item->email_queue_uuid);
        
        $maxRetries = 3; 
        $newStatus = ($item->email_retry_count + 1) >= $maxRetries ? 'failed' : 'waiting';
        
        $this->emailQueueRepository->updateStatus($item->email_queue_uuid, $newStatus, [
            'email_response' => substr($e->getMessage(), 0, 1000)
        ]);

        Log::error("Email sending failed: {$item->email_queue_uuid} - " . $e->getMessage());
    }

    public function queueEmail(array $emailData): EmailQueue
    {
        $queueItem = $this->emailQueueRepository->create([
            'email_queue_uuid' => \Illuminate\Support\Str::uuid(),
            'domain_uuid' => $emailData['domain_uuid'] ?? null,
            'hostname' => $emailData['hostname'] ?? config('app.url'),
            'email_to' => $emailData['to'],
            'email_from' => $emailData['from'],
            'email_subject' => $emailData['subject'],
            'email_body' => $emailData['body'],
            'email_status' => 'waiting',
            'email_retry_count' => 0,
            'email_date' => now(),
        ]);

        SendEmail::dispatch($queueItem->email_queue_uuid);

        return $queueItem;
    }
}