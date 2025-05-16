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
            $this->emails = $contact->emails->toArray();
        } else {
            $this->addEmail();
        }
    }
    
    public function addEmail()
    {
        $this->emails[] = [
            'email_address' => '',
            'email_label' => '',
            'email_primary' => false,
            'email_description' => '',
        ];
    }
    
    public function removeEmail($index)
    {
        unset($this->emails[$index]);
        $this->emails = array_values($this->emails);
    }
    
    public function save()
    {
        try {
        ContactEmail::where('contact_uuid', $this->contactUuid)->delete();
        
        foreach ($this->emails as $email) {
            if (!empty($email['email_address'])) {
                ContactEmail::create([
                    'contact_uuid' => $this->contactUuid,
                    'email_address' => $email['email_address'] ?? '',
                    'email_label' => $email['email_label'] ?? '',
                    'email_primary' => $email['email_primary'] ?? false,
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
