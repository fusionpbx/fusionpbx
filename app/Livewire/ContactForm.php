<?php

namespace App\Livewire;

use App\Models\Contact;
use App\Models\ContactAddress;
use App\Models\ContactEmail;
use App\Models\ContactPhone;
use App\Models\ContactUrl;
use App\Models\Group;
use Livewire\Component;
use Illuminate\Support\Str;

class ContactForm extends Component
{
  public $contact;
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
    public $contactUrl;
    public $contactTimeZone;
    public $contactNote;
    

    public $emails = [];
    public $phones = [];
    public $addresses = [];
    public $selectedGroups = [];
    public $urls = [];
    

    public $availableGroups = [];
    public $contactTypes = ['customer', 'vendor', 'friend', 'family', 'subscriber', 'supplier', 'provider', 'user'];
    public $timeZones = [];
    
    protected $rules = [
        'contactType' => 'required',
        'contactOrganization' => 'nullable|string|max:255',
        'contactNameGiven' => 'nullable|string|max:255',
        'contactNameFamily' => 'nullable|string|max:255',
    ];

    public function mount($contact = null)
    {
        
        $this->loadTimeZones();
        
        $this->availableGroups = Group::all()->pluck('group_name', 'group_uuid')->toArray();
        
        if ($contact) {
            // dd($contact);
            $this->contactUuid = $contact->contact_uuid;
            $this->loadContact();
        } else {
            $this->contact = new Contact();
            $this->contactUuid = (string) Str::uuid();
            
            $this->addEmail();
            $this->addPhone();
            $this->addAddress();
            $this->addUrls();
        }
    }
    
    public function loadContact()
    {
        $this->contact = Contact::with(['emails', 'phones', 'addresses', 'groups', 'urls', 'settings'])
            ->where('contact_uuid', $this->contactUuid)
            ->firstOrFail();
            
        
        $this->contactType = $this->contact->contact_type;
        $this->contactOrganization = $this->contact->contact_organization;
        $this->contactNamePrefix = $this->contact->contact_name_prefix;
        $this->contactNameGiven = $this->contact->contact_name_given;
        $this->contactNameMiddle = $this->contact->contact_name_middle;
        $this->contactNameFamily = $this->contact->contact_name_family;
        $this->contactNameSuffix = $this->contact->contact_name_suffix;
        $this->contactNickname = $this->contact->contact_nickname;
        $this->contactTitle = $this->contact->contact_title;
        $this->contactRole = $this->contact->contact_role;
        $this->contactCategory = $this->contact->contact_category;
        $this->contactUrl = $this->contact->contact_url;
        $this->contactTimeZone = $this->contact->contact_time_zone;
        $this->contactNote = $this->contact->contact_note;
        
        
        $this->emails = $this->contact->emails->toArray() ?: [['email_address' => '', 'email_type' => 'work']];
        
        
        $this->phones = $this->contact->phones->toArray() ?: [['phone_number' => '', 'phone_type' => 'work']];
        
        
        $this->addresses = $this->contact->addresses->toArray() ?: [['address_street' => '', 'address_city' => '', 'address_region' => '', 'address_postal_code' => '', 'address_country' => '', 'address_type' => 'work']];

        $this->urls = $this->contact->urls->toArray() ?: [['url_label' => '', 'url_type' => 'work', 'url_description' => '']];
        
        $this->selectedGroups = $this->contact->groups->pluck('group_uuid')->toArray();
    }
    
    public function addEmail()
    {
        $this->emails[] = ['email_address' => '', 'email_type' => 'work'];
    }
    
    public function removeEmail($index)
    {
        unset($this->emails[$index]);
        $this->emails = array_values($this->emails);
    }
    
    public function addPhone()
    {
        $this->phones[] = ['phone_number' => '', 'phone_type' => 'work'];
    }
    
    public function removePhone($index)
    {
        unset($this->phones[$index]);
        $this->phones = array_values($this->phones);
    }
    
    public function addAddress()
    {
        $this->addresses[] = [
            'address_street' => '',
            'address_city' => '',
            'address_region' => '',
            'address_postal_code' => '',
            'address_country' => '',
            'address_type' => 'work'
        ];
    }
    
    public function removeAddress($index)
    {
        unset($this->addresses[$index]);
        $this->addresses = array_values($this->addresses);
    }

    public function addUrls()
    {
        $this->urls[] = [
            'url_label' => '',
            'url_type' => 'work',
            'url_description' => ''
        ];
    }

    public function removeUrls($index)
    {
        unset($this->urls[$index]);
        $this->urls = array_values($this->urls);
    }


    
    public function loadTimeZones()
    {
        $this->timeZones = \DateTimeZone::listIdentifiers();
    }
    
    public function save()
    {
        $this->validate();
        
        if ($this->contact->exists) {
            $this->contact->contact_type = $this->contactType;
            $this->contact->contact_organization = $this->contactOrganization;
            $this->contact->contact_name_prefix = $this->contactNamePrefix;
            $this->contact->contact_name_given = $this->contactNameGiven;
            $this->contact->contact_name_middle = $this->contactNameMiddle;
            $this->contact->contact_name_family = $this->contactNameFamily;
            $this->contact->contact_name_suffix = $this->contactNameSuffix;
            $this->contact->contact_nickname = $this->contactNickname;
            $this->contact->contact_title = $this->contactTitle;
            $this->contact->contact_role = $this->contactRole;
            $this->contact->contact_category = $this->contactCategory;
            $this->contact->contact_url = $this->contactUrl;
            $this->contact->contact_time_zone = $this->contactTimeZone;
            $this->contact->contact_note = $this->contactNote;
            $this->contact->save();
        } else {
            $this->contact = Contact::create([
                'contact_uuid' => $this->contactUuid,
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
                'contact_url' => $this->contactUrl,
                'contact_time_zone' => $this->contactTimeZone,
                'contact_note' => $this->contactNote,
            ]);
        }
        
        $this->saveEmails();
        
        $this->savePhones();
        
        $this->saveAddresses();
        
        $this->contact->groups()->sync($this->selectedGroups);
        
        // session()->flash('message', 'Contacto guardado correctamente.');
        return redirect()->route('contacts.index');
    }
    
    private function saveEmails()
    {
        $this->contact->emails()->delete();
        
        foreach ($this->emails as $email) {
            if (!empty($email['email_address'])) {
                ContactEmail::create([
                    'contact_uuid' => $this->contactUuid,
                    'email_address' => $email['email_address'],
                    'email_type' => $email['email_type'],
                ]);
            }
        }
    }
    
    private function savePhones()
    {
        $this->contact->phones()->delete();
        
        foreach ($this->phones as $phone) {
            if (!empty($phone['phone_number'])) {
                ContactPhone::create([
                    'contact_uuid' => $this->contactUuid,
                    'phone_number' => $phone['phone_number'],
                    'phone_type' => $phone['phone_type'],
                ]);
            }
        }
    }
    
    private function saveAddresses()
    {
        $this->contact->addresses()->delete();
        
        foreach ($this->addresses as $address) {
            if (!empty($address['address_street']) || !empty($address['address_city'])) {
                ContactAddress::create([
                    'contact_uuid' => $this->contactUuid,
                    'address_street' => $address['address_street'],
                    'address_city' => $address['address_city'],
                    'address_region' => $address['address_region'],
                    'address_postal_code' => $address['address_postal_code'],
                    'address_country' => $address['address_country'],
                    'address_type' => $address['address_type'],
                ]);
            }
        }
    }

    public function saveUrl()
    {
        $this->contact->urls()->delete();

        foreach ($this->urls as $url) {
            if (!empty($url['url'])) {
                ContactUrl::create([
                    'contact_uuid' => $this->contactUuid,
                    'url_label' => $url['url_label'],
                    'url_type' => $url['url_type'],
                    'url_description' => $url['url_description'],
                ]);
            }
        }
    }
    
    public function render()
    {
        return view('livewire.contact-form');
    }
}
