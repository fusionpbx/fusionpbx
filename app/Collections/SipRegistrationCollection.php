<?php

namespace App\Collections;

use App\Services\FreeSwitch\FreeSwitchRegistrationService;
use Illuminate\Database\Eloquent\Collection;
use App\Services\FreeSwitch\FreeSwitchService;

class SipRegistrationCollection extends Collection
{
    public function withRegistrationStatus()
    {
        if ($this->isEmpty()) {
            return $this;
        }
        
        $showAll = request()->input('show') === 'all';
        $sipProfileNames = $this->pluck('sip_profile_name')->filter()->toArray();
        
        if (empty($sipProfileNames)) {
            return $this;
        }

        $registrationData = app(FreeSwitchRegistrationService::class)->fetchRegistrationStatus($sipProfileNames);
        
        $allProfiles = $this->all();
        $result = new static([]);
        
        foreach ($allProfiles as $sipProfile) {
            $username = $sipProfile->sip_profile_name;
            
            $sipProfile->is_registered = false;
            $sipProfile->last_registration = null;
            $sipProfile->registration_ip = null;
            $sipProfile->network_port = null;
            $sipProfile->agent = null;
            $sipProfile->user = $username . '@' . ($sipProfile->sip_profile_hostname ?? 'domain.local');
            $sipProfile->contact = null;
            $sipProfile->connection_status = 'Not Registered';
            $sipProfile->lan_ip = null;
            $sipProfile->ping_status = null;
            $sipProfile->ping_time = null;
            
            if (isset($registrationData[$username])) {
                $sipProfile->is_registered = $registrationData[$username]['registered'] ?? false;
                $sipProfile->last_registration = $registrationData[$username]['timestamp'] ?? null;
                $sipProfile->registration_ip = $registrationData[$username]['ip_address'] ?? null;
                $sipProfile->network_port = $registrationData[$username]['network_port'] ?? null;
                $sipProfile->agent = $registrationData[$username]['agent'] ?? null;
                $sipProfile->user = $registrationData[$username]['user'] ?? $sipProfile->user;
                $sipProfile->contact = $registrationData[$username]['contact'] ?? null;
                $sipProfile->connection_status = $registrationData[$username]['status'] ?? 'Not Registered';
                $sipProfile->lan_ip = $registrationData[$username]['lan_ip'] ?? null;
                $sipProfile->ping_status = $registrationData[$username]['ping_status'] ?? null;
                $sipProfile->ping_time = $registrationData[$username]['ping_time'] ?? null;
                
                $result->push($sipProfile);
            } elseif ($showAll) {
               
                $result->push($sipProfile);
            }
        }
        
        
        $this->items = $result->all();
        
        return $this;
    }
}