<?php

namespace App\Livewire;

use App\Models\Contact;
use App\Models\ContactAddress;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class ContactAddressForm extends Component
{
    public $contactUuid;
    public $addresses = [];

    public $listeners = [
        'addressesSaved' => 'save'
    ];

    public function mount($contactUuid)
    {
        $this->contactUuid = $contactUuid;
        $this->loadAddresses();
    }

    public function loadAddresses()
    {
        $contact = Contact::with('addresses')
            ->where('contact_uuid', $this->contactUuid)
            ->first();

        if ($contact && $contact->addresses->count() > 0) {
            $this->addresses = $contact->addresses->map(function ($address) {
                return [
                    'address_street' => $address->address_street,
                    'address_primary' => (bool)$address->address_primary,
                    'address_extended' => $address->address_extended,
                    'address_region' => $address->address_region,
                    'address_postal_code' => $address->address_postal_code,
                    'address_locality' => $address->address_locality,
                    'address_country' => $address->address_country,
                    'address_type' => $address->address_type,
                    'address_label' => $address->address_label,
                    'address_description' => $address->address_description,
                    'address_city' => $address->address_city,
                ];
            })->toArray();
        } else {
            $this->addAddress();
        }
    }

    public function addAddress()
    {
        if (!auth()->user()->hasPermission('contact_address_add')) {
            session()->flash('message', 'You do not have permission to add addresses.');
            return;
        }
        $this->addresses[] = [
            'address_street' => '',
            'address_primary' => '',
            'address_extended' => '',
            'address_region' => '',
            'address_postal_code' => '',
            'address_country' => '',
            'address_locality' => '',
            'address_type' => '',
            'address_label' => '',
            'address_description' => '',
            'address_city' => '',
        ];
    }

    public function removeAddress($index)
    {
        unset($this->addresses[$index]);
        $this->addresses = array_values($this->addresses);
    }

    public function save()
    {
        try {
            ContactAddress::where('contact_uuid', $this->contactUuid)->delete();

            foreach ($this->addresses as $address) {
                if (!empty($address['address_street']) || !empty($address['address_locality'])) {
                    ContactAddress::create([
                        'contact_uuid' => $this->contactUuid,
                        'domain_uuid' => Session::get('domain_uuid'),
                        'address_street' => $address['address_street'],
                        'address_primary' => $address['address_primary'] ? 1 : 0,
                        'address_street' => $address['address_street'],
                        'address_extended' => $address['address_extended'],
                        'address_locality' => $address['address_locality'],
                        'address_region' => $address['address_region'],
                        'address_postal_code' => $address['address_postal_code'],
                        'address_country' => $address['address_country'],
                        'address_type' => $address['address_type'],
                        'address_label' => $address['address_label'],
                        'address_description' => $address['address_description']
                    ]);
                }
            }

            $this->dispatch('urlsSaved')->to(ContactUrlForm::class);
        } catch (\Throwable $e) {
            DB::rollBack();
            session()->flash('message', 'Error: ' . $e->getMessage());
            throw $e;
        }
    }
    public function render()
    {
        return view('livewire.contact-address-form');
    }
}
