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
            $this->settings = $contact->settings->map(function ($setting) {
                return [
                    'contact_setting_category' => $setting->contact_setting_category,
                    'contact_setting_subcategory' => $setting->contact_setting_subcategory,
                    'contact_setting_name' => $setting->contact_setting_name,
                    'contact_setting_value' => $setting->contact_setting_value,
                    'contact_setting_order' => $setting->contact_setting_order,
                    'contact_setting_enabled' => (bool)$setting->contact_setting_enabled,
                    'contact_setting_description' => $setting->contact_setting_description,
                ];
            })->toArray();
        } else {
            $this->addSetting();
        }
    }
    public function addSetting()
    {
        if(!auth()->user()->hasPermission('contact_setting_add')) {
            session()->flash('message', 'You do not have permission to add settings.');
            return;
        }

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
        if (!auth()->user()->hasPermission('contact_setting_delete')) {
            session()->flash('message', 'You do not have permission to remove settings.');
            return;
        }
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
                        'domain_uuid' => auth()->user()->domain_uuid,
                        'contact_setting_category' => $setting['contact_setting_category'],
                        'contact_setting_subcategory' => $setting['contact_setting_subcategory'],
                        'contact_setting_name' => $setting['contact_setting_name'],
                        'contact_setting_value' => $setting['contact_setting_value'],
                        'contact_setting_order' => empty($setting['contact_setting_order']) ? 0 : $setting['contact_setting_order'],
                        'contact_setting_enabled' => $setting['contact_setting_enabled'] ? 1 : 0,
                        'contact_setting_description' => $setting['contact_setting_description'],
                    ]);
                }
            }
            $this->dispatch('saveAttachment')->to(ContactAttachmentForm::class);
           session()->flash('message', 'Settings saved successfully.');
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
