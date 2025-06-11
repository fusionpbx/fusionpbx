<?php

namespace App\Livewire;

use Livewire\Component;
use App\Repositories\DeviceRepository;
use App\Models\Device;
use App\Facades\Setting;
use App\Http\Requests\DeviceRequest;
use App\Models\DeviceLine;
use App\Services\DeviceService;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DeviceForm extends Component
{
    public  $deviceUuid;
    public $device;
    public $isEditing = false;

    public $domain_uuid;
    public $device_mac_address;
    public $device_label;
    public $device_user_uuid;
    public $device_username;
    public $device_password;
    public $device_vendor;
    public $device_location;
    public $device_uuid_alternate;
    public $device_model;
    public $device_firmware_version;
    public $device_template;
    public $device_profile_uuid;
    public $device_enabled = 'true';
    public $device_description;

    public $deviceLines = [];
    public $deviceKeys = [];
    public $showKeySubtype = false;
    public $deviceSettings = [];

    public $vendors = [];
    public $users = [];
    public $availableDomains = [];
    public $deviceTemplates = [];
    public $alternateDevices = [];
    public $vendorFunctions = [];

    public $deviceLinesServerPrimary = '';
    public $deviceLinesServerSecondary = '';
    public $outboundProxyPrimary = '';
    public $outboundProxySecondary = '';

    public $showAdvanced = false;
    public $duplicateMacDomain = null;

    protected $deviceRepository;
    protected $deviceService;
    public $deviceProfiles = [];
    public $hasProfiles = false;


    public function boot(DeviceRepository $deviceRepository, DeviceService $deviceService)
    {
        $this->deviceRepository = $deviceRepository;
        $this->deviceService = $deviceService;
    }

    public function rules()
    {
        $request = new DeviceRequest();
        $request->setVendorUuid($this->deviceUuid);
        return $request->rules();
    }

    public function mount($deviceUuid = null)
    {
        $this->deviceUuid = $deviceUuid;
        $this->isEditing = !is_null($deviceUuid);
        if ($this->device_vendor == 'fanvil') {
            $this->showKeySubtype = true;
        }

        $this->loadDropdownData();

        if ($this->isEditing) {
            $this->loadDevice();
        } else {
            $this->initializeDefaults();
        }
    }

    protected function loadDevice()
    {
        $this->device = $this->deviceRepository->findByUuid($this->deviceUuid, true);

        if (!$this->device) {
            session()->flash('error', 'Device not found.');
            return redirect()->route('devices.index');
        }

        $this->domain_uuid = $this->device->domain_uuid;
        $this->device_mac_address = $this->device->device_mac_address;
        $this->device_label = $this->device->device_label;
        $this->device_user_uuid = $this->device->device_user_uuid;
        $this->device_username = $this->device->device_username;
        $this->device_password = $this->device->device_password;
        $this->device_vendor = $this->device->device_vendor;
        $this->device_location = $this->device->device_location;
        $this->device_uuid_alternate = $this->device->device_uuid_alternate;
        $this->device_model = $this->device->device_model;
        $this->device_firmware_version = $this->device->device_firmware_version;
        $this->device_template = $this->device->device_template;
        $this->device_profile_uuid = $this->device->device_profile_uuid;
        $this->device_enabled = $this->device->device_enabled ?? 'true';
        $this->device_description = $this->device->device_description;

        $this->deviceLines = $this->device->lines->toArray();
        $this->deviceKeys = $this->device->keys->toArray();
        $this->deviceSettings = $this->device->settings->toArray();
    }

    protected function initializeDefaults()
    {
        $user = auth()->user();
        $this->domain_uuid = $user->domain_uuid;
        $this->device_enabled = 'true';

        if ($user->hasPermission('device_password')) {
            $passwordLength = Setting::getSetting('device', 'password_length', 'numeric');
            $this->device_password = generatePassword($passwordLength, 1);
        }
    }

    protected function loadDropdownData()
    {
        $user = auth()->user();

        $this->vendors = $this->deviceRepository->getDeviceVendors();
        $this->users = $this->deviceRepository->getUsersForDomain($user->domain_uuid);
        $this->vendorFunctions = $this->deviceRepository->getVendorFunctions();

        $this->deviceTemplates = $this->deviceService->getDeviceTemplates();

        $this->availableDomains = $this->deviceRepository->getDomain();

        $this->loadDeviceProfiles($user->domain_uuid);

        if (!$this->isEditing) {
            $this->domain_uuid = Session::get('domain_uuid');
        }
    }

    protected function loadDeviceProfiles($domainUuid)
    {
        $this->hasProfiles = $this->deviceRepository->hasDeviceProfiles($domainUuid);

        if ($this->hasProfiles) {
            $this->deviceProfiles = $this->deviceRepository->getDeviceProfiles($domainUuid);
        }
    }

    public function LoadDeviceLines()
    {
        if (null !== Setting::getSetting('provision', 'server_address_primary') && null !== Setting::getSetting('provision', 'server_address_primary', 'text')) {
            $this->deviceLinesServerPrimary = DeviceLine::select('server_address_primary')->get();
        }
        if (null !== Setting::getSetting('provision', 'server_address_secondary') && null !== Setting::getSetting('provision', 'server_address_secondary', 'text')) {
            $this->deviceLinesServerSecondary = DeviceLine::select('server_address_secondary')->get();
        }

        if (null !== Setting::getSetting('provision', 'outbound_proxy_primary') && null !== Setting::getSetting('provision', 'outbound_proxy_primary', 'text')) {
            $this->outboundProxyPrimary = DeviceLine::select('outbound_proxy_primary')->get();
        }
        if (null !== Setting::getSetting('provision', 'outbound_proxy_secondary') && null !== Setting::getSetting('provision', 'outbound_proxy_secondary', 'text')) {
            $this->outboundProxySecondary = DeviceLine::select('outbound_proxy_secondary')->get();
        }
    }

    public function updatedDeviceMacAddress()
    {
        if ($this->device_mac_address) {
            $normalizedMac = $this->deviceRepository->normalizeMacAddress($this->device_mac_address);

            $this->device_mac_address = format_mac($normalizedMac, ':', 'upper');

            $this->duplicateMacDomain = $this->deviceRepository->checkDuplicateMacAddress(
                $this->device_mac_address,
                $this->deviceUuid
            );

            if (!$this->device_vendor) {
                $detectedVendor = $this->deviceRepository->getVendorByMac($this->device_mac_address);
                if ($detectedVendor) {
                    $this->device_vendor = $detectedVendor;
                }
            }
        }
    }

    public function updatedDeviceUuidAlternate()
    {
        if ($this->device_uuid_alternate) {
            $this->alternateDevices = $this->deviceRepository->getAlternateDevice(
                $this->domain_uuid,
                $this->device_uuid_alternate
            );
        }
    }

    public function updatedDeviceTemplate()
    {

        if (empty($this->device_vendor) && !empty($this->device_template)) {
            $templateParts = explode('/', $this->device_template);
            if (count($templateParts) >= 1) {
                $this->device_vendor = $templateParts[0];
            }
        }
    }


    public function addDeviceLine()
    {
        $this->loadDeviceLines();
        $this->deviceLines[] = [
            'device_line_uuid' => '',
            'line_number' => count($this->deviceLines) + 1,
            'server_address' => '',
            'server_address_primary' => '',
            'server_address_secondary' => '',
            'outbound_proxy_primary' => '',
            'outbound_proxy_secondary' => '',
            'label' => '',
            'display_name' => '',
            'user_id' => '',
            'auth_id' => '',
            'password' => '',
            'sip_port' => '',
            'sip_transport' => '',
            'register_expires' => '120',
            'shared_line' => '',
            'enabled' => '',
        ];
    }

    public function removeDeviceLine($index)
    {
        if (isset($this->deviceLines[$index])) {
            $line = $this->deviceLines[$index];

            if (!empty($line['device_line_uuid'])) {
                $this->deviceRepository->deleteSpecificLines(
                    $this->deviceUuid,
                    [$line['device_line_uuid']]
                );
            }

            unset($this->deviceLines[$index]);
            $this->deviceLines = array_values($this->deviceLines);
        }
    }

    public function addDeviceKey()
    {
        $this->deviceKeys[] = [
            'device_key_uuid' => '',
            'device_key_id' => '',
            'device_key_category' => '',
            'device_key_vendor' => $this->device_vendor,
            'device_key_type' => '',
            'device_key_subtype' => '',
            'device_key_line' => '',
            'device_key_value' => '',
            'device_key_extension' => '',
            'device_key_protected' => 'false',
            'device_key_label' => '',
            'device_key_icon' => '',
        ];
    }

    public function removeDeviceKey($index)
    {
        if (isset($this->deviceKeys[$index])) {
            $key = $this->deviceKeys[$index];

            if (!empty($key['device_key_uuid'])) {
                $this->deviceRepository->deleteSpecificKeys(
                    $this->deviceUuid,
                    [$key['device_key_uuid']]
                );
            }

            unset($this->deviceKeys[$index]);
            $this->deviceKeys = array_values($this->deviceKeys);
        }
    }

    public function addDeviceSetting()
    {
        $this->deviceSettings[] = [
            'device_setting_uuid' => '',
            'device_setting_category' => '',
            'device_setting_subcategory' => '',
            'device_setting_name' => '',
            'device_setting_value' => '',
            'device_setting_enabled' => 'true',
            'device_setting_description' => '',
        ];
    }

    public function removeDeviceSetting($index)
    {
        if (isset($this->deviceSettings[$index])) {
            $setting = $this->deviceSettings[$index];

            if (!empty($setting['device_setting_uuid'])) {
                $this->deviceRepository->deleteSpecificSettings(
                    $this->deviceUuid,
                    [$setting['device_setting_uuid']]
                );
            }

            unset($this->deviceSettings[$index]);
            $this->deviceSettings = array_values($this->deviceSettings);
        }
    }

    public function copyDevice()
    {
        if (!$this->isEditing) {
            return;
        }

        try {
            $newMacAddress = $this->generateNewMacAddress();
            $newLabel = $this->device_label . ' (Copy)';

            $copiedDevice = $this->deviceRepository->copy(
                $this->deviceUuid,
                $newMacAddress,
                $newLabel
            );

            session()->flash('success', 'Device copied successfully.');
            return redirect()->route('devices.edit', $copiedDevice->device_uuid);
        } catch (\Exception $e) {
            throw $e;
            session()->flash('error', 'Error copying device: ' . $e->getMessage());
        }
    }

    public function save()
    {
        $this->validate();

        try {
            $deviceData = [
                'domain_uuid' => $this->domain_uuid,
                'device_mac_address' => $this->device_mac_address,
                'device_label' => $this->device_label,
                'device_user_uuid' => $this->device_user_uuid,
                'device_username' => $this->device_username,
                'device_password' => $this->device_password,
                'device_vendor' => $this->device_vendor,
                'device_location' => $this->device_location,
                'device_uuid_alternate' => $this->device_uuid_alternate,
                'device_model' => $this->device_model,
                'device_firmware_version' => $this->device_firmware_version,
                'device_template' => $this->device_template,
                'device_profile_uuid' => $this->device_profile_uuid,
                'device_enabled' => $this->device_enabled,
                'device_description' => $this->device_description,
            ];

            if ($this->isEditing) {
                $device = $this->deviceRepository->update(
                    $this->deviceUuid,
                    $deviceData,
                    $this->deviceLines,
                    $this->deviceKeys,
                    $this->deviceSettings
                );
                session()->flash('success', 'Device updated successfully.');
                return redirect()->route('devices.edit', $device->device_uuid);
            } else {
                $device = $this->deviceRepository->create(
                    $deviceData,
                    $this->deviceLines,
                    $this->deviceKeys,
                    $this->deviceSettings
                );
                session()->flash('success', 'Device created successfully.');
                return redirect()->route('devices.edit', $device->device_uuid);
            }
        } catch (\Exception $e) {
            throw $e;
            session()->flash('error', 'Error saving device: ' . $e->getMessage());
        }
    }

    public function delete()
    {
        if (!$this->isEditing) {
            return;
        }

        try {
            $this->deviceRepository->delete($this->deviceUuid);
            session()->flash('success', 'Device deleted successfully.');
            return redirect()->route('devices.index');
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting device: ' . $e->getMessage());
        }
    }


    protected function generateNewMacAddress()
    {
        $originalMac = $this->device_mac_address;
        $baseMac = substr(str_replace(':', '', $originalMac), 0, 10);

        $lastByte = hexdec(substr(str_replace(':', '', $originalMac), 10, 2));
        $newLastByte = str_pad(dechex(($lastByte + 1) % 256), 2, '0', STR_PAD_LEFT);

        return format_mac($baseMac . $newLastByte, ':', 'upper');
    }

    public function render()
    {
        return view('livewire.device-form');
    }
}
