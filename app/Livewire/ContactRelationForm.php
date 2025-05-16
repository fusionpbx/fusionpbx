<?php

namespace App\Livewire;

use App\Models\Contact;
use App\Models\ContactRelation;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class ContactRelationForm extends Component
{
    public $contactUuid;
    public $relations = [];
    public $searchTerm = '';
    public $searchResults = [];

    public $listeners = [
        'relationsSaved' => 'save',
    ];

    public function mount($contactUuid)
    {
        $this->contactUuid = $contactUuid;
        $this->loadRelations();
    }

    public function loadRelations()
    {
        $contact = Contact::with('relations')
            ->where('contact_uuid', $this->contactUuid)
            ->first();

        if ($contact && $contact->relations->count() > 0) {
            $this->relations = $contact->relations->toArray();
        } else {
            $this->addRelation();
        }
    }

    public function addRelation()
    {
        $this->relations[] = [
            'relation_label' => '',
            'relation_contact_uuid' => '',
            'contact_name' => '', 
        ];
    }

    public function removeRelation($index)
    {
        unset($this->relations[$index]);
        $this->relations = array_values($this->relations);
    }

    public function updatedSearchTerm()
    {
        if (strlen($this->searchTerm) >= 2) {
            $this->searchResults = Contact::where(function ($query) {
                $query->where('contact_name_given', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('contact_name_family', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('contact_organization', 'like', '%' . $this->searchTerm . '%');
            })
                ->where('contact_uuid', '!=', $this->contactUuid) // Excluir el contacto actual
                ->limit(10)
                ->get()
                ->map(function ($contact) {
                    $displayName = $contact->contact_organization ?:
                        trim($contact->contact_name_given . ' ' . $contact->contact_name_family);

                    return [
                        'id' => $contact->contact_uuid,
                        'name' => $displayName
                    ];
                })
                ->toArray();
        } else {
            $this->searchResults = [];
        }
    }

    public function selectContact($contactId, $contactName, $index)
    {
        $this->relations[$index]['relation_contact_uuid'] = $contactId;
        $this->relations[$index]['contact_name'] = $contactName;
        $this->searchTerm = '';
        $this->searchResults = [];
    }

    public function save()
    {
        try {
            ContactRelation::where('contact_uuid', $this->contactUuid)->delete();

            foreach ($this->relations as $relation) {
                if (!empty($relation['relation_contact_uuid'])) {
                    ContactRelation::create([
                        'contact_uuid' => $this->contactUuid,
                        'relation_label' => $relation['relation_label'],
                        'relation_contact_uuid' => $relation['relation_contact_uuid'],
                    ]);
                }
            }

            $this->dispatch('settingsSaved')->to(ContactSettingForm::class);
        } catch (\Throwable $e) {
            throw $e;
            session()->flash('message', 'Error: ' . $e->getMessage());
            DB::rollBack();
        }
    }

    public function render()
    {
        return view('livewire.contact-relation-form');
    }
}
