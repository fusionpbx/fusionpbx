<?php

namespace App\Repositories;

use App\Facades\Setting;
use App\Models\Extension;
use App\Models\ExtensionUser;
use App\Models\User;
use App\Models\Voicemail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ExtensionRepository
{
    protected $extension;
    protected $extensionUser;

    public function __construct(Extension $extension, ExtensionUser $extensionUser)
    {
        $this->extension = $extension;
        $this->extensionUser = $extensionUser;
    }

    public function all()
    {
        return $this->extension->all();
    }

    public function findByUuid(string $uuid, bool $withRelations = false)
    {
        $query = $this->extension->where('extension_uuid', $uuid);

        if ($withRelations) {
            $query->with(['users', 'domain', 'settings', 'xmlcdr', 'extensionUsers']);
        }

        return $query->firstOrFail();
    }

    private function applyExtensionPermissions(array $extensionData, ?Extension $existingExtension = null): array
    {
        $filteredData = [];
        $user = auth()->user();

        $filteredData['domain_uuid'] = $extensionData['domain_uuid'] ?? ($existingExtension->domain_uuid ?? null);
        $filteredData['extension'] = $extensionData['extension'] ?? ($existingExtension->extension ?? null);

        if (is_null($existingExtension)) {
            $filteredData['extension_uuid'] = $extensionData['extension_uuid'] ?? Str::uuid();
        }

        if ($user->hasPermission('number_alias')) {
            $filteredData['number_alias'] = $extensionData['number_alias'] ?? ($existingExtension->number_alias ?? null);
        }

        if (is_null($existingExtension) && empty($extensionData['password'])) {
            $passwordLength = Setting::getSetting('extension', 'password_length', 'numeric');
            $passwordStrength = Setting::getSetting('extension', 'password_strength', 'numeric');
            $filteredData['password'] = generatePassword($passwordLength, $passwordStrength);
        } elseif ($user->hasPermission('extension_password') && empty($extensionData['password'])) {
            $passwordLength = Setting::getSetting('extension', 'password_length', 'numeric');
            $passwordStrength = Setting::getSetting('extension', 'password_strength', 'numeric');
            $filteredData['password'] = generatePassword($passwordLength, $passwordStrength);
        } elseif (!empty($extensionData['password'])) {
            $filteredData['password'] = $extensionData['password'];
        }

        if ($user->hasPermission('extension_accountcode')) {
            $filteredData['accountcode'] = $extensionData['accountcode'] ?? ($existingExtension->accountcode ?? null);
        } else {
            if (is_null($existingExtension)) {
                $filteredData['accountcode'] = getAccountCode() ?? $extensionData['domain_uuid'];
            }
        }

        if ($user->hasPermission('effective_caller_id_name')) {
            $filteredData['effective_caller_id_name'] = $extensionData['effective_caller_id_name'] ?? ($existingExtension->effective_caller_id_name ?? null);
        }

        if ($user->hasPermission('effective_caller_id_number')) {
            $filteredData['effective_caller_id_number'] = $extensionData['effective_caller_id_number'] ?? ($existingExtension->effective_caller_id_number ?? null);
        }

        if ($user->hasPermission('outbound_caller_id_name')) {
            $filteredData['outbound_caller_id_name'] = $extensionData['outbound_caller_id_name'] ?? ($existingExtension->outbound_caller_id_name ?? null);
        }

        if ($user->hasPermission('outbound_caller_id_number')) {
            $filteredData['outbound_caller_id_number'] = $extensionData['outbound_caller_id_number'] ?? ($existingExtension->outbound_caller_id_number ?? null);
        }

        if ($user->hasPermission('emergency_caller_id_name')) {
            $filteredData['emergency_caller_id_name'] = $extensionData['emergency_caller_id_name'] ?? ($existingExtension->emergency_caller_id_name ?? null);
        }

        if ($user->hasPermission('emergency_caller_id_number')) {
            $filteredData['emergency_caller_id_number'] = $extensionData['emergency_caller_id_number'] ?? ($existingExtension->emergency_caller_id_number ?? null);
        }

        if ($user->hasPermission('extension_directory')) {
            $filteredData['directory_first_name'] = $extensionData['directory_first_name'] ?? ($existingExtension->directory_first_name ?? null);
            $filteredData['directory_last_name'] = $extensionData['directory_last_name'] ?? ($existingExtension->directory_last_name ?? null);
            $filteredData['directory_visible'] = $extensionData['directory_visible'] ?? ($existingExtension->directory_visible ?? null);
            $filteredData['directory_exten_visible'] = $extensionData['directory_exten_visible'] ?? ($existingExtension->directory_exten_visible ?? null);
        }

        if ($user->hasPermission('extension_max_registrations')) {
            $filteredData['max_registrations'] = $extensionData['max_registrations'] ?? ($existingExtension->max_registrations ?? null);
        } else {
            if (is_null($existingExtension)) {
                $filteredData['max_registrations'] = Setting::getSetting('extension', 'max_registrations', 'numeric');
            }
        }

        if ($user->hasPermission('extension_limit')) {
            $filteredData['limit_max'] = $extensionData['limit_max'] ?? ($existingExtension->limit_max ?? null);
            $filteredData['limit_destination'] = $extensionData['limit_destination'] ?? ($existingExtension->limit_destination ?? null);
        }

        if ($user->hasPermission('extension_user_context')) {
            $filteredData['user_context'] = $extensionData['user_context'] ?? ($existingExtension->user_context ?? null);
        } else {
            if (is_null($existingExtension)) {
                $filteredData['user_context'] = $user->domain_name ?? $extensionData['domain_uuid'];
            }
        }

        if ($user->hasPermission('extension_missed_call')) {
            $filteredData['missed_call_app'] = $extensionData['missed_call_app'] ?? ($existingExtension->missed_call_app ?? null);
            $filteredData['missed_call_data'] = $extensionData['missed_call_data'] ?? ($existingExtension->missed_call_data ?? null);
        }

        if ($user->hasPermission('extension_toll')) {
            $filteredData['toll_allow'] = $extensionData['toll_allow'] ?? ($existingExtension->toll_allow ?? null);
        }

        if (isset($extensionData['call_timeout']) || !empty($extensionData['call_timeout'])) {
            $filteredData['call_timeout'] = $extensionData['call_timeout'];
        }

        if ($user->hasPermission('extension_call_group')) {
            $filteredData['call_group'] = $extensionData['call_group'] ?? ($existingExtension->call_group ?? null);
        }

        $filteredData['call_screen_enabled'] = $extensionData['call_screen_enabled'] ?? ($existingExtension->call_screen_enabled ?? null);

        if ($user->hasPermission('extension_user_record')) {
            $filteredData['user_record'] = $extensionData['user_record'] ?? ($existingExtension->user_record ?? null);
        }

        if ($user->hasPermission('extension_hold_music')) {
            $filteredData['hold_music'] = $extensionData['hold_music'] ?? ($existingExtension->hold_music ?? null);
        }

        if ($user->hasPermission('extension_advanced')) {
            $filteredData['auth_acl'] = $extensionData['auth_acl'] ?? ($existingExtension->auth_acl ?? null);
            $filteredData['sip_force_contact'] = $extensionData['sip_force_contact'] ?? ($existingExtension->sip_force_contact ?? null);
            $filteredData['sip_force_expires'] = $extensionData['sip_force_expires'] ?? ($existingExtension->sip_force_expires ?? null);
            $filteredData['sip_bypass_media'] = $extensionData['sip_bypass_media'] ?? ($existingExtension->sip_bypass_media ?? null);

            if ($user->hasPermission('extension_cidr')) {
                $filteredData['cidr'] = $extensionData['cidr'] ?? ($existingExtension->cidr ?? null);
            }

            if ($user->hasPermission('extension_nibble_account')) {
                if (!empty($extensionData['nibble_account'])) {
                    $filteredData['nibble_account'] = $extensionData['nibble_account'];
                } elseif ($existingExtension && !empty($existingExtension->nibble_account)) {
                    $filteredData['nibble_account'] = $existingExtension->nibble_account;
                }
            }

            if (isset($extensionData['mwi_account'])) {
                $mwiAccount = $extensionData['mwi_account'];
                if (!empty($mwiAccount) && strpos($mwiAccount, '@') === false) {
                    $mwiAccount .= "@" . $user->domain_name;
                }
                $filteredData['mwi_account'] = $mwiAccount;
            } elseif ($existingExtension && !empty($existingExtension->mwi_account)) {
                $filteredData['mwi_account'] = $existingExtension->mwi_account;
            }

            if ($user->hasPermission('extension_absolute_codec_string')) {
                $filteredData['absolute_codec_string'] = $extensionData['absolute_codec_string'] ?? ($existingExtension->absolute_codec_string ?? null);
            }

            if ($user->hasPermission('extension_force_ping')) {
                $filteredData['force_ping'] = $extensionData['force_ping'] ?? ($existingExtension->force_ping ?? null);
            }

            if ($user->hasPermission('extension_dial_string')) {
                $filteredData['dial_string'] = $extensionData['dial_string'] ?? ($existingExtension->dial_string ?? null);
            }
        }

        if ($user->hasPermission('extension_enabled')) {
            $filteredData['enabled'] = $extensionData['enabled'] ?? ($existingExtension->enabled ?? 'true');
        }

        $filteredData['description'] = $extensionData['description'] ?? ($existingExtension->description ?? null);

        return $filteredData;
    }

    protected function incrementString(string $base, int $increment): string
    {
        $prefix = substr($base, 0, -1);
        $lastChar = substr($base, -1);

        $newChar = chr(ord($lastChar) + $increment);

        return $prefix . $newChar;
    }


    public function create(array $extensionData, array $extensionUsers = []): array
    {
        $extensionData['extension_uuid'] = $extensionData['extension_uuid'] ?? Str::uuid();
        $range = intval($extensionData['range'] ?? 1);
        $baseExtension = $extensionData['extension'];
        $baseNumberAlias = $extensionData['number_alias'] ?? null;
        $baseMwiAccount = $extensionData['mwi_account'] ?? null;
        $createdExtensions = [];

        if ($range > 1) {
            $this->validateExtensionLimit($extensionData['domain_uuid'], $range);
        }

        try {
            DB::beginTransaction();

            for ($i = 0; $i < $range; $i++) {
                if (is_numeric($baseExtension)) {
                    $currentExtension = $baseExtension + $i;
                    $currentNumberAlias = !empty($baseNumberAlias) ? $baseNumberAlias + $i : null;
                    $currentMwiAccount = $this->incrementMwiAccount($baseMwiAccount, $i);
                } else {
                    $currentExtension = $this->incrementString($baseExtension, $i);
                    $currentNumberAlias = !empty($baseNumberAlias) ? $this->incrementString($baseNumberAlias, $i) : null;
                    $currentMwiAccount = $this->incrementMwiAccount($baseMwiAccount, $i);
                }

                if ($this->extensionExists($currentExtension, $extensionData['domain_uuid'])) {
                    continue;
                }

                $currentExtensionData = $extensionData;
                $currentExtensionData['extension'] = $currentExtension;
                $currentExtensionData['number_alias'] = $currentNumberAlias;
                $currentExtensionData['mwi_account'] = $currentMwiAccount;

                $filteredData = $this->applyExtensionPermissions($currentExtensionData);


                $extension = $this->extension->create($filteredData);

                if (!empty($extensionUsers)) {
                    $this->syncExtensionUsers(
                        $extension->extension_uuid,
                        $extensionUsers,
                        $extensionData['domain_uuid']
                    );
                }

                // // TDOO - Uncomment if device assignment is needed
                // if (!empty($extensionData['device_mac_addresses']) && is_array($extensionData['device_mac_addresses'])) {
                //     $this->assignDevicesToExtension($extension, $extensionData);
                // }

                if ($this->shouldCreateVoicemail($currentExtensionData)) {
                    $this->createVoicemail($extension, $currentExtensionData);
                }

                $createdExtensions[] = $extension;
            }
            DB::commit();
            return $createdExtensions;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(string $uuid, array $extensionData, array $extensionUsers = [], array $usersToDelete = []): Extension
    {
        try {
            DB::beginTransaction();

            $extension = $this->findByUuid($uuid);

            $filteredData = $this->applyExtensionPermissions($extensionData, $extension);

            $extension->update($filteredData);

            if (!empty($usersToDelete)) {
                $this->deleteExtensionUsers($usersToDelete);
            }

            if (!empty($extensionUsers)) {
                $this->syncExtensionUsers(
                    $extension->extension_uuid,
                    $extensionUsers,
                    $extensionData['domain_uuid'] ?? $extension->domain_uuid
                );
            }

            if (isset($extensionData['voicemail_enabled']) || isset($extensionData['voicemail_password'])) {
                $this->updateVoicemail($extension, $extensionData);
            }

            // 
            // if (!empty($extensionData['device_mac_addresses']) && is_array($extensionData['device_mac_addresses'])) {
            //     $this->assignDevicesToExtension($extension, $extensionData);
            // }

            DB::commit();
            return $extension->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function delete(string $uuid): void
    {
        try {
            DB::beginTransaction();
            $extension = $this->findByUuid($uuid);
            $extension->delete();
            $extension->extensionUsers->delete();
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function syncExtensionUsers(string $extensionUuid, array $extensionUsers, ?string $defaultDomainUuid = null): void
    {
        foreach ($extensionUsers as $userData) {
            if (empty($userData['user_uuid'])) {
                continue;
            }

            $domainUuid = $this->resolveDomainUuid($userData, $defaultDomainUuid);

            if (empty($userData['extension_user_uuid'])) {
                $this->createExtensionUser($extensionUuid, $userData, $domainUuid);
            } else {
                $this->updateExtensionUser($userData['extension_user_uuid'], $userData, $domainUuid);
            }
        }
    }

    protected function resolveDomainUuid(array $userData, ?string $defaultDomainUuid = null): ?string
    {
        if (auth()->user()->hasPermission('extension_domain') && !empty($userData['domain_uuid'])) {
            return $userData['domain_uuid'];
        }

        if (!empty($defaultDomainUuid)) {
            return $defaultDomainUuid;
        }

        if (!empty($userData['user_uuid'])) {
            $user = User::where('user_uuid', $userData['user_uuid'])->first();
            if ($user && !empty($user->domain_uuid)) {
                return $user->domain_uuid;
            }
        }

        return null;
    }

    protected function createExtensionUser(string $extensionUuid, array $userData, ?string $domainUuid = null): ExtensionUser
    {
        $extensionUserData = [
            'extension_user_uuid' => Str::uuid(),
            'extension_uuid' => $extensionUuid,
            'user_uuid' => $userData['user_uuid'],
            'domain_uuid' => $domainUuid,
        ];
        return $this->extensionUser->create($extensionUserData);
    }

    protected function updateExtensionUser(string $extensionUserUuid, array $userData, ?string $domainUuid = null): ExtensionUser
    {
        $extensionUser = $this->extensionUser->where('extension_user_uuid', $extensionUserUuid)->firstOrFail();

        $updateData = [
            'user_uuid' => $userData['user_uuid'],
            'domain_uuid' => $domainUuid ?? $extensionUser->domain_uuid,
        ];

        $extensionUser->update($updateData);
        return $extensionUser;
    }

    protected function deleteExtensionUsers(array $extensionUserUuids): void
    {
        try {
            DB::beginTransaction();
            $this->extensionUser->whereIn('extension_user_uuid', $extensionUserUuids)->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getExtensionWithUsers(string $uuid)
    {
        return $this->extension->where('extension_uuid', $uuid)
            ->with(['users', 'domain'])
            ->firstOrFail();
    }

    public function getDomains(string $uuid)
    {
        return $this->extension->where('extension_uuid', $uuid)
            ->with('domain')
            ->firstOrFail();
    }

    public function getAvailableUsers()
    {
        return User::select('user_uuid', 'username', 'user_email', 'domain_uuid')
            ->with('domain:domain_uuid,domain_name')
            ->orderBy('username')
            ->get();
    }


    protected function validateExtensionLimit(string $domainUuid, int $range): void
    {
        $extensionLimit = Setting::getSetting('extension', 'limit', 'numeric');

        if (!empty($extensionLimit)) {
            $currentCount = $this->extension->where('domain_uuid', $domainUuid)->count();

            if ($currentCount + $range > $extensionLimit) {
                $range = $extensionLimit - $currentCount;
            }
        }
    }

    protected function extensionExists(string $extension, string $domainUuid): bool
    {
        return $this->extension
            ->where('extension', $extension)
            ->where('domain_uuid', $domainUuid)
            ->exists();
    }

    protected function incrementMwiAccount(?string $mwiAccount, int $increment): ?string
    {
        if (empty($mwiAccount)) {
            return null;
        }

        if (strpos($mwiAccount, '@') === false) {
            $mwiAccount .= "@" . auth()->user()->domain->domain_name;
        }

        if ($increment > 0) {
            $mwiAccountArray = explode('@', $mwiAccount);
            $mwiAccountArray[0] = intval($mwiAccountArray[0]) + $increment;
            return implode('@', $mwiAccountArray);
        }

        return $mwiAccount;
    }

    protected function shouldCreateVoicemail(array $extensionData): bool
    {
        return ($extensionData['voicemail_enabled'] ?? false) &&
            class_exists('App\Models\Voicemail');
    }

    protected function createVoicemail(Extension $extension, array $extensionData): void
    {
        $voicemailId = !empty($extensionData['number_alias']) ? $extensionData['number_alias'] : $extensionData['extension'];

        $voicemailData = [
            'domain_uuid' => $extensionData['domain_uuid'],
            'voicemail_uuid' => Str::uuid(),
            'voicemail_id' => $voicemailId,
            'voicemail_password' => $extensionData['voicemail_password'] ??
                generatePassword(Setting::getSetting('voicemail', 'password_length', 'numeric'), 1),
            'voicemail_mail_to' => $extensionData['voicemail_mail_to'] ?? '',
            'voicemail_transcription_enabled' => $extensionData['voicemail_transcription_enabled'] ?? false,
            'voicemail_file' => $extensionData['voicemail_file'] ??
                Setting::getSetting('voicemail', 'voicemail_file', 'text'),
            'voicemail_local_after_email' => $extensionData['voicemail_local_after_email'] ??
                Setting::getSetting('voicemail', 'keep_local', 'boolean'),
            'voicemail_enabled' => $extensionData['voicemail_enabled'] ?? true,
            'voicemail_tutorial' => 'true',
            'voicemail_description' => $extensionData['description'] ?? '',
        ];


        Voicemail::create($voicemailData);

        $this->createVoicemailDirectory($extensionData['domain_name'] ?? '', $voicemailId);
    }

    protected function updateVoicemail(Extension $extension, array $extensionData): void
    {
        if (!$this->shouldCreateVoicemail($extensionData)) {
            return;
        }

        $voicemailId = !empty($extensionData['number_alias']) ? $extensionData['number_alias'] : $extension->extension;

        $voicemail = Voicemail::where('domain_uuid', $extension->domain_uuid)
            ->where('voicemail_id', $voicemailId)
            ->first();

        if ($voicemail) {
            $voicemail->update([
                'voicemail_password' => $extensionData['voicemail_password'] ?? $voicemail->voicemail_password,
                'voicemail_mail_to' => $extensionData['voicemail_mail_to'] ?? '',
                'voicemail_transcription_enabled' => $extensionData['voicemail_transcription_enabled'] ?? false,
                'voicemail_file' => $extensionData['voicemail_file'] ?? $voicemail->voicemail_file,
                'voicemail_local_after_email' => $extensionData['voicemail_local_after_email'] ?? $voicemail->voicemail_local_after_email,
                'voicemail_enabled' => $extensionData['voicemail_enabled'] ?? true,
                'voicemail_description' => $extensionData['description'] ?? '',
            ]);
        } else {
            $this->createVoicemail($extension, $extensionData);
        }
    }

    protected function createVoicemailDirectory(string $domainName, string $voicemailId): void
    {
        $voicemailDir = Setting::getSetting('switch', 'voicemail', 'dir');
        $directory = "{$voicemailDir}/default/{$domainName}/{$voicemailId}";

        if (!file_exists($directory)) {
            mkdir($directory, 0770, true);
        }
    }

    public function copy(string $uuid, string $extensionCopy, string $numberAliasCopy): Extension
    {

        try {
            DB::beginTransaction();

            $originalExtension = $this->findByUuid($uuid, true);
            
            $newExtension = $originalExtension->replicate();
            $newExtension->extension_uuid = Str::uuid();
            $newExtension->extension = $extensionCopy;
            $newExtension->number_alias = $numberAliasCopy;
            $newExtension->description = $originalExtension->description . ' (copy)';
            $newExtension->save();

            $originalVoicemail = $originalExtension->voicemail;

            if ($originalVoicemail) {
                $newVoicemail = $originalVoicemail->replicate();
                $newVoicemail->voicemail_uuid = Str::uuid();
                $newVoicemail->voicemail_id = $newExtension->number_alias ?? $newExtension->extension;
                $newVoicemail->save();
            }
    
            DB::commit();
            return $newExtension;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
