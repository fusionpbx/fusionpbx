<?php

namespace App\Livewire;

use App\Models\Contact;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Illuminate\Support\Str;

class ContactForm extends Component
{
    public $contact;
    public $contactUuid;
    public $availableGroups = [];
    public $formStatus = "";

    public $listeners = [
        'formStatusChanged' => 'handleFormStatusChange'
    ];

    public function mount($contact = null)
    {
        if ($contact) {
            $this->contactUuid = $contact->contact_uuid;
            $this->contact = Contact::with(['emails', 'phones', 'addresses', 'groups', 'urls', 'settings'])
                ->where('contact_uuid', $this->contactUuid)
                ->firstOrFail();
        } else {
            $this->contact = new Contact();
            $this->contactUuid = (string) Str::uuid();
        }
    }

    public function handleFormStatusChange($status)
    {
        $this->formStatus = $status;
    }

    public function saveTest()
    {
        $this->dispatch('saveBasicInfo')->to(ContactBasicInformationForm::class);
    }

    public function handleContactSaved()
    {
        return redirect()->route('contacts.index');
    }

    public function render()
    {
        return view('livewire.contact-form');
    }
}
