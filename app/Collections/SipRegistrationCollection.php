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
        
        $sipProfileNames = $this->pluck('sip_profile_name')->filter()->toArray();

        
        
        if (empty($sipProfileNames)) {
            return $this;
        }


        $registrationData = app(FreeSwitchRegistrationService::class)->fetchRegistrationStatus($sipProfileNames);
        
        
        $this->each(function ($sipProfile, $key) use ($registrationData) {
            $username = $sipProfile->sip_profile_name;
            
            if (isset($registrationData[$username])) {
                $sipProfile->is_registered = $registrationData[$username]['registered'] ?? false;
                $sipProfile->last_registration = $registrationData[$username]['timestamp'] ?? null;
                $sipProfile->registration_ip = $registrationData[$username]['ip_address'] ?? null;
                $sipProfile->network_port = $registrationData[$username]['network_port'] ?? null;
                $sipProfile->agent = $registrationData[$username]['agent'] ?? null;
                $sipProfile->user = $registrationData[$username]['user'] ?? null;
                $sipProfile->contact = $registrationData[$username]['contact'] ?? null;
                $sipProfile->connection_status = $registrationData[$username]['status'] ?? 'unknown';
                $sipProfile->lan_ip = $registrationData[$username]['lan_ip'] ?? null;
                $sipProfile->ping_status = $registrationData[$username]['ping_status'] ?? null;
                $sipProfile->ping_time = $registrationData[$username]['ping_time'] ?? null;

            } else {
                
                $this->forget($key);
            }
        });
    }
}