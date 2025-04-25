<?php

namespace App\Repositories;

use App\Models\SipProfile;
use App\Models\SipProfileDomain;
use App\Models\SipProfileSetting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SipProfileRepository
{
    protected $sipProfile;
    protected $sipProfileDomain;
    protected $sipProfileSetting;

    public function __construct(
        SipProfile $sipProfile,
        SipProfileDomain $sipProfileDomain,
        SipProfileSetting $sipProfileSetting
    ) {
        $this->sipProfile = $sipProfile;
        $this->sipProfileDomain = $sipProfileDomain;
        $this->sipProfileSetting = $sipProfileSetting;
    }


    public function all(): Collection
    {
        return $this->sipProfile->all();
    }

    public function findByUuid(string $uuid, bool $withRelations = false)
    {
        $query = $this->sipProfile->where('sip_profile_uuid', $uuid);

        if ($withRelations) {
            $query->with(['sipprofiledomains', 'sipprofilesettings']);
        }

        return $query->firstOrFail();
    }


    public function create(array $profileData, array $domains = [], array $settings = []): SipProfile
    {
        $profileData['sip_profile_uuid'] = $profileData['sip_profile_uuid'] ?? Str::uuid();

        DB::beginTransaction();
        try {
            $profile = $this->sipProfile->create($profileData);


            foreach ($domains as $domain) {
                $this->createDomain($profile->sip_profile_uuid, $domain);
            }


            foreach ($settings as $setting) {
                $this->createSetting($profile->sip_profile_uuid, $setting);
            }

            DB::commit();
            return $profile;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(
        string $uuid,
        array $profileData,
        array $domains = [],
        array $settings = [],
        array $domainsToDelete = [],
        array $settingsToDelete = []
    ): SipProfile {
        DB::beginTransaction();
        try {
            $profile = $this->findByUuid($uuid);
            $profile->update($profileData);

            foreach ($domains as $domain) {
                if (empty($domain['sip_profile_domain_uuid'])) {
                    $this->createDomain($profile->sip_profile_uuid, $domain);
                } else {
                    $this->updateDomain($domain['sip_profile_domain_uuid'], $domain);
                }
            }

            if (!empty($domainsToDelete)) {
                $this->deleteDomains($domainsToDelete);
            }

            foreach ($settings as $setting) {
                if (empty($setting['sip_profile_setting_uuid'])) {
                    $this->createSetting($profile->sip_profile_uuid, $setting);
                } else {
                    $this->updateSetting($setting['sip_profile_setting_uuid'], $setting);
                }
            }

            if (!empty($settingsToDelete)) {
                $this->deleteSettings($settingsToDelete);
            }

            DB::commit();
            return $profile;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    public function delete(string $uuid): bool
    {
        DB::beginTransaction();
        try {
            $profile = $this->findByUuid($uuid);


            $profile->sipprofiledomains()->delete();
            $profile->sipprofilesettings()->delete();


            $result = $profile->delete();

            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    public function copy(string $uuid): SipProfile
    {
        DB::beginTransaction();
        try {
            $originalProfile = $this->findByUuid($uuid, true);

            $newProfile = $originalProfile->replicate();
            $newProfile->sip_profile_uuid = Str::uuid();
            $newProfile->sip_profile_description = $originalProfile->sip_profile_description . ' (copy)';
            $newProfile->save();

            foreach ($originalProfile->sipprofiledomains as $domain) {
                $newDomain = $domain->replicate();
                $newDomain->sip_profile_domain_uuid = Str::uuid();
                $newDomain->sip_profile_uuid = $newProfile->sip_profile_uuid;
                $newDomain->save();
            }

            foreach ($originalProfile->sipprofilesettings as $setting) {
                $newSetting = $setting->replicate();
                $newSetting->sip_profile_setting_uuid = Str::uuid();
                $newSetting->sip_profile_uuid = $newProfile->sip_profile_uuid;
                $newSetting->save();
            }

            DB::commit();
            return $newProfile;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function createDomain(string $profileUuid, array $domainData): SipProfileDomain
    {
        $domainData['sip_profile_domain_uuid'] = $domainData['sip_profile_domain_uuid'] ?? Str::uuid();
        $domainData['sip_profile_uuid'] = $profileUuid;

        return $this->sipProfileDomain->create($domainData);
    }


    private function updateDomain(string $domainUuid, array $domainData): bool
    {
        return $this->sipProfileDomain
            ->where('sip_profile_domain_uuid', $domainUuid)
            ->update([
                'sip_profile_domain_name' => $domainData['sip_profile_domain_name'],
                'sip_profile_domain_alias' => $domainData['sip_profile_domain_alias'],
                'sip_profile_domain_parse' => $domainData['sip_profile_domain_parse'],
            ]);
    }


    private function deleteDomains(array $uuids): int
    {
        return $this->sipProfileDomain->whereIn('sip_profile_domain_uuid', $uuids)->delete();
    }


    private function createSetting(string $profileUuid, array $settingData): SipProfileSetting
    {
        $settingData['sip_profile_setting_uuid'] = $settingData['sip_profile_setting_uuid'] ?? Str::uuid();
        $settingData['sip_profile_uuid'] = $profileUuid;

        return $this->sipProfileSetting->create($settingData);
    }


    private function updateSetting(string $settingUuid, array $settingData): bool
    {
        return $this->sipProfileSetting
            ->where('sip_profile_setting_uuid', $settingUuid)
            ->update([
                'sip_profile_setting_name' => $settingData['sip_profile_setting_name'],
                'sip_profile_setting_value' => $settingData['sip_profile_setting_value'],
                'sip_profile_setting_enabled' => $settingData['sip_profile_setting_enabled'],
                'sip_profile_setting_description' => $settingData['sip_profile_setting_description'],
            ]);
    }

    private function deleteSettings(array $uuids): int
    {
        return $this->sipProfileSetting->whereIn('sip_profile_setting_uuid', $uuids)->delete();
    }
}
