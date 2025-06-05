<?php

namespace App\Livewire;

use App\Models\Contact;
use App\Models\ContactEmail;
use App\Models\ContactPhone;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class ContactEmailForm extends Component
{
    public $contactUuid;
    public $emails = [];

    public $listeners = [
        'emailsSaved'=> 'save'
    ];

    public function mount($contactUuid)
    {
        $this->contactUuid = $contactUuid;
        $this->loadEmails();

    }

    public function loadEmails()
    {
        $contact = Contact::with('emails')
            ->where('contact_uuid', $this->contactUuid)
            ->first();

        if ($contact && $contact->emails->count() > 0) {
            $this->emails = $contact->emails->map(function ($email) {
                return [
                    'email_address' => $email->email_address,
                    'email_label' => $email->email_label,
                    'email_primary' => (bool)$email->email_primary,
                    'email_description' => $email->email_description,
                ];
            })->toArray();
        } else {
            $this->addEmail();
        }
    }

    public function addEmail()
    {
        if(auth()->user()->hasPermission('contact_email_add')) {
            $this->emails[] = [
                'email_address' => '',
                'email_label' => '',
                'email_primary' => '',
                'email_description' => '',
            ];
        } else {
            session()->flash('message', 'You do not have permission to add email addresses.');
        }
    }

    public function removeEmail($index)
    {
        if (auth()->user()->hasPermission('contact_email_delete')) {
            unset($this->emails[$index]);
            $this->emails = array_values($this->emails);
        } else {
            session()->flash('message', 'You do not have permission to remove email addresses.');
        }
    }

    public function save()
    {
        try {
        ContactEmail::where('contact_uuid', $this->contactUuid)->delete();

        foreach ($this->emails as $email) {
            if (!empty($email['email_address'])) {
                ContactEmail::create([
                    'contact_uuid' => $this->contactUuid,
                    'domain_uuid' => Session::get('domain_uuid'),
                    'email_address' => $email['email_address'] ?? '',
                    'email_label' => $email['email_label'] ?? '',
                    'email_primary' => $email['email_primary'] ? 1 : 0,
                    'email_description' => $email['email_description'] ?? '',
                ]);
            }
        }
        $this->dispatch('phonesSaved')->to(ContactPhoneForm::class);

        } catch (\Throwable $e) {
            DB::rollBack();
            session()->flash('message', 'Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function render()
    {
        return view('livewire.contact-email-form');
    }
}
