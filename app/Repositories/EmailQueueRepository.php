<?php

namespace App\Repositories;

use App\Models\EmailQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EmailQueueRepository
{
    protected $emailQueue;

    public function __construct(EmailQueue $emailQueue)
    {
        $this->emailQueue = $emailQueue;
    }

    public function all(): Collection
    {
        return $this->emailQueue->all();
    }


    public function mine()
    {
        return $this->emailQueue->toResourceCollection();
    }

    public function findByUuid(string $uuid): ?EmailQueue
    {
        return $this->emailQueue->where('email_queue_uuid', $uuid)->first();
    }

    public function findByStatus(string $status): Collection
    {
        return $this->emailQueue->where('email_status', $status)->get();
    }


    public function countWithFilters(array $filters = []): int
    {
        $query = $this->emailQueue->newQuery();

        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(email_from) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email_to) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email_subject) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email_body) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email_status) LIKE ?', ["%{$search}%"]);
            });
        }

        if (!empty($filters['email_status'])) {
            $query->where('email_status', $filters['email_status']);
        }

        if (!empty($filters['domain_uuid'])) {
            $query->where('domain_uuid', $filters['domain_uuid']);
        }

        return $query->count();
    }


    public function create(array $data): EmailQueue
    {
        return $this->emailQueue->create($data);
    }

    /**
     * Actualizar email en cola
     */
    public function update(string $uuid, array $data): ?EmailQueue
    {
        $email = $this->findByUuid($uuid);
        if ($email) {
            $email->update($data);
            return $email->fresh();
        }
        return null;
    }

    /**
     * Eliminar emails por UUIDs
     */
    public function deleteByUuids(array $uuids): int
    {
        return $this->emailQueue->whereIn('email_queue_uuid', $uuids)->delete();
    }

    /**
     * Eliminar email por UUID
     */
    public function deleteByUuid(string $uuid): bool
    {
        $email = $this->findByUuid($uuid);
        if ($email) {
            return $email->delete();
        }
        return false;
    }


    public function getPendingEmails(int $limit = 100): Collection
    {
        return $this->emailQueue->where('email_status', 'waiting')
            ->orderBy('email_date', 'asc')
            ->limit($limit)
            ->get();
    }


    public function getFailedEmailsForRetry(int $maxRetries = 3): Collection
    {
        return $this->emailQueue->where('email_status', 'failed')
            ->where('email_retry_count', '<', $maxRetries)
            ->orderBy('email_date', 'asc')
            ->get();
    }

    public function updateStatus(string $uuid, string $status, array $additionalData = []): bool
    {
        $updateData = array_merge(['email_status' => $status], $additionalData);
        

        return $this->emailQueue->where('email_queue_uuid', $uuid)
            ->update($updateData) > 0;
    }

    /**
     * Incrementar contador de reintentos
     */
    public function incrementRetryCount(string $uuid): bool
    {
        return $this->emailQueue->where('email_queue_uuid', $uuid)
            ->increment('email_retry_count') > 0;
    }


    public function getQueueStats(): array
    {
        return [
            'total' => $this->emailQueue->count(),
            'waiting' => $this->emailQueue->where('email_status', 'waiting')->count(),
            'trying' => $this->emailQueue->where('email_status', 'trying')->count(),
            'sent' => $this->emailQueue->where('email_status', 'sent')->count(),
            'failed' => $this->emailQueue->where('email_status', 'failed')->count(),
        ];
    }


    public function getEmailsWithAttachments(): Collection
    {
        return $this->emailQueue->whereHas('attachments')->with('attachments')->get();
    }

    public function getEmailsByDateRange(\DateTime $startDate, \DateTime $endDate): Collection
    {
        return $this->emailQueue->whereBetween('email_date', [$startDate, $endDate])
            ->orderBy('email_date', 'desc')
            ->get();
    }

    public function getEmailsByHostname(string $hostname): Collection
    {
        return $this->emailQueue->where('hostname', $hostname)
            ->orderBy('email_date', 'desc')
            ->get();
    }
}