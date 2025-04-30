<?php

namespace App\Livewire;

use App\Http\Requests\SipProfileRequest;
use App\Repositories\SipProfileRepository;
use Livewire\Component;
use Illuminate\Contracts\View\View;

class SipProfileForm extends Component
{
    public $sipProfile;
    public string $sip_profile_uuid;
    public string $sip_profile_name;
    public ?string $sip_profile_hostname = '';
    public bool $sip_profile_enabled = true;
    public string $sip_profile_description;

    public ?array $domains = [];
    public ?array $settings = [];

    public array $domainsToDelete = [];
    public array $settingsToDelete = [];

    public bool $canViewDomain = false;
    public bool $canAddDomain = false;
    public bool $canEditDomain = false;
    public bool $canDeleteDomain = false;

    public bool $canViewSetting = false;
    public bool $canAddSetting = false;
    public bool $canEditSetting = false;
    public bool $canDeleteSetting = false;
    
    protected $sipProfileRepository;

    public function boot(SipProfileRepository $sipProfileRepository)
    {
        $this->sipProfileRepository = $sipProfileRepository;
    }

    public function rules() 
    {
        $request = new SipProfileRequest();
        return $request->rules();
    }

    public function mount($sipProfile = null) : void
    {
        if ($sipProfile) {
            $this->sipProfile = $sipProfile;
            $this->sip_profile_uuid = $sipProfile->sip_profile_uuid;
            $this->sip_profile_name = $sipProfile->sip_profile_name;
            $this->sip_profile_hostname = $sipProfile->sip_profile_hostname;
            $this->sip_profile_enabled = $sipProfile->sip_profile_enabled;
            $this->sip_profile_description = $sipProfile->sip_profile_description;

            foreach ($sipProfile->sipprofiledomains as $domain) {
                $this->domains[] = [
                    'sip_profile_domain_uuid' => $domain->sip_profile_domain_uuid,
                    'sip_profile_domain_name' => $domain->sip_profile_domain_name,
                    'sip_profile_domain_alias' => $domain->sip_profile_domain_alias,
                    'sip_profile_domain_parse' => $domain->sip_profile_domain_parse,
                ];
            }

            foreach ($sipProfile->sipprofilesettings as $setting) {
                $this->settings[] = [
                    'sip_profile_setting_uuid' => $setting->sip_profile_setting_uuid,
                    'sip_profile_setting_name' => $setting->sip_profile_setting_name,
                    'sip_profile_setting_value' => $setting->sip_profile_setting_value,
                    'sip_profile_setting_enabled' => $setting->sip_profile_setting_enabled,
                    'sip_profile_setting_description' => $setting->sip_profile_setting_description,
                ];
            }
        }

        $this->loadPermissions();

        if (empty($this->domains) && $this->canAddDomain) {
            $this->addDomain();
        }

        if (empty($this->settings) && $this->canAddSetting) {
            $this->addSetting();
        }
    }

    private function loadPermissions() : void
    {
        $user = auth()->user();

        $this->canViewDomain = $user->hasPermission('sip_profile_domain_view');
        $this->canAddDomain = $user->hasPermission('sip_profile_domain_add');
        $this->canEditDomain = $user->hasPermission('sip_profile_domain_edit');
        $this->canDeleteDomain = $user->hasPermission('sip_profile_domain_delete');

        $this->canViewSetting = $user->hasPermission('sip_profile_setting_view');
        $this->canAddSetting = $user->hasPermission('sip_profile_setting_add');
        $this->canEditSetting = $user->hasPermission('sip_profile_setting_edit');
        $this->canDeleteSetting = $user->hasPermission('sip_profile_setting_delete');
    }

    public function addDomain() : void
    {
        if (!$this->canAddDomain) {
            session()->flash('error', 'You do not have permission to add domains.');
            return;
        }

        $this->domains[] = [
            'sip_profile_domain_uuid' => '',
            'sip_profile_domain_name' => '',
            'sip_profile_domain_alias' => 'true',
            'sip_profile_domain_parse' => 'true',
        ];
    }

    public function removeDomain($index) : void
    {
        if (!$this->canDeleteDomain) {
            session()->flash('error', 'You do not have permission to delete domains.');
            return;
        }
        
        if (isset($this->domains[$index]['sip_profile_domain_uuid']) && !empty($this->domains[$index]['sip_profile_domain_uuid'])) {
            $this->domainsToDelete[] = $this->domains[$index]['sip_profile_domain_uuid'];
        }
        
        unset($this->domains[$index]);
        $this->domains = array_values($this->domains);
    }

    public function addSetting() : void
    {
        if (!$this->canAddSetting) {
            session()->flash('error', 'You do not have permission to add settings.');
            return;
        }

        $this->settings[] = [
            'sip_profile_setting_uuid' => '',
            'sip_profile_setting_name' => '',
            'sip_profile_setting_value' => '',
            'sip_profile_setting_enabled' => 'true',
            'sip_profile_setting_description' => '',
        ];
    }

    public function removeSetting($index) : void
    {
        if (!$this->canDeleteSetting) {
            session()->flash('error', 'You do not have permission to delete settings.');
            return;
        }

        if (isset($this->settings[$index]['sip_profile_setting_uuid']) && !empty($this->settings[$index]['sip_profile_setting_uuid'])) {
            $this->settingsToDelete[] = $this->settings[$index]['sip_profile_setting_uuid'];
        }

        unset($this->settings[$index]);
        $this->settings = array_values($this->settings);
    }

    public function save() : void
    {
        $this->validate();

        $filteredDomains = collect($this->domains)->filter(function ($domain) {
            return !empty($domain['sip_profile_domain_name']);
        })->toArray();

        $filteredSettings = collect($this->settings)->filter(function ($setting) {
            return !empty($setting['sip_profile_setting_name']);
        })->toArray();

        $hasNewDomains = collect($filteredDomains)->filter(fn($d) => empty($d['sip_profile_domain_uuid']))->count() > 0;
        if ($hasNewDomains && !$this->canAddDomain) {
            session()->flash('error', 'You do not have permission to add domains.');
            return;
        }

        if (!empty($this->domainsToDelete) && !$this->canDeleteDomain) {
            session()->flash('error', 'You do not have permission to delete domains.');
            return;
        }

        $hasNewSettings = collect($filteredSettings)->filter(fn($s) => empty($s['sip_profile_setting_uuid']))->count() > 0;
        if ($hasNewSettings && !$this->canAddSetting) {
            session()->flash('error', 'You do not have permission to add settings.');
            return;
        }
        
        if (!empty($this->settingsToDelete) && !$this->canDeleteSetting) {
            session()->flash('error', 'You do not have permission to delete settings.');
            return;
        }

        $profileData = [
            'sip_profile_name' => $this->sip_profile_name,
            'sip_profile_hostname' => $this->sip_profile_hostname,
            'sip_profile_enabled' => $this->sip_profile_enabled,
            'sip_profile_description' => $this->sip_profile_description,
        ];

        try {
            if ($this->sipProfile) {
                $this->sipProfileRepository->update(
                    $this->sipProfile->sip_profile_uuid,
                    $profileData,
                    $filteredDomains,
                    $filteredSettings,
                    $this->domainsToDelete,
                    $this->settingsToDelete
                );
                session()->flash('message', 'SIP Profile updated successfully.');
            } else {
                $this->sipProfileRepository->create(
                    $profileData,
                    $filteredDomains,
                    $filteredSettings
                );
                session()->flash('message', 'SIP Profile created successfully.');
            }

            redirect()->route('sipprofiles.index');
        } catch (\Exception $e) {
            session()->flash('error', 'Error saving SIP Profile: ' . $e->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.sip-profile-form');
    }
}