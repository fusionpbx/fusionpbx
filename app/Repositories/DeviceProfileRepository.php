<?php

namespace App\Repositories;

use App\Models\DeviceProfile;
use App\Models\DeviceProfileKey;
use App\Models\DeviceProfileSetting;
use App\Facades\Setting;
use App\Models\Domain;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class DeviceProfileRepository
{
    protected $deviceProfile;
    protected $deviceProfileKey;
    protected $deviceProfileSetting;

    protected $table = 'v_device_profiles';
    protected $deviceProfileKeysTable = 'v_device_profile_keys';
    protected $deviceProfileSettingsTable = 'v_device_profile_settings';
    protected $deviceVendorsTable = 'v_device_vendors';
    protected $deviceVendorFunctionsTable = 'v_device_vendor_functions';
    protected $domainsTable = 'v_domains';

    public function __construct(
        DeviceProfile $deviceProfile,
        DeviceProfileKey $deviceProfileKey,
        DeviceProfileSetting $deviceProfileSetting
    ) {
        $this->deviceProfile = $deviceProfile;
        $this->deviceProfileKey = $deviceProfileKey;
        $this->deviceProfileSetting = $deviceProfileSetting;
    }

    public function all()
    {
        return $this->deviceProfile->all();
    }

    public function mine()
    {
        $user = auth()->user();
        return $this->deviceProfile->where('domain_uuid', $user->domain_uuid)->get();
    }

    public function findByUuid(string $deviceProfileUuid, bool $withRelations = false): ?DeviceProfile
    {
        $query = $this->deviceProfile->where('device_profile_uuid', $deviceProfileUuid);

        if ($withRelations) {
            $query->with(['keys', 'settings', 'domain']);
        }

        return $query->first();
    }

    public function getTotalProfilesCount(string $domainUuid): int
    {
        return $this->deviceProfile->where('domain_uuid', $domainUuid)->count();
    }

    public function create(array $profileData, array $profileKeys = [], array $profileSettings = []): DeviceProfile
    {
        $profileData['device_profile_uuid'] = $profileData['device_profile_uuid'] ?? Str::uuid();

        try {
            DB::beginTransaction();

            $filteredData = $this->applyProfilePermissions($profileData);
            $profile = $this->deviceProfile->create($filteredData);

            if (!empty($profileKeys)) {
                $this->syncProfileKeys($profile->device_profile_uuid, $profile->domain_uuid, $profileKeys);
            }

            if (!empty($profileSettings)) {
                $this->syncProfileSettings($profile->device_profile_uuid, $profile->domain_uuid, $profileSettings);
            }

            DB::commit();
            return $profile;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(string $deviceProfileUuid, array $profileData, array $profileKeys = [], array $profileSettings = []): DeviceProfile
    {
        try {
            DB::beginTransaction();

            $profile = $this->findByUuid($deviceProfileUuid);
            if (!$profile) {
                throw new Exception("Device profile not found");
            }

            $filteredData = $this->applyProfilePermissions($profileData, $profile);
            $profile->update($filteredData);
            
            if (!empty($profileKeys)) {
                $this->syncProfileKeys($profile->device_profile_uuid, $profile->domain_uuid, $profileKeys);
            }

            if (!empty($profileSettings)) {
                $this->syncProfileSettings($profile->device_profile_uuid, $profile->domain_uuid, $profileSettings);
            }

            DB::commit();
            return $profile->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(string $deviceProfileUuid): void
    {
        try {
            DB::beginTransaction();

            $profile = $this->findByUuid($deviceProfileUuid);
            if (!$profile) {
                throw new Exception("Device profile not found");
            }

            $profile->keys()->delete();
            $profile->settings()->delete();
            $profile->delete();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function copy(string $uuid, string $newName): DeviceProfile
    {
        try {
            DB::beginTransaction();

            $originalProfile = $this->findByUuid($uuid, true);
            if (!$originalProfile) {
                throw new Exception("Device profile not found");
            }

            $newProfile = $originalProfile->replicate();
            $newProfile->device_profile_uuid = Str::uuid();
            $newProfile->device_profile_name = $newName;
            $newProfile->device_profile_description = $originalProfile->device_profile_description . ' (copy)';
            $newProfile->save();

            foreach ($originalProfile->keys as $key) {
                $newKey = $key->replicate();
                $newKey->device_profile_key_uuid = Str::uuid();
                $newKey->device_profile_uuid = $newProfile->device_profile_uuid;
                $newKey->save();
            }

            foreach ($originalProfile->settings as $setting) {
                $newSetting = $setting->replicate();
                $newSetting->device_profile_setting_uuid = Str::uuid();
                $newSetting->device_profile_uuid = $newProfile->device_profile_uuid;
                $newSetting->save();
            }

            DB::commit();
            return $newProfile;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function applyProfilePermissions(array $profileData, ?DeviceProfile $existingProfile = null): array
    {
        $filteredData = [];
        $user = auth()->user();

        // Asignar domain_uuid
        if ($user->hasPermission('device_profile_domain')) {
            $filteredData['domain_uuid'] = $profileData['domain_uuid'] ?? ($existingProfile->domain_uuid ?? $user->domain_uuid);
        } else {
            $filteredData['domain_uuid'] = $existingProfile->domain_uuid ?? $user->domain_uuid;
        }

        if (is_null($existingProfile)) {
            $filteredData['device_profile_uuid'] = $profileData['device_profile_uuid'] ?? Str::uuid();
        }

        // Campos básicos con permisos
        if ($user->hasPermission('device_profile_add') || $user->hasPermission('device_profile_edit')) {
            $filteredData['device_profile_name'] = $profileData['device_profile_name'] ?? ($existingProfile->device_profile_name ?? null);
            $filteredData['device_profile_enabled'] = $profileData['device_profile_enabled'] ?? ($existingProfile->device_profile_enabled ?? 'true');
            $filteredData['device_profile_description'] = $profileData['device_profile_description'] ?? ($existingProfile->device_profile_description ?? null);
        }

        return $filteredData;
    }

    protected function syncProfileKeys(string $deviceProfileUuid, string $domainUuid, array $keys): void
    {
        foreach ($keys as $keyData) {
            // Validar campos requeridos
            if (empty($keyData['profile_key_vendor']) || empty($keyData['profile_key_id'])) {
                continue;
            }

            if (empty($keyData['device_profile_key_uuid'])) {
                $this->createProfileKey($deviceProfileUuid, $domainUuid, $keyData);
            } else {
                $this->updateProfileKey($keyData['device_profile_key_uuid'], $keyData);
            }
        }
    }

    protected function syncProfileSettings(string $deviceProfileUuid, string $domainUuid, array $settings): void
    {
        foreach ($settings as $settingData) {
            // Validar campos requeridos
            if (empty($settingData['profile_setting_name']) || empty($settingData['profile_setting_enabled'])) {
                continue;
            }

            if (empty($settingData['device_profile_setting_uuid'])) {
                $this->createProfileSetting($deviceProfileUuid, $domainUuid, $settingData);
            } else {
                $this->updateProfileSetting($settingData['device_profile_setting_uuid'], $settingData);
            }
        }
    }

    protected function createProfileKey(string $deviceProfileUuid, string $domainUuid, array $keyData): DeviceProfileKey
    {
        $data = array_merge($keyData, [
            'device_profile_key_uuid' => Str::uuid(),
            'device_profile_uuid' => $deviceProfileUuid,
            'domain_uuid' => $domainUuid,
        ]);

        return $this->deviceProfileKey->create($data);
    }

    protected function updateProfileKey(string $deviceProfileKeyUuid, array $keyData): DeviceProfileKey
    {
        $profileKey = $this->deviceProfileKey->where('device_profile_key_uuid', $deviceProfileKeyUuid)->firstOrFail();
        $profileKey->update($keyData);
        return $profileKey;
    }

    protected function createProfileSetting(string $deviceProfileUuid, string $domainUuid, array $settingData): DeviceProfileSetting
    {
        $data = array_merge($settingData, [
            'device_profile_setting_uuid' => Str::uuid(),
            'device_profile_uuid' => $deviceProfileUuid,
            'domain_uuid' => $domainUuid,
        ]);

        return $this->deviceProfileSetting->create($data);
    }

    protected function updateProfileSetting(string $deviceProfileSettingUuid, array $settingData): DeviceProfileSetting
    {
        $profileSetting = $this->deviceProfileSetting->where('device_profile_setting_uuid', $deviceProfileSettingUuid)->firstOrFail();
        $profileSetting->update($settingData);
        return $profileSetting;
    }

    public function deleteSpecificKeys(string $deviceProfileUuid, array $keyUuids): bool
    {
        return $this->deviceProfileKey
            ->where('device_profile_uuid', $deviceProfileUuid)
            ->whereIn('device_profile_key_uuid', $keyUuids)
            ->delete() > 0;
    }

    public function deleteSpecificSettings(string $deviceProfileUuid, array $settingUuids): bool
    {
        return $this->deviceProfileSetting
            ->where('device_profile_uuid', $deviceProfileUuid)
            ->whereIn('device_profile_setting_uuid', $settingUuids)
            ->delete() > 0;
    }

    public function getProfileKeys(string $deviceProfileUuid): array
    {
        return $this->deviceProfileKey
            ->where('device_profile_uuid', $deviceProfileUuid)
            ->orderBy('profile_key_vendor', 'asc')
            ->orderByRaw("
                case profile_key_category 
                when 'line' then 1 
                when 'memory' then 2 
                when 'programmable' then 3 
                when 'expansion' then 4 
                when 'expansion-1' then 5 
                when 'expansion-2' then 6 
                when 'expansion-3' then 7 
                when 'expansion-4' then 8 
                when 'expansion-5' then 9 
                when 'expansion-6' then 10 
                else 100 end
            ")
            ->orderBy('profile_key_id', 'asc')
            ->get()
            ->toArray();
    }

    public function getProfileSettings(string $deviceProfileUuid): array
    {
        return $this->deviceProfileSetting
            ->where('device_profile_uuid', $deviceProfileUuid)
            ->orderBy('profile_setting_name', 'asc')
            ->get()
            ->toArray();
    }

    public function getDeviceVendors(): array
    {
        $vendors = DB::table($this->deviceVendorsTable)
            ->where('enabled', 'true')
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
        $functions = DB::table($this->deviceVendorsTable . ' as v')
            ->join($this->deviceVendorFunctionsTable . ' as f', 'v.device_vendor_uuid', '=', 'f.device_vendor_uuid')
            ->where('v.enabled', 'true')
            ->where('f.enabled', 'true')
            ->select('v.name as vendor_name', 'f.type', 'f.subtype', 'f.value')
            ->orderBy('v.name', 'asc')
            ->orderBy('f.type', 'asc')
            ->get()
            ->toArray();

        return array_map(function ($function) {
            return (array) $function;
        }, $functions);
    }

    public function getVendorCount(string $deviceProfileUuid): int
    {
        $keys = $this->getProfileKeys($deviceProfileUuid);
        $vendors = array_unique(array_column($keys, 'profile_key_vendor'));
        return count(array_filter($vendors));
    }

    public function shouldShowKeySubtype(string $deviceProfileUuid): bool
    {
        $keys = $this->getProfileKeys($deviceProfileUuid);
        foreach ($keys as $key) {
            if ($key['profile_key_vendor'] === 'fanvil') {
                return true;
            }
        }
        return false;
    }

    public function getDomain(): array
    {
        return Domain::select('domain_uuid', 'domain_name')->get()->toArray();
    }

    public function getProfilesForDomain(string $domainUuid): array
    {
        return $this->deviceProfile
            ->where('domain_uuid', $domainUuid)
            ->where('device_profile_enabled', 'true')
            ->orderBy('device_profile_name', 'asc')
            ->get()
            ->toArray();
    }

    public function addEmptyKeysRows(array $keys, int $rowCount, string $deviceProfileUuid, string $domainUuid): array
    {
        $startIndex = count($keys);
        
        for ($i = 0; $i < $rowCount; $i++) {
            $keys[$startIndex + $i] = [
                'domain_uuid' => $domainUuid,
                'device_profile_uuid' => $deviceProfileUuid,
                'device_profile_key_uuid' => '',
                'profile_key_category' => '',
                'profile_key_id' => '',
                'profile_key_vendor' => '',
                'profile_key_type' => '',
                'profile_key_subtype' => '',
                'profile_key_line' => '',
                'profile_key_value' => '',
                'profile_key_extension' => '',
                'profile_key_protected' => '',
                'profile_key_label' => '',
                'profile_key_icon' => '',
            ];
        }

        return $keys;
    }

    public function addEmptySettingsRow(array $settings, string $deviceProfileUuid, string $domainUuid): array
    {
        $settings[] = [
            'domain_uuid' => $domainUuid,
            'device_profile_uuid' => $deviceProfileUuid,
            'device_profile_setting_uuid' => '',
            'profile_setting_name' => '',
            'profile_setting_value' => '',
            'profile_setting_enabled' => '',
            'profile_setting_description' => '',
        ];

        return $settings;
    }
}