<?php

namespace App\Livewire;

use App\Models\EmailQueue;
use App\Repositories\EmailQueueRepository;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Illuminate\Support\Str;

class EmailQueueForm extends Component
{
    public ?string $emailQueueUuid = null;
    public ?EmailQueue $emailQueue = null;
    public bool $isEditing = false;

    public string $email_from = '';
    
    public string $email_to = '';
    
    public string $email_subject = '';
    
    public string $email_body = '';
    
    public string $email_status = 'waiting';
    
    public string $hostname = '';
    
    
    public string $email_action_after = '';
    
    public int $email_retry_count = 0;
    
    public ?string $email_date = null;
    

    public array $statusOptions = [
        'waiting' => 'Waiting',
        'trying' => 'Trying', 
        'sent' => 'Sent',
        'failed' => 'Failed'
    ];

    public function rules(): array
    {
        return [
            'email_from' => 'required|string',
            'email_to' => 'required|string',
            'email_subject' => 'required|string|max:255',
            'email_body' => 'required|string',
            'email_status' => 'in:' . implode(',', array_keys($this->statusOptions)),
            'hostname' => 'nullable|string|max:255',
            'email_action_after' => 'nullable|string',
            'email_retry_count' => 'integer',
            'email_date' => 'nullable|date',
        ];
    }

    protected EmailQueueRepository $repository;

    public function boot(EmailQueueRepository $repository)
    {
        $this->repository = $repository;
    }

    public function mount(?string $emailQueueUuid = null)
    {
        if ($emailQueueUuid) {
            $this->emailQueueUuid = $emailQueueUuid;
            $this->loadEmailQueue();
        } else {
            $this->initializeNewEmail();
        }
    }

    protected function loadEmailQueue(): void
    {
        $this->emailQueue = $this->repository->findByUuid($this->emailQueueUuid);
        
        if (!$this->emailQueue) {
            session()->flash('error', 'Email queue entry not found.');
            redirect()->route('email-queue.index');
        }

        $this->isEditing = true;
        $this->fillFormData();
    }

    protected function fillFormData(): void
    {
        if (!$this->emailQueue) return;

        $this->email_from = $this->emailQueue->email_from ?? '';
        $this->email_to = $this->emailQueue->email_to ?? '';
        $this->email_subject = $this->emailQueue->email_subject ?? '';
        $this->email_body = $this->emailQueue->email_body ?? '';
        $this->email_status = $this->emailQueue->email_status ?? 'waiting';
        $this->hostname = $this->emailQueue->hostname ?? '';
        $this->email_action_after = $this->emailQueue->email_action_after ?? '';
        $this->email_retry_count = $this->emailQueue->email_retry_count ?? 0;
        $this->email_date = $this->emailQueue->email_date?->format('Y-m-d\TH:i');
    }

    protected function initializeNewEmail(): void
    {
        $this->isEditing = false;
        $this->email_date = now()->format('Y-m-d\TH:i');
        $this->hostname = request()->getHost();
        $this->email_status = 'waiting';
        $this->email_retry_count = 0;
    }

    public function save()
    {
        $this->validate();

        try {
            $data = $this->getFormData();

            if ($this->isEditing) {
                $this->emailQueue = $this->repository->update($this->emailQueueUuid, $data);
                session()->flash('success', 'Email queue entry updated successfully.');
            } else {
                $data['email_queue_uuid'] = Str::uuid()->toString();
                $this->emailQueue = $this->repository->create($data);
                session()->flash('success', 'Email queue entry created successfully.');
                
                return $this->redirect(route('email-queue.edit', $this->emailQueue->email_queue_uuid));
            }

        } catch (\Exception $e) {
            session()->flash('error', 'Error saving email queue entry: ' . $e->getMessage());
        }
    }

    protected function getFormData(): array
    {
        return [
            'email_from' => $this->email_from,
            'email_to' => $this->email_to,
            'email_subject' => $this->email_subject,
            'email_body' => $this->email_body,
            'email_status' => $this->email_status,
            'hostname' => $this->hostname ?: request()->getHost(),
            'email_action_after' => $this->email_action_after,
            'email_retry_count' => $this->email_retry_count,
            'email_date' => $this->email_date ? Carbon::parse($this->email_date) : now(),
        ];
    }

    public function delete(): void
    {
        if (!$this->isEditing || !$this->emailQueueUuid) {
            session()->flash('error', 'Cannot delete: Email queue entry not found.');
            return;
        }

        try {
            $deleted = $this->repository->deleteByUuid($this->emailQueueUuid);
            
            if ($deleted) {
                session()->flash('success', 'Email queue entry deleted successfully.');
                redirect(route('email-queue.index'));
            } else {
                session()->flash('error', 'Failed to delete email queue entry.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting email queue entry: ' . $e->getMessage());
        }
    }



    public function updatedEmailStatus($value): void
    {
        if ($value === 'sent') {
            $this->email_date = now()->format('Y-m-d\TH:i');
        } else {
            $this->email_date = null;
        }
    }

    public function getFormattedEmailDate(): ?string
    {
        if (!$this->emailQueue || !$this->emailQueue->email_date) {
            return null;
        }


        return $this->emailQueue->email_date->format('d M Y H:i:s');
    }

    public function goBack(): void
    {
        $this->redirect(route('email-queue.index'));
    }

    public function render()
    {
        return view('livewire.email-queue-form', [
            'isEditing' => $this->isEditing,
            'statusOptions' => $this->statusOptions,
            'formattedEmailDate' => $this->getFormattedEmailDate(),
        ]);
    }
}