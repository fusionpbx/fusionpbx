<?php

namespace App\Livewire;

use App\Models\Contact;
use App\Models\ContactSetting;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ContactSettingForm extends Component
{
    public $contactUuid;
    public $settings = [];

    public $listeners = [
        'settingsSaved' => 'save',
    ];

    public function mount($contactUuid)
    {
        $this->contactUuid = $contactUuid;
        $this->loadSettings();
    }
    public function loadSettings()
    {
        $contact = Contact::where('contact_uuid', $this->contactUuid)->first();
        if ($contact && $contact->settings->count() > 0) {
            $this->settings = $contact->settings->toArray();
        } else {
            $this->addSetting();
        }
    }
    public function addSetting()
    {
        $this->settings[] = [
            'contact_setting_category' => '',
            'contact_setting_subcategory' => '',
            'contact_setting_name' => '',
            'contact_setting_value' => '',
            'contact_setting_order' => '',
            'contact_setting_enabled' => '',
            'contact_setting_description' => '',
        ];
    }
    
    public function removeSetting($index)
    {
        unset($this->settings[$index]);
        $this->settings = array_values($this->settings);
    }
    public function save()
    {
        try {
            ContactSetting::where('contact_uuid', $this->contactUuid)->delete();
            foreach ($this->settings as $setting) {
                if (!empty($setting['contact_setting_name'])) {
                    ContactSetting::create([
                        'contact_uuid' => $this->contactUuid,
                        'contact_setting_category' => $setting['contact_setting_category'],
                        'contact_setting_subcategory' => $setting['contact_setting_subcategory'],
                        'contact_setting_name' => $setting['contact_setting_name'],
                        'contact_setting_value' => $setting['contact_setting_value'],
                        'contact_setting_order' => empty($setting['contact_setting_order']) ? 0 : $setting['contact_setting_order'],
                        'contact_setting_enabled' => $setting['contact_setting_enabled'],
                        'contact_setting_description' => $setting['contact_setting_description'],
                    ]);
                }
            }
            redirect()->route('contacts.index');
        } catch (\Throwable $e) {
            DB::rollBack();
            session()->flash('message', 'Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function render()
    {
        return view('livewire.contact-setting-form');
    }
}
