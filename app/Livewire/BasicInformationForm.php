<?php

namespace App\Livewire;

use App\Models\Contact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Livewire\Component;

class BasicInformationForm extends Component
{
    public $contactUuid;
    public $contactType;
    public $contactOrganization;
    public $contactNamePrefix;
    public $contactNameGiven;
    public $contactNameMiddle;
    public $contactNameFamily;
    public $contactNameSuffix;
    public $contactNickname;
    public $contactTitle;
    public $contactRole;
    public $contactCategory;
    public $contactTimeZone;
    public $contactNote;

    public $contactTypes = ['customer', 'contractor','friend', 'lead', 'member', 'family', 'subscriber', 'supplier', 'provider', 'user', 'volunteer'];
    public $timeZones = [];

    protected $rules = [
        'contactType' => 'required',
        'contactOrganization' => 'nullable|string|max:255',
        'contactNameGiven' => 'nullable|string|max:255',
        'contactNameFamily' => 'nullable|string|max:255',
    ];

    public $listeners = [
        'saveBasicInfo' => 'save'
    ];

    public function mount($contactUuid)
    {
        $this->contactUuid = $contactUuid;
        $this->loadTimeZones();

        $contact = Contact::where('contact_uuid', $this->contactUuid)->first();

        if ($contact) {
            $this->contactType = $contact->contact_type;
            $this->contactOrganization = $contact->contact_organization;
            $this->contactNamePrefix = $contact->contact_name_prefix;
            $this->contactNameGiven = $contact->contact_name_given;
            $this->contactNameMiddle = $contact->contact_name_middle;
            $this->contactNameFamily = $contact->contact_name_family;
            $this->contactNameSuffix = $contact->contact_name_suffix;
            $this->contactNickname = $contact->contact_nickname;
            $this->contactTitle = $contact->contact_title;
            $this->contactRole = $contact->contact_role;
            $this->contactCategory = $contact->contact_category;
            $this->contactTimeZone = $contact->contact_time_zone;
            $this->contactNote = $contact->contact_note;
        }
    }

    public function loadTimeZones()
    {
        $this->timeZones = \DateTimeZone::listIdentifiers();
    }

    public function save()
    {
        try {
            $this->validate();
            $contact = Contact::where('contact_uuid', $this->contactUuid)->first();
            if ($contact) {
                $contact->domain_uuid = Session::get('domain_uuid');
                $contact->contact_type = $this->contactType;
                $contact->contact_organization = $this->contactOrganization;
                $contact->contact_name_prefix = $this->contactNamePrefix;
                $contact->contact_name_given = $this->contactNameGiven;
                $contact->contact_name_middle = $this->contactNameMiddle;
                $contact->contact_name_family = $this->contactNameFamily;
                $contact->contact_name_suffix = $this->contactNameSuffix;
                $contact->contact_nickname = $this->contactNickname;
                $contact->contact_title = $this->contactTitle;
                $contact->contact_role = $this->contactRole;
                $contact->contact_category = $this->contactCategory;
                $contact->contact_time_zone = $this->contactTimeZone;
                $contact->contact_note = $this->contactNote;
                $contact->save();
            } else {
                Contact::create([
//                    'contact_uuid' => Str::uuid(),
                    'domain_uuid' => Session::get('domain_uuid'),
                    'contact_type' => $this->contactType,
                    'contact_organization' => $this->contactOrganization,
                    'contact_name_prefix' => $this->contactNamePrefix,
                    'contact_name_given' => $this->contactNameGiven,
                    'contact_name_middle' => $this->contactNameMiddle,
                    'contact_name_family' => $this->contactNameFamily,
                    'contact_name_suffix' => $this->contactNameSuffix,
                    'contact_nickname' => $this->contactNickname,
                    'contact_title' => $this->contactTitle,
                    'contact_role' => $this->contactRole,
                    'contact_category' => $this->contactCategory,
                    'contact_time_zone' => $this->contactTimeZone,
                    'contact_note' => $this->contactNote,
                ]);
            }
            $this->dispatch('emailsSaved')->to(ContactEmailForm::class);
        } catch (\Throwable $e) {
           session()->flash('message', 'Error: ' . $e->getMessage());
           throw $e;
        }
    }
    public function render()
    {
        return view('livewire.basic-information-form');
    }
}
