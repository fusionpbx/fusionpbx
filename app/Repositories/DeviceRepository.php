<?php

namespace App\Repositories;

use App\Facades\Setting;
use App\Models\Device;
use App\Models\DeviceLine;
use App\Models\DeviceKey;
use App\Models\DeviceSetting;
use App\Models\DeviceProfile;
use App\Models\DeviceVendor;
use App\Models\DeviceVendorFunction;
use App\Models\Domain;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class DeviceRepository
{
    protected $device;
    protected $deviceLine;
    protected $deviceKey;
    protected $deviceSetting;
    protected $deviceProfile;

    public function __construct(
        Device $device,
        DeviceLine $deviceLine,
        DeviceKey $deviceKey,
        DeviceSetting $deviceSetting,
        DeviceProfile $deviceProfile
    ) {
        $this->device = $device;
        $this->deviceLine = $deviceLine;
        $this->deviceKey = $deviceKey;
        $this->deviceSetting = $deviceSetting;
        $this->deviceProfile = $deviceProfile;
    }

    public function all()
    {
        return $this->device->all();
    }

    public function mine()
    {
        $user = auth()->user();
        return $this->device->where('domain_uuid', $user->domain_uuid)->get();
    }

    public function findByUuid(string $deviceUuid, bool $withRelations = false): ?Device
    {
        $query = $this->device->where('device_uuid', $deviceUuid);

        if ($withRelations) {
            $query->with(['lines', 'keys', 'settings', 'domain', 'user']);
        }

        return $query->first();
    }

    public function getTotalDevicesCount(string $domainUuid): int
    {
        return $this->device->where('domain_uuid', $domainUuid)->count();
    }

    public function checkDuplicateMacAddress(string $macAddress, ?string $excludeDeviceUuid = null): ?string
    {
        $query = $this->device
            ->join(Domain::getTableName() . ' as d', 'v_devices.domain_uuid', '=', 'd.domain_uuid')
            ->where('device_mac_address', $macAddress)
            ->select('d.domain_name');

        if ($excludeDeviceUuid) {
            $query->where('device_uuid', '!=', $excludeDeviceUuid);
        }

        $result = $query->first();
        return $result ? $result->domain_name : null;
    }

    public function create(array $deviceData, array $deviceLines = [], array $deviceKeys = [], array $deviceSettings = []): Device
    {
        $deviceData['device_uuid'] = $deviceData['device_uuid'] ?? Str::uuid();

        try {
            DB::beginTransaction();

            $filteredData = $this->applyDevicePermissions($deviceData);
            $device = $this->device->create($filteredData);

            if (!empty($deviceLines)) {
                $this->syncDeviceLines($device->device_uuid, $device->domain_uuid, $deviceLines);
            }

            if (!empty($deviceKeys)) {
                $this->syncDeviceKeys($device->device_uuid, $device->domain_uuid, $deviceKeys);
            }

            if (!empty($deviceSettings)) {
                $this->syncDeviceSettings($device->device_uuid, $device->domain_uuid, $deviceSettings);
            }

            DB::commit();
            return $device;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(string $deviceUuid, array $deviceData, array $deviceLines = [], array $deviceKeys = [], array $deviceSettings = []): Device
    {
        try {
            DB::beginTransaction();

            $device = $this->findByUuid($deviceUuid);
            if (!$device) {
                throw new Exception("Device not found");
            }


            $filteredData = $this->applyDevicePermissions($deviceData, $device);
            $device->update($filteredData);

            if (!empty($deviceLines)) {
                $this->syncDeviceLines($device->device_uuid, $device->domain_uuid, $deviceLines);
            }

            if (!empty($deviceKeys)) {
                $this->syncDeviceKeys($device->device_uuid, $device->domain_uuid, $deviceKeys);
            }

            if (!empty($deviceSettings)) {
                $this->syncDeviceSettings($device->device_uuid, $device->domain_uuid, $deviceSettings);
            }

            DB::commit();
            return $device->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(string $deviceUuid): void
    {
        try {
            DB::beginTransaction();

            $device = $this->findByUuid($deviceUuid);
            if (!$device) {
                throw new Exception("Device not found");
            }

            $device->lines()->delete();
            $device->keys()->delete();
            $device->settings()->delete();

            $device->delete();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function copy(string $uuid, string $newMacAddress, string $newLabel): Device
    {
        try {
            DB::beginTransaction();

            $originalDevice = $this->findByUuid($uuid, true);
            if (!$originalDevice) {
                throw new Exception("Device not found");
            }

            $newDevice = $originalDevice->replicate();
            $newDevice->device_uuid = Str::uuid();
            $newDevice->device_mac_address = $newMacAddress;
            $newDevice->device_label = $newLabel;
            $newDevice->device_description = $originalDevice->device_description . ' (copy)';
            $newDevice->save();

            foreach ($originalDevice->lines as $line) {
                $newLine = $line->replicate();
                $newLine->device_line_uuid = Str::uuid();
                $newLine->device_uuid = $newDevice->device_uuid;
                $newLine->save();
            }

            foreach ($originalDevice->keys as $key) {
                $newKey = $key->replicate();
                $newKey->device_key_uuid = Str::uuid();
                $newKey->device_uuid = $newDevice->device_uuid;
                $newKey->save();
            }

            foreach ($originalDevice->settings as $setting) {
                $newSetting = $setting->replicate();
                $newSetting->device_setting_uuid = Str::uuid();
                $newSetting->device_uuid = $newDevice->device_uuid;
                $newSetting->save();
            }

            DB::commit();
            return $newDevice;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function applyDevicePermissions(array $deviceData, ?Device $existingDevice = null): array
    {
        $filteredData = [];
        $user = auth()->user();

        $filteredData['domain_uuid'] = $deviceData['domain_uuid'] ?? ($existingDevice->domain_uuid ?? null);

        if (is_null($existingDevice)) {
            $filteredData['device_uuid'] = $deviceData['device_uuid'] ?? Str::uuid();
        }

        if ($user->hasPermission('device_mac_address')) {
            $filteredData['device_mac_address'] = $deviceData['device_mac_address'] ?? ($existingDevice->device_mac_address ?? null);
        }

        if ($user->hasPermission('device_label')) {
            $filteredData['device_label'] = $deviceData['device_label'] ?? ($existingDevice->device_label ?? null);
        }

        if ($user->hasPermission('device_user')) {
            $filteredData['device_user_uuid'] = $deviceData['device_user_uuid'] ?? ($existingDevice->device_user_uuid ?? null);
            $filteredData['device_username'] = $deviceData['device_username'] ?? ($existingDevice->device_username ?? null);
        }

        if ($user->hasPermission('device_password')) {
            if (empty($deviceData['device_password']) && is_null($existingDevice)) {
                $passwordLength = Setting::getSetting('device', 'password_length', 'numeric');
                $filteredData['device_password'] = generatePassword($passwordLength, 1);
            } elseif (!empty($deviceData['device_password'])) {
                $filteredData['device_password'] = $deviceData['device_password'];
            }
        }

        if ($user->hasPermission('device_template')) {
            $filteredData['device_template'] = $deviceData['device_template'] ?? ($existingDevice->device_template ?? null);
        }

        if($user->hasPermission('device_profile_edit')) {
            $filteredData['device_profile_uuid'] = $deviceData['device_profile_uuid'] ?? ($existingDevice->device_profile_uuid ?? null);
        }

        if ($user->hasPermission('device_vendor')) {
            $filteredData['device_vendor'] = $deviceData['device_vendor'] ?? ($existingDevice->device_vendor ?? null);
        }

        if ($user->hasPermission('device_location')) {
            $filteredData['device_location'] = $deviceData['device_location'] ?? ($existingDevice->device_location ?? null);
        }

        if ($user->hasPermission('device_alternate')) {
            $filteredData['device_uuid_alternate'] = $deviceData['device_uuid_alternate'] ?? ($existingDevice->device_uuid_alternate ?? null);
        }

        if ($user->hasPermission('device_advanced')) {
            $filteredData['device_model'] = $deviceData['device_model'] ?? ($existingDevice->device_model ?? null);
            $filteredData['device_firmware_version'] = $deviceData['device_firmware_version'] ?? ($existingDevice->device_firmware_version ?? null);
            $filteredData['device_template'] = $deviceData['device_template'] ?? ($existingDevice->device_template ?? null);
            $filteredData['device_profile_uuid'] = $deviceData['device_profile_uuid'] ?? ($existingDevice->device_profile_uuid ?? null);
        }

        if ($user->hasPermission('device_enable')) {
            $filteredData['device_enabled'] = $deviceData['device_enabled'] ?? ($existingDevice->device_enabled ?? 'true');
            if (isset($deviceData['device_enabled']) && $deviceData['device_enabled']) {
                $filteredData['device_enabled_date'] = now();
            }
        }

        $filteredData['device_description'] = $deviceData['device_description'] ?? ($existingDevice->device_description ?? null);

        return $filteredData;
    }

    protected function syncDeviceLines(string $deviceUuid, string $domainUuid, array $lines): void
    {
        foreach ($lines as $lineData) {
            if (empty($lineData['line_number'])) {
                continue;
            }

            if (empty($lineData['device_line_uuid'])) {
                $this->createDeviceLine($deviceUuid, $domainUuid, $lineData);
            } else {
                $this->updateDeviceLine($lineData['device_line_uuid'], $lineData);
            }
        }
    }

    protected function syncDeviceKeys(string $deviceUuid, string $domainUuid, array $keys): void
    {
        foreach ($keys as $keyData) {
            if (empty($keyData['device_key_category'])) {
                continue;
            }

            if (empty($keyData['device_key_uuid'])) {
                $this->createDeviceKey($deviceUuid, $domainUuid, $keyData);
            } else {
                $this->updateDeviceKey($keyData['device_key_uuid'], $keyData);
            }
        }
    }

    protected function syncDeviceSettings(string $deviceUuid, string $domainUuid, array $settings): void
    {
        foreach ($settings as $settingData) {

            if (empty($settingData['device_setting_uuid'])) {
                $this->createDeviceSetting($deviceUuid, $domainUuid, $settingData);
            } else {
                $this->updateDeviceSetting($settingData['device_setting_uuid'], $settingData);
            }
        }
    }

    protected function createDeviceLine(string $deviceUuid, string $domainUuid, array $lineData): DeviceLine
    {
        $defaultFields = [
            'device_uuid' => $deviceUuid,
            'domain_uuid' => $domainUuid,
        ];

        $optionalFields = [
            'device_line_uuid',
            'line_number',
            'server_address',
            'outbound_proxy_primary',
            'outbound_proxy_secondary',
            'server_address_primary',
            'server_address_secondary',
            'label',
            'display_name',
            'user_id',
            'auth_id',
            'password',
            'shared_line',
            'enabled',
            'sip_port',
            'sip_transport',
            'register_expires',
        ];

        $filtered = collect($optionalFields)
            ->filter(fn($field) => array_key_exists($field, $lineData))
            ->mapWithKeys(fn($field) => [$field => $lineData[$field]])
            ->toArray();

        $defaults = [
            'outbound_proxy_primary' => Setting::getSetting('provision', 'outbound_proxy_primary', 'text'),
            'outbound_proxy_secondary' => Setting::getSetting('provision', 'outbound_proxy_secondary', 'text'),
            'server_address_primary' => Setting::getSetting('provision', 'server_address_primary', 'text'),
            'server_address_secondary' => Setting::getSetting('provision', 'server_address_secondary', 'text'),
            'sip_port' => Setting::getSetting('provision', 'line_sip_port', 'numeric'),
            'sip_transport' => Setting::getSetting('provision', 'line_sip_transport', 'text'),
            'register_expires' => Setting::getSetting('provision', 'line_register_expires', 'numeric'),
        ];

        foreach ($defaults as $key => $value) {
            if (!isset($filtered[$key])) {
                $filtered[$key] = $value;
            }
        }

        $data = array_merge($defaultFields, $filtered);


        return $this->deviceLine->create($data);
    }

    protected function updateDeviceLine(string $deviceLineUuid, array $lineData): DeviceLine
    {
        $deviceLine = $this->deviceLine->where('device_line_uuid', $deviceLineUuid)->firstOrFail();
        $deviceLine->update($lineData);

        return $deviceLine;
    }

    protected function createDeviceKey(string $deviceUuid, string $domainUuid, array $keyData): DeviceKey
    {
        $data = array_merge($keyData, [
            'device_key_uuid' => Str::uuid(),
            'device_uuid' => $deviceUuid,
            'domain_uuid' => $domainUuid,
        ]);

        return $this->deviceKey->create($data);
    }

    protected function updateDeviceKey(string $deviceKeyUuid, array $keyData): DeviceKey
    {
        $deviceKey = $this->deviceKey->where('device_key_uuid', $deviceKeyUuid)->firstOrFail();
        $deviceKey->update($keyData);
        return $deviceKey;
    }

    protected function createDeviceSetting(string $deviceUuid, string $domainUuid, array $settingData): DeviceSetting
    {
        $data = array_merge($settingData, [
            'device_setting_uuid' => Str::uuid(),
            'device_uuid' => $deviceUuid,
            'domain_uuid' => $domainUuid,
        ]);

        return $this->deviceSetting->create($data);
    }

    protected function updateDeviceSetting(string $deviceSettingUuid, array $settingData): DeviceSetting
    {
        $deviceSetting = $this->deviceSetting->where('device_setting_uuid', $deviceSettingUuid)->firstOrFail();
        $deviceSetting->update($settingData);
        return $deviceSetting;
    }

    public function deleteSpecificLines(string $deviceUuid, array $lineUuids): bool
    {
        return $this->deviceLine
            ->where('device_uuid', $deviceUuid)
            ->whereIn('device_line_uuid', $lineUuids)
            ->delete() > 0;
    }

    public function deleteSpecificKeys(string $deviceUuid, array $keyUuids): bool
    {
        return $this->deviceKey
            ->where('device_uuid', $deviceUuid)
            ->whereIn('device_key_uuid', $keyUuids)
            ->delete() > 0;
    }

    public function deleteSpecificSettings(string $deviceUuid, array $settingUuids): bool
    {
        return $this->deviceSetting
            ->where('device_uuid', $deviceUuid)
            ->whereIn('device_setting_uuid', $settingUuids)
            ->delete() > 0;
    }

    public function getDeviceLines(string $deviceUuid): array
    {
        $device = $this->findByUuid($deviceUuid, true);
        return $device ? $device->lines->toArray() : [];
    }

    public function getDeviceKeys(string $deviceUuid): array
    {
        $device = $this->findByUuid($deviceUuid, true);
        return $device ? $device->keys->toArray() : [];
    }

    public function getDeviceSettings(string $deviceUuid): array
    {
        $device = $this->findByUuid($deviceUuid, true);
        return $device ? $device->settings->toArray() : [];
    }

    public function getDeviceVendors(): array
    {
        $vendors = DeviceVendor::where('enabled', 'true')
            ->orderBy('name', 'asc')
            ->select('name')
            ->get()
            ->toArray();

        return array_map(function ($vendor) {
            return (array) $vendor;
        }, $vendors);
    }

    public function getVendorFunctions(): array
    {
        $functions = DB::table(DeviceVendor::getTableName() . ' as v')
            ->join(DeviceVendorFunction::getTableName() . ' as f', 'v.device_vendor_uuid', '=', 'f.device_vendor_uuid')
            ->where('v.enabled', 'true')
            ->where('f.enabled', 'true')
            ->select('v.name as vendor_name', 'f.type', 'f.subtype', 'f.value')
            ->orderBy('v.name', 'asc')
            ->orderBy('f.type', 'asc')
            ->get()
            ->toArray();
        $result = [];
        foreach ($functions as $function)
        {
            $result[$function->vendor_name][] = json_decode(json_encode($function, true), true);
        }
        return $result;

//        return array_map(function ($function) {
            //return (array) $function;
//        }, $functions);
    }

    public function getUsersForDomain(string $domainUuid): array
    {
        $users = DB::table(User::getTableName())
            ->where('domain_uuid', $domainUuid)
            ->where('user_enabled', 'true')
            ->orderBy('username', 'asc')
            ->get()
            ->toArray();

        return array_map(function ($user) {
            return (array) $user;
        }, $users);
    }

    public function getDomain(): array
    {
        $domain = Domain::select('domain_uuid', 'domain_name')->get()->toArray();

        return $domain;
    }

    public function getAlternateDevice(string $domainUuid, string $alternateDeviceUuid): array
    {
        $devices = $this->device
            ->where(function ($query) use ($domainUuid) {
                $query->where('domain_uuid', $domainUuid)
                    ->orWhereNull('domain_uuid');
            })
            ->where('device_uuid', $alternateDeviceUuid)
            ->get()
            ->toArray();

        return $devices;
    }

    public function getVendorByMac(string $macAddress): ?string
    {
        $macPrefix = strtoupper(substr(str_replace([':', '-'], '', $macAddress), 0, 6));

        $vendorMap = [];

        return $vendorMap[$macPrefix] ?? null;
    }

    public function normalizeMacAddress(string $macAddress): string
    {
        $macAddress = strtolower($macAddress);
        return preg_replace('#[^a-fA-F0-9./]#', '', $macAddress);
    }

    public function getDeviceProfiles($domainUuid = null)
    {
        $query = DeviceProfile::query();

        if ($domainUuid) {
            $query->where('domain_uuid', $domainUuid);
        }

        return $query->select('device_profile_uuid', 'device_profile_name', 'device_profile_description')
            ->where('device_profile_enabled', 'true')
            ->orderBy('device_profile_name')
            ->get();
    }

    public function hasDeviceProfiles($domainUuid = null)
    {
        $query = DeviceProfile::query();

        if ($domainUuid) {
            $query->where('domain_uuid', $domainUuid);
        }

        return $query->where('device_profile_enabled', 'true')->exists();
    }
}
