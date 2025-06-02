<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class DeviceRepository
{
    protected $table = 'v_devices';
    protected $deviceLinesTable = 'v_device_lines';
    protected $deviceKeysTable = 'v_device_keys';
    protected $deviceSettingsTable = 'v_device_settings';
    protected $deviceVendorsTable = 'v_device_vendors';
    protected $deviceVendorFunctionsTable = 'v_device_vendor_functions';
    protected $usersTable = 'v_users';
    protected $domainsTable = 'v_domains';


    public function findByUuid(string $deviceUuid): ?array
    {
        $device = DB::table($this->table)
            ->where('device_uuid', $deviceUuid)
            ->first();

        return $device ? (array) $device : null;
    }


    public function getTotalDevicesCount(string $domainUuid): int
    {
        return DB::table($this->table)
            ->where('domain_uuid', $domainUuid)
            ->count();
    }


    public function checkDuplicateMacAddress(string $macAddress, ?string $excludeDeviceUuid = null): ?string
    {
        $query = DB::table($this->table . ' as d1')
            ->join($this->domainsTable . ' as d2', 'd1.domain_uuid', '=', 'd2.domain_uuid')
            ->where('d1.device_mac_address', $macAddress)
            ->select('d2.domain_name');

        if ($excludeDeviceUuid) {
            $query->where('d1.device_uuid', '!=', $excludeDeviceUuid);
        }

        $result = $query->first();
        return $result ? $result->domain_name : null;
    }


    public function create(array $deviceData): string
    {
        $deviceUuid = Str::uuid()->toString();
        
        DB::beginTransaction();
        
        try {
            $mainDeviceData = $this->prepareMainDeviceData($deviceData, $deviceUuid);
            
            DB::table($this->table)->insert($mainDeviceData);
            
            $this->handleDeviceLines($deviceUuid, $deviceData['domain_uuid'], $deviceData['device_lines'] ?? []);
            $this->handleDeviceKeys($deviceUuid, $deviceData['domain_uuid'], $deviceData['device_keys'] ?? []);
            $this->handleDeviceSettings($deviceUuid, $deviceData['domain_uuid'], $deviceData['device_settings'] ?? []);
            
            DB::commit();
            return $deviceUuid;
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    public function update(string $deviceUuid, array $deviceData): bool
    {
        DB::beginTransaction();
        
        try {
            $mainDeviceData = $this->prepareMainDeviceData($deviceData, $deviceUuid);
            
            DB::table($this->table)
                ->where('device_uuid', $deviceUuid)
                ->update($mainDeviceData);
            
            $this->handleDeviceLines($deviceUuid, $deviceData['domain_uuid'], $deviceData['device_lines'] ?? []);
            $this->handleDeviceKeys($deviceUuid, $deviceData['domain_uuid'], $deviceData['device_keys'] ?? []);
            $this->handleDeviceSettings($deviceUuid, $deviceData['domain_uuid'], $deviceData['device_settings'] ?? []);
            
            DB::commit();
            return true;
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    public function delete(string $deviceUuid): bool
    {
        DB::beginTransaction();
        
        try {
            $this->deleteDeviceLines($deviceUuid);
            $this->deleteDeviceKeys($deviceUuid);
            $this->deleteDeviceSettings($deviceUuid);
            
            $deleted = DB::table($this->table)
                ->where('device_uuid', $deviceUuid)
                ->delete();
            
            DB::commit();
            return $deleted > 0;
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    public function getDeviceLines(string $deviceUuid): array
    {
        $lines = DB::table($this->deviceLinesTable)
            ->where('device_uuid', $deviceUuid)
            ->orderByRaw('CAST(line_number as UNSIGNED) ASC')
            ->get()
            ->toArray();

        return array_map(function($line) {
            return (array) $line;
        }, $lines);
    }


    public function getDeviceKeys(string $deviceUuid): array
    {
        $keys = DB::table($this->deviceKeysTable)
            ->where('device_uuid', $deviceUuid)
            ->orderBy('device_key_vendor', 'asc')
            ->orderByRaw("CASE device_key_category 
                WHEN 'line' THEN 1 
                WHEN 'memory' THEN 2 
                WHEN 'programmable' THEN 3 
                WHEN 'expansion' THEN 4 
                WHEN 'expansion-1' THEN 5 
                WHEN 'expansion-2' THEN 6 
                WHEN 'expansion-3' THEN 7 
                WHEN 'expansion-4' THEN 8 
                WHEN 'expansion-5' THEN 9 
                WHEN 'expansion-6' THEN 10 
                ELSE 100 END")
            ->orderByRaw('CAST(device_key_id as UNSIGNED) ASC')
            ->get()
            ->toArray();

        return array_map(function($key) {
            return (array) $key;
        }, $keys);
    }

    public function getDeviceSettings(string $deviceUuid): array
    {
        $settings = DB::table($this->deviceSettingsTable)
            ->where('device_uuid', $deviceUuid)
            ->orderBy('device_setting_subcategory', 'asc')
            ->get()
            ->toArray();

        return array_map(function($setting) {
            return (array) $setting;
        }, $settings);
    }


    public function getDeviceVendors(): array
    {
        $vendors = DB::table($this->deviceVendorsTable)
            ->where('enabled', 'true')
            ->orderBy('name', 'asc')
            ->select('name')
            ->get()
            ->toArray();

        return array_map(function($vendor) {
            return (array) $vendor;
        }, $vendors);
    }

    public function getVendorFunctions(): array
    {
        $functions = DB::table($this->deviceVendorsTable . ' as v')
            ->join($this->deviceVendorFunctionsTable . ' as f', 'v.device_vendor_uuid', '=', 'f.device_vendor_uuid')
            ->where('v.enabled', 'true')
            ->where('f.enabled', 'true')
            ->select('v.name as vendor_name', 'f.type', 'f.subtype', 'f.value')
            ->orderBy('v.name', 'asc')
            ->orderBy('f.type', 'asc')
            ->get()
            ->toArray();

        return array_map(function($function) {
            return (array) $function;
        }, $functions);
    }

    public function getUsersForDomain(string $domainUuid): array
    {
        $users = DB::table($this->usersTable)
            ->where('domain_uuid', $domainUuid)
            ->where('user_enabled', 'true')
            ->orderBy('username', 'asc')
            ->get()
            ->toArray();

        return array_map(function($user) {
            return (array) $user;
        }, $users);
    }

    public function getAlternateDevice(string $domainUuid, string $alternateDeviceUuid): array
    {
        $device = DB::table($this->table)
            ->where(function($query) use ($domainUuid) {
                $query->where('domain_uuid', $domainUuid)
                      ->orWhereNull('domain_uuid');
            })
            ->where('device_uuid', $alternateDeviceUuid)
            ->get()
            ->toArray();

        return array_map(function($dev) {
            return (array) $dev;
        }, $device);
    }


    public function deleteSpecificLines(string $deviceUuid, array $lineUuids): bool
    {
        return DB::table($this->deviceLinesTable)
            ->where('device_uuid', $deviceUuid)
            ->whereIn('device_line_uuid', $lineUuids)
            ->delete() > 0;
    }


    public function deleteSpecificKeys(string $deviceUuid, array $keyUuids): bool
    {
        return DB::table($this->deviceKeysTable)
            ->where('device_uuid', $deviceUuid)
            ->whereIn('device_key_uuid', $keyUuids)
            ->delete() > 0;
    }


    public function deleteSpecificSettings(string $deviceUuid, array $settingUuids): bool
    {
        return DB::table($this->deviceSettingsTable)
            ->where('device_uuid', $deviceUuid)
            ->whereIn('device_setting_uuid', $settingUuids)
            ->delete() > 0;
    }

    public function getVendorByMac(string $macAddress): ?string
    {
        
        $macPrefix = strtoupper(substr(str_replace([':', '-'], '', $macAddress), 0, 6));
        

        $vendorMap = [
        ];
        
        return $vendorMap[$macPrefix] ?? null;
    }


    public function normalizeMacAddress(string $macAddress): string
    {
        $macAddress = strtolower($macAddress);
        return preg_replace('/[^a-f0-9]/', '', $macAddress);
    }

    private function prepareMainDeviceData(array $data, string $deviceUuid): array
    {
        $prepared = [
            'device_uuid' => $deviceUuid,
            'domain_uuid' => $data['domain_uuid'],
        ];


        $permissionFields = [
            'device_mac_address' => 'device_mac_address',
            'device_label' => 'device_label',
            'device_user_uuid' => 'device_user_uuid',
            'device_username' => 'device_username',
            'device_password' => 'device_password',
            'device_vendor' => 'device_vendor',
            'device_location' => 'device_location',
            'device_uuid_alternate' => 'device_uuid_alternate',
            'device_model' => 'device_model',
            'device_firmware_version' => 'device_firmware_version',
            'device_enabled' => 'device_enabled',
            'device_template' => 'device_template',
            'device_profile_uuid' => 'device_profile_uuid',
            'device_description' => 'device_description',
        ];

        foreach ($permissionFields as $field) {
            if (isset($data[$field])) {
                $prepared[$field] = $data[$field];
            }
        }

        if (isset($data['device_enabled']) && $data['device_enabled']) {
            $prepared['device_enabled_date'] = now();
        }

        return $prepared;
    }


    private function handleDeviceLines(string $deviceUuid, string $domainUuid, array $lines): void
    {
        foreach ($lines as $line) {
            if (empty($line['line_number'])) {
                continue;
            }

            $lineUuid = $line['device_line_uuid'] ?? Str::uuid()->toString();
            
            $lineData = [
                'domain_uuid' => $domainUuid,
                'device_uuid' => $deviceUuid,
                'device_line_uuid' => $lineUuid,
                'line_number' => $line['line_number'],
                'server_address' => $line['server_address'] ?? '',
                'outbound_proxy_primary' => $line['outbound_proxy_primary'] ?? '',
                'outbound_proxy_secondary' => $line['outbound_proxy_secondary'] ?? '',
                'server_address_primary' => $line['server_address_primary'] ?? '',
                'server_address_secondary' => $line['server_address_secondary'] ?? '',
                'label' => $line['label'] ?? '',
                'display_name' => $line['display_name'] ?? '',
                'user_id' => $line['user_id'] ?? '',
                'auth_id' => $line['auth_id'] ?? '',
                'password' => $line['password'] ?? '',
                'shared_line' => $line['shared_line'] ?? '',
                'enabled' => $line['enabled'] ?? 'true',
                'sip_port' => $line['sip_port'] ?? 5060,
                'sip_transport' => $line['sip_transport'] ?? 'udp',
                'register_expires' => $line['register_expires'] ?? 120,
            ];

            DB::table($this->deviceLinesTable)->updateOrInsert(
                ['device_line_uuid' => $lineUuid],
                $lineData
            );
        }
    }

    private function handleDeviceKeys(string $deviceUuid, string $domainUuid, array $keys): void
    {
        foreach ($keys as $key) {
            if (empty($key['device_key_category'])) {
                continue;
            }

            $keyUuid = $key['device_key_uuid'] ?? Str::uuid()->toString();
            
            $keyData = [
                'domain_uuid' => $domainUuid,
                'device_uuid' => $deviceUuid,
                'device_key_uuid' => $keyUuid,
                'device_key_category' => $key['device_key_category'],
                'device_key_vendor' => $key['device_key_vendor'] ?? '',
                'device_key_id' => $key['device_key_id'] ?? '',
                'device_key_type' => $key['device_key_type'] ?? '',
                'device_key_subtype' => $key['device_key_subtype'] ?? '',
                'device_key_line' => $key['device_key_line'] ?? '',
                'device_key_value' => $key['device_key_value'] ?? '',
                'device_key_extension' => $key['device_key_extension'] ?? '',
                'device_key_label' => $key['device_key_label'] ?? '',
                'device_key_icon' => $key['device_key_icon'] ?? '',
            ];

            DB::table($this->deviceKeysTable)->updateOrInsert(
                ['device_key_uuid' => $keyUuid],
                $keyData
            );
        }
    }

    private function handleDeviceSettings(string $deviceUuid, string $domainUuid, array $settings): void
    {
        foreach ($settings as $setting) {
            if (empty($setting['device_setting_subcategory'])) {
                continue;
            }

            $settingUuid = $setting['device_setting_uuid'] ?? Str::uuid()->toString();
            
            $settingData = [
                'domain_uuid' => $domainUuid,
                'device_uuid' => $deviceUuid,
                'device_setting_uuid' => $settingUuid,
                'device_setting_category' => $setting['device_setting_category'] ?? '',
                'device_setting_subcategory' => $setting['device_setting_subcategory'],
                'device_setting_name' => $setting['device_setting_name'] ?? '',
                'device_setting_value' => $setting['device_setting_value'] ?? '',
                'device_setting_enabled' => $setting['device_setting_enabled'] ?? 'true',
                'device_setting_description' => $setting['device_setting_description'] ?? '',
            ];

            DB::table($this->deviceSettingsTable)->updateOrInsert(
                ['device_setting_uuid' => $settingUuid],
                $settingData
            );
        }
    }


    private function deleteDeviceLines(string $deviceUuid): void
    {
        DB::table($this->deviceLinesTable)
            ->where('device_uuid', $deviceUuid)
            ->delete();
    }


    private function deleteDeviceKeys(string $deviceUuid): void
    {
        DB::table($this->deviceKeysTable)
            ->where('device_uuid', $deviceUuid)
            ->delete();
    }


    private function deleteDeviceSettings(string $deviceUuid): void
    {
        DB::table($this->deviceSettingsTable)
            ->where('device_uuid', $deviceUuid)
            ->delete();
    }
}