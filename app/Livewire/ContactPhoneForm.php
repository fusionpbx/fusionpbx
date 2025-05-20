<?php

namespace App\Livewire;

use App\Models\Contact;
use App\Models\ContactPhone;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class ContactPhoneForm extends Component
{
    public $contactUuid;
    public $phones = [];

    public $listeners = [
        'phonesSaved' => 'save'
    ];

    public function mount($contactUuid)
    {
        $this->contactUuid = $contactUuid;
        $this->loadPhones();
    }

    public function loadPhones()
    {
        $contact = Contact::with('phones')
            ->where('contact_uuid', $this->contactUuid)
            ->first();

        if ($contact && $contact->phones->count() > 0) {
            $this->phones = $contact->phones->map(function ($phone) {
                return [
                    'phone_number' => $phone->phone_number,
                    'phone_label' => $phone->phone_label,
                    'phone_type_voice' => (bool)$phone->phone_type_voice,
                    'phone_type_video' => (bool)$phone->phone_type_video,
                    'phone_type_text' => (bool)$phone->phone_type_text,
                    'phone_type_fax' => (bool)$phone->phone_type_fax,
                    'phone_primary' => (bool)$phone->phone_primary,
                    'phone_speed_dial' => $phone->phone_speed_dial,
                    'phone_country_code' => $phone->phone_country_code,
                    'phone_extension' => $phone->phone_extension,
                    'phone_description' => $phone->phone_description,
                ];
            })->toArray();
        } else {
            $this->addPhone();
        }
    }

    public function addPhone()
    {
        $this->phones[] = [
            'phone_number' => '',
            'phone_label' => '',
            'phone_type_voice' => '',
            'phone_type_video' => '',
            'phone_type_text' => '',
            'phone_type_fax' => '',
            'phone_speed_dial' => '',
            'phone_country_code' => '',
            'phone_extension' => '',
            'phone_primary' => '',
            'phone_description' => '',

        ];
    }

    public function removePhone($index)
    {
        unset($this->phones[$index]);
        $this->phones = array_values($this->phones);
    }

    public function save()
    {
        try {
            ContactPhone::where('contact_uuid', $this->contactUuid)->delete();

            foreach ($this->phones as $phone) {
                if (!empty($phone['phone_number'])) {
                    ContactPhone::create([
                        'contact_uuid' => $this->contactUuid,
                        'domain_uuid' => auth()->user()->domain_uuid,
                        'phone_number' => $phone['phone_number'] ?? '',
                        'phone_label' => $phone['phone_label'] ?? '',
                        'phone_type_voice' => $phone['phone_type_voice'] ? 1 : 0,
                        'phone_type_video' => $phone['phone_type_video'] ? 1 : 0,
                        'phone_type_text' => $phone['phone_type_text'] ? 1 : 0,
                        'phone_type_fax' => $phone['phone_type_fax'] ? 1 : 0,
                        'phone_speed_dial' => $phone['phone_speed_dial'] ?? '',
                        'phone_country_code' => $phone['phone_country_code'] ?? '',
                        'phone_extension' => $phone['phone_extension'] ?? '',
                        'phone_primary' => $phone['phone_primary'] ? 1 : 0,
                        'phone_description' => $phone['phone_description'] ?? '',
                    ]);
                }
            }
            $this->dispatch('addressesSaved')->to(ContactAddressForm::class);
        } catch (\Throwable $e) {
            DB::rollBack();
            session()->flash('message', 'Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function render()
    {
        return view('livewire.contact-phone-form');
    }
}
