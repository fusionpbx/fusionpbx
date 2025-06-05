<?php

namespace App\Livewire;

use App\Models\Contact;
use App\Models\Group;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class ContactGroupForm extends Component
{
        public $contactUuid;
    public $selectedGroups = [];
    public $availableGroups = [];

    public function mount($contactUuid)
    {
        $this->contactUuid = $contactUuid;
        $this->loadGroups();
    }

    public function loadGroups()
    {
        $this->availableGroups = Group::all()->pluck('group_name', 'group_uuid')->toArray();

        $contact = Contact::with('groups')
            ->where('contact_uuid', $this->contactUuid)
            ->first();

        if ($contact) {
            $this->selectedGroups = $contact->groups->pluck('group_uuid')->toArray();
        }
    }

    public function save()
    {
        $contact = Contact::where('contact_uuid', $this->contactUuid)->first();

        if ($contact) {
            $contact->groups()->sync($this->selectedGroups);

            $this->dispatch('groupsSaved');
            $this->dispatch('contactSaved');
        }
    }
    public function render()
    {
        return view('livewire.contact-group-form');
    }
}
