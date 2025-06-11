<?php

namespace App\Livewire;

use App\Http\Requests\ContactRelationRequest;
use App\Models\Contact;
use App\Models\ContactRelation;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class ContactRelationForm extends Component
{
    public $contactUuid;
    public $relations = [];

    public $listeners = [
        'relationsSaved' => 'save',
    ];

    public function rules()
    {
        $request = new ContactRelationRequest();
        return $request->rules();
    }

    public function mount($contactUuid)
    {
        $this->contactUuid = $contactUuid;
        $this->loadRelations();
    }

    public function loadRelations()
    {
        $contact = Contact::where('contact_uuid', $this->contactUuid)
            ->first();

        $relations = ContactRelation::where('contact_uuid', $this->contactUuid)
            ->get();

        if ($relations->count() > 0) {
            $this->relations = $relations->map(function($relation) {
                $relatedContact = Contact::where('contact_uuid', $relation->relation_contact_uuid)->first();

                $displayName = '';
                if ($relatedContact) {
                    $displayName = $relatedContact->contact_organization ?:
                        trim($relatedContact->contact_name_given . ' ' . $relatedContact->contact_name_family);
                }

                return [
                    'relation_label' => $relation->relation_label,
                    'relation_contact_uuid' => $relation->relation_contact_uuid,
                    'contact_name' => $displayName
                ];
            })->toArray();
        } else {
            $this->addRelation();
        }
    }

    public function addRelation()
    {
        if(!auth()->user()->hasPermission('contact_relation_add')) {
            session()->flash('message', 'You do not have permission to add relations.');
            return;
        }

        $this->relations[] = [
            'relation_label' => '',
            'relation_contact_uuid' => '',
            'contact_name' => '',
        ];
        $this->dispatch('relation-added');
    }

    public function removeRelation($index)
    {
        if(!auth()->user()->hasPermission('contact_relation_delete')) {
            session()->flash('message', 'You do not have permission to remove relations.');
            return;
        }
        unset($this->relations[$index]);
        $this->relations = array_values($this->relations);

        $this->dispatch('relation-removed');
    }

    public function selectContact($contactId, $contactName, $index)
    {
        $this->relations[$index]['relation_contact_uuid'] = $contactId;
        $this->relations[$index]['contact_name'] = $contactName;
    }

    public function save()
    {
        $this->validate();
        try {
            ContactRelation::where('contact_uuid', $this->contactUuid)->delete();

            foreach ($this->relations as $relation) {
                if (!empty($relation['relation_contact_uuid']) && !empty($relation['relation_label'])) {
                    ContactRelation::create([
                        'contact_uuid' => $this->contactUuid,
                        'domain_uuid' => Session::get('domain_uuid'),
                        'relation_label' => $relation['relation_label'],
                        'relation_contact_uuid' => $relation['relation_contact_uuid'],
                    ]);
                }
            }

            $this->dispatch('settingsSaved')->to(ContactSettingForm::class);
            session()->flash('message', 'Relations saved successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.contact-relation-form');
    }
}
