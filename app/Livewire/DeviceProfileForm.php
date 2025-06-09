<?php

namespace App\Livewire;

use Livewire\Component;
use App\Repositories\DeviceProfileRepository;
use App\Models\DeviceProfile;
use App\Facades\Setting;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DeviceProfileForm extends Component
{
    public $deviceProfileUuid;
    public $deviceProfile;
    public $isEditing = false;

    // Device Profile properties
    public $domain_uuid;
    public $device_profile_name;
    public $device_profile_enabled = 'true';
    public $device_profile_description;

    // Related data
    public $profileKeys = [];
    public $profileSettings = [];
    public $showKeySubtype = false;

    // Dropdown data
    public $availableDomains = [];
    public $vendors = [];
    public $vendorFunctions = [];

    // UI state
    public $showAdvanced = false;

    protected $deviceProfileRepository;

    public function boot(DeviceProfileRepository $deviceProfileRepository)
    {
        $this->deviceProfileRepository = $deviceProfileRepository;
    }

    public function mount($deviceProfileUuid = null)
    {
        $this->deviceProfileUuid = $deviceProfileUuid;
        $this->isEditing = !is_null($deviceProfileUuid);

        $this->loadDropdownData();

        if ($this->isEditing) {
            $this->loadDeviceProfile();
        } else {
            $this->initializeDefaults();
        }
    }

    protected function loadDeviceProfile()
    {
        $this->deviceProfile = $this->deviceProfileRepository->findByUuid($this->deviceProfileUuid, true);

        if (!$this->deviceProfile) {
            session()->flash('error', 'Device profile not found.');
            return redirect()->route('devices_profiles.index');
        }

        $this->domain_uuid = $this->deviceProfile->domain_uuid;
        $this->device_profile_name = $this->deviceProfile->device_profile_name;
        $this->device_profile_enabled = $this->deviceProfile->device_profile_enabled ?? 'true';
        $this->device_profile_description = $this->deviceProfile->device_profile_description;

        $this->profileKeys = $this->deviceProfileRepository->getProfileKeys($this->deviceProfileUuid);
        $this->profileSettings = $this->deviceProfileRepository->getProfileSettings($this->deviceProfileUuid);

        $this->showKeySubtype = $this->deviceProfileRepository->shouldShowKeySubtype($this->deviceProfileUuid);
    }

    protected function initializeDefaults()
    {
        $user = auth()->user();
        $this->domain_uuid = $user->domain_uuid;
        $this->device_profile_enabled = 'true';

        // Add empty rows for keys and settings
        $this->addEmptyKeysRows(5);
        $this->addEmptySettingsRow();
    }

    protected function loadDropdownData()
    {
        $this->vendors = $this->deviceProfileRepository->getDeviceVendors();
        $this->vendorFunctions = $this->deviceProfileRepository->getVendorFunctions();
        $this->availableDomains = $this->deviceProfileRepository->getDomain();

        if (!$this->isEditing) {
            $this->domain_uuid = auth()->user()->domain_uuid;
        }
    }

    public function addProfileKey()
    {
        $this->profileKeys[] = [
            'device_profile_key_uuid' => '',
            'profile_key_category' => '',
            'profile_key_id' => '',
            'profile_key_vendor' => '',
            'profile_key_type' => '',
            'profile_key_subtype' => '',
            'profile_key_line' => '',
            'profile_key_value' => '',
            'profile_key_extension' => '',
            'profile_key_protected' => 'false',
            'profile_key_label' => '',
            'profile_key_icon' => '',
        ];
    }

    public function removeProfileKey($index)
    {
        if (isset($this->profileKeys[$index])) {
            $key = $this->profileKeys[$index];

            if (!empty($key['device_profile_key_uuid']) && $this->isEditing) {
                $this->deviceProfileRepository->deleteSpecificKeys(
                    $this->deviceProfileUuid,
                    [$key['device_profile_key_uuid']]
                );
            }

            unset($this->profileKeys[$index]);
            $this->profileKeys = array_values($this->profileKeys);
        }
    }

    public function addProfileSetting()
    {
        $this->profileSettings[] = [
            'device_profile_setting_uuid' => '',
            'profile_setting_name' => '',
            'profile_setting_value' => '',
            'profile_setting_enabled' => 'true',
            'profile_setting_description' => '',
        ];
    }

    public function removeProfileSetting($index)
    {
        if (isset($this->profileSettings[$index])) {
            $setting = $this->profileSettings[$index];

            if (!empty($setting['device_profile_setting_uuid']) && $this->isEditing) {
                $this->deviceProfileRepository->deleteSpecificSettings(
                    $this->deviceProfileUuid,
                    [$setting['device_profile_setting_uuid']]
                );
            }

            unset($this->profileSettings[$index]);
            $this->profileSettings = array_values($this->profileSettings);
        }
    }

    public function addEmptyKeysRows($count = 1)
    {
        $this->profileKeys = $this->deviceProfileRepository->addEmptyKeysRows(
            $this->profileKeys,
            $count,
            $this->deviceProfileUuid ?? '',
            $this->domain_uuid ?? ''
        );
    }

    public function addEmptySettingsRow()
    {
        $this->profileSettings = $this->deviceProfileRepository->addEmptySettingsRow(
            $this->profileSettings,
            $this->deviceProfileUuid ?? '',
            $this->domain_uuid ?? ''
        );
    }

    public function updatedProfileKeys()
    {
        foreach ($this->profileKeys as $key) {
            if (isset($key['profile_key_vendor']) && $key['profile_key_vendor'] === 'fanvil') {
                $this->showKeySubtype = true;
                break;
            }
        }
    }

    public function getCategoryOptionsProperty()
    {
        return collect($this->profileKeys)->map(function ($row) {
            $vendor = strtolower($row['profile_key_vendor'] ?? '');

            $options = [
                'line' => 'Line',
                'programmable' => 'Programmable',
            ];

            if ($vendor !== 'polycom') {
                $options['memory'] = 'Memory';

                if (empty($vendor)) {
                    foreach (range(0, 6) as $i) {
                        $options[$i === 0 ? 'expansion' : "expansion-{$i}"] = 'Expansion' . ($i === 0 ? '' : " {$i}");
                    }
                } elseif ($vendor === 'grandstream') {
                    $options['expansion'] = 'Expansion';
                } elseif (in_array($vendor, ['yealink', 'cisco'])) {
                    foreach (range(1, 6) as $i) {
                        $options["expansion-{$i}"] = "Expansion {$i}";
                    }
                }
            }

            return $options;
        })->toArray();
    }

    public function getGroupedVendorFunctionsProperty()
    {
        return collect($this->vendorFunctions)
            ->groupBy('vendor_name')
            ->map(function ($items, $vendor) {
                return $items->map(function ($item) {
                    return [
                        'value' => $item['value'],
                        'label' => $item['type'] . ($item['subtype'] ? ' - ' . $item['subtype'] : '')
                    ];
                });
            });
    }

    public function copyProfile()
    {
        if (!$this->isEditing) {
            return;
        }

        try {
            $newName = $this->device_profile_name . ' (Copy)';

            $copiedProfile = $this->deviceProfileRepository->copy(
                $this->deviceProfileUuid,
                $newName
            );

            session()->flash('success', 'Device profile copied successfully.');
            return redirect()->route('device-profiles.edit', $copiedProfile->device_profile_uuid);
        } catch (\Exception $e) {
            session()->flash('error', 'Error copying profile: ' . $e->getMessage());
        }
    }

    public function save()
    {
        $this->validate();

        try {
            $profileData = [
                'domain_uuid' => $this->domain_uuid,
                'device_profile_name' => $this->device_profile_name,
                'device_profile_enabled' => $this->device_profile_enabled,
                'device_profile_description' => $this->device_profile_description,
            ];

            $filteredKeys = array_filter($this->profileKeys, function ($key) {
                return !empty($key['profile_key_vendor']) && !empty($key['profile_key_id']);
            });

            $filteredSettings = array_filter($this->profileSettings, function ($setting) {
                return !empty($setting['profile_setting_name']);
            });

            if ($this->isEditing) {
                $profile = $this->deviceProfileRepository->update(
                    $this->deviceProfileUuid,
                    $profileData,
                    $filteredKeys,
                    $filteredSettings
                );
                session()->flash('success', 'Device profile updated successfully.');
                return redirect()->route('devices_profiles.edit', $profile->device_profile_uuid);
            } else {
                $profile = $this->deviceProfileRepository->create(
                    $profileData,
                    $filteredKeys,
                    $filteredSettings
                );
                session()->flash('success', 'Device profile created successfully.');
                return redirect()->route('devices_profiles.edit', $profile->device_profile_uuid);
            }
        } catch (\Exception $e) {
            throw $e;
            session()->flash('error', 'Error saving profile: ' . $e->getMessage());
        }
    }

    public function delete()
    {
        if (!$this->isEditing) {
            return;
        }

        try {
            $this->deviceProfileRepository->delete($this->deviceProfileUuid);
            session()->flash('success', 'Device profile deleted successfully.');
            return redirect()->route('devices_profiles.index');
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting profile: ' . $e->getMessage());
        }
    }

    protected function rules()
    {
        return [
            'device_profile_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('v_device_profiles', 'device_profile_name')
                    ->where('domain_uuid', $this->domain_uuid)
                    ->ignore($this->deviceProfileUuid, 'device_profile_uuid')
            ],
            'device_profile_enabled' => 'required|in:true,false',
            'device_profile_description' => 'nullable|string',
            'domain_uuid' => 'required|exists:v_domains,domain_uuid',
            'profileKeys.*.profile_key_vendor' => 'nullable|string|max:255',
            'profileKeys.*.profile_key_id' => 'nullable|string|max:255',
            'profileKeys.*.profile_key_category' => 'nullable|string|max:255',
            'profileKeys.*.profile_key_type' => 'nullable|string|max:255',
            'profileKeys.*.profile_key_subtype' => 'nullable|string|max:255',
            'profileKeys.*.profile_key_line' => 'nullable|string|max:255',
            'profileKeys.*.profile_key_value' => 'nullable|string',
            'profileKeys.*.profile_key_extension' => 'nullable|string|max:255',
            'profileKeys.*.profile_key_protected' => 'nullable|in:true,false',
            'profileKeys.*.profile_key_label' => 'nullable|string|max:255',
            'profileKeys.*.profile_key_icon' => 'nullable|string|max:255',
            'profileSettings.*.profile_setting_name' => 'nullable|string|max:255',
            'profileSettings.*.profile_setting_value' => 'nullable|string',
            'profileSettings.*.profile_setting_enabled' => 'nullable|in:true,false',
            'profileSettings.*.profile_setting_description' => 'nullable|string',
        ];
    }

    public function render()
    {
        return view('livewire.device-profile-form');
    }
}