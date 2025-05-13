<?php

namespace App\Services\FreeSwitch;

use App\Collections\SipRegistrationCollection;
use App\Facades\FreeSwitch;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class FreeSwitchRegistrationService
{
    protected FreeSwitchService $freeSwitchService;
    protected bool $useMockResponses;

    public function __construct(FreeSwitchService $freeSwitchService)
    {
        $this->freeSwitchService = $freeSwitchService;
        $this->useMockResponses = env('FREESWITCH_USE_MOCK_RESPONSES', false);
    }

    public function getRegistrations(array $sipProfileUserNames): array
    {
        $id = 0;
        $registrations = [];

        foreach ($sipProfileUserNames as $profileName) {
            if ($this->useMockResponses) {
                $xml_response = $this->getMockRegistrationXml($profileName);
            } else {
                $xml_response = FreeSwitch::execute(
                    "sofia xmlstatus profile",
                    "'$profileName' reg"
                );
                
            }

            if (empty($xml_response) || str_contains($xml_response, 'Invalid')) {
                continue;
            }

            $xml_response = $this->normalizeXmlResponse($xml_response);
            if (App::hasDebugModeEnabled()) {
                Log::error('[' . __CLASS__ . '][' . __METHOD__ . '] Normalized XML: ' . $xml_response);
            }

            if (App::hasDebugModeEnabled()) {
                Log::debug('[' . __CLASS__ . '][' . __METHOD__ . '] XML Response: ' . $xml_response);
            }

            try {
                libxml_use_internal_errors(true);
                $xml = new SimpleXMLElement($xml_response);
                dd($xml);
                if ($xml === false) {
                    $errors = libxml_get_errors();
                    if (!empty($errors)) {
                        if (App::hasDebugModeEnabled()) {
                            Log::error('[' . __CLASS__ . '][' . __METHOD__ . '] XML Errors: ' . print_r($errors, true));
                        }
                    }
                }
                $array = json_decode(json_encode($xml), true);

                if (!empty($array) && isset($array['registrations']['registration'])) {
                    if (!isset($array['registrations']['registration'][0])) {
                        $row = $array['registrations']['registration'];
                        unset($array['registrations']['registration']);
                        $array['registrations']['registration'][0] = $row;
                    }

                    foreach ($array['registrations']['registration'] as $row) {
                        $user_array = explode('@', $row['user'] ?? '');

                        $registrations[$id] = [
                            'user' => $row['user'] ?? '',
                            'call-id' => $row['call-id'] ?? '',
                            'contact' => $row['contact'] ?? '',
                            'sip-auth-user' => $row['sip-auth-user'] ?? '',
                            'agent' => $row['agent'] ?? '',
                            'host' => $row['host'] ?? '',
                            'network-ip' => $row['network-ip'] ?? '',
                            'network-port' => $row['network-port'] ?? '',
                            'sip-auth-realm' => $row['sip-auth-realm'] ?? '',
                            'mwi-account' => $row['mwi-account'] ?? '',
                            'status' => $row['status'] ?? '',
                            'ping-time' => $row['ping-time'] ?? '',
                            'ping-status' => $row['ping-status'] ?? '',
                            'sip_profile_name' => $profileName
                        ];

                        $registrations[$id]['lan-ip'] = $this->extractLanIp($row);

                        $id++;
                    }
                }
            } catch (\Exception $e) {
                throw $e;
                if (App::hasDebugModeEnabled()) {
                    Log::error('[' . __CLASS__ . '][' . __METHOD__ . '] Error parsing XML: ' . $e->getMessage());
                }
                continue;
            }
        }
        return $registrations;
    }

    public function getActiveProfilesWithRegistrations(): array
    {
        $activeProfiles = $this->getActiveProfiles();
        $profilesWithRegistrations = [];

        foreach ($activeProfiles as $profileName) {
            if ($this->useMockResponses) {
                $xml_response = $this->getMockRegistrationXml($profileName);
            } else {
                $xml_response = FreeSwitch::execute(
                    "sofia xmlstatus profile",
                    "'$profileName' reg"
                );
            }

            if (empty($xml_response) || $xml_response == "Invalid Profile!") {
                continue;
            }

            $xml_response = $this->normalizeXmlResponse($xml_response);

            try {
                $xml = new SimpleXMLElement($xml_response);
                $array = json_decode(json_encode($xml), true);

                if (!empty($array) && isset($array['registrations']['registration'])) {
                    $profilesWithRegistrations[] = $profileName;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $profilesWithRegistrations;
    }


    public function getActiveProfiles(string $specificProfile = 'all'): array
    {
        if ($specificProfile !== 'all') {
            return [$specificProfile];
        }

        if ($this->useMockResponses) {
            return $this->getMockActiveProfiles();
        }

        $response = FreeSwitch::execute('show', 'registrations as xml');

        if (empty($response)) {
            return [];
        }

        $profiles = [];
        $lines = explode("\n", $response);

        foreach ($lines as $line) {
            if (preg_match('/^\s*(\w+)\s+(\w+)\s+running/', $line, $matches)) {
                $profiles[] = $matches[1];
            }
        }


        return $profiles;
    }

    private function normalizeXmlResponse(string $xml_response): string
    {
        $xml_response = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $xml_response);
        if ($xml_response == "Invalid Profile!") {
            $xml_response = "<error_msg>" . !empty($text['label-message']) . "</error_msg>";
        }
        $xml_response = str_replace("<profile-info>", "<profile_info>", $xml_response);
        $xml_response = str_replace("</profile-info>", "</profile_info>", $xml_response);
        $xml_response = str_replace("&lt;", "", $xml_response);
        $xml_response = str_replace("&gt;", "", $xml_response);

        // dd($xml_response);

        return $xml_response;
    }

    private function extractLanIp(array $row): string
    {
        if (!empty($row['call-id'])) {
            $call_id_array = explode('@', $row['call-id']);
            if (isset($call_id_array[1])) {
                $agent = $row['agent'] ?? '';
                $lan_ip = $call_id_array[1];

                if (!empty($agent)) {
                    if (false !== stripos($agent, 'grandstream')) {
                        $lan_ip = str_ireplace(
                            ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'],
                            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
                            $lan_ip
                        );
                    } elseif (1 === preg_match('/\ACL750A/', $agent)) {
                        $lan_ip = preg_replace('/_/', '.', $lan_ip);
                    }
                }

                return $lan_ip;
            }
        }

        if (!empty($row['contact'])) {
            if (preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $row['contact'], $ip_match)) {
                return preg_replace('/_/', '.', $ip_match[0]);
            }
        }

        return '';
    }

    public function fetchRegistrationStatus(array $sipProfileNames): array
    {
        if (empty($sipProfileNames)) {
            return [];
        }

        $allRegistrations = $this->getRegistrations($sipProfileNames);
        $results = [];

        foreach ($allRegistrations as $reg) {
            $user = $reg['user'] ?? '';
            $profile = $reg['sip_profile_name'] ?? '';

            if (in_array($profile, $sipProfileNames)) {
                $results[$profile] = [
                    'registered' => true,
                    'ip_address' => $reg['network-ip'] ?? null,
                    'network_port' => $reg['network-port'] ?? null,
                    'user' => $user,
                    'agent' => $reg['agent'] ?? null,
                    'timestamp' => now(),
                    'status' => $reg['status'] ?? null,
                    'lan_ip' => $reg['lan-ip'] ?? null,
                    'ping_status' => $reg['ping-status'] ?? null,
                    'ping_time' => $reg['ping-time'] ?? null
                ];
            }
        }

        return $results;
    }


    public function executeUnregisterAction(SipRegistrationCollection $registrations): array
    {
        $responses = [];

        foreach ($registrations as $registration) {

            $command = "sofia profile {$registration['profile']} flush_inbound_reg {$registration['user']} reboot";

            $response = $this->useMockResponses
                ? $this->getMockActionResponse('unregister', $registration['user'])
                : FreeSwitch::execute('api', $command);


            $responses[$registration['user']] = [
                'command' => $command,
                'response' => $response,
                'success' => $response !== '-ERR no reply' && !empty($response)
            ];
        }

        return [
            'success' => !empty($responses),
            'responses' => $responses
        ];
    }


    public function executeProvisionAction(SipRegistrationCollection $registrations): array
    {
        $responses = [];

        foreach ($registrations as $registration) {

            $command = "lua app.lua event_notify {$registration['sip_profile_name']} check_sync {$registration['user']} {$registration['agent']} {$registration['host']}";

            $response = $this->useMockResponses
                ? $this->getMockActionResponse('provision', $registration['user'])
                : $this->freeSwitchService->execute('api', $command);

            $responses[$registration['user']] = [
                'command' => $command,
                'response' => $response,
                'success' => $response !== '-ERR no reply' && !empty($response)
            ];
        }

        return [
            'success' => !empty($responses),
            'responses' => $responses
        ];
    }

    public function executeRebootAction(SipRegistrationCollection  $registrations): array
    {
        $responses = [];

        foreach ($registrations as $registration) {

            $command = "lua app.lua event_notify {$registration['sip_profile_name']} reboot {$registration['user']} {$registration['agent']} {$registration['host']}";

            $response = $this->useMockResponses
                ? $this->getMockActionResponse('reboot', $registration['user'])
                : FreeSwitch::execute('api', $command);

            $responses[$registration['user']] = [
                'command' => $command,
                'response' => $response,
                'success' => $response !== '-ERR no reply' && !empty($response)
            ];
        }

        return [
            'success' => !empty($responses),
            'responses' => $responses
        ];
    }


    private function getMockActiveProfiles(): array
    {
        return ['internal', 'external'];
    }

    private function getMockRegistrationXml(string $profile): string
    {
        $mockResponses = [
            'internal' => <<<XML
<?xml version="1.0" encoding="ISO-8859-1"?>
<profile>
  <registrations>
    <registration>
        <call-id>G6sckKCTIN</call-id>
        <user>999@hornblower.tel</user>
        <contact>&quot;&quot; &lt;sip:999@184.147.21.228;transport=udp;fs_nat=yes;fs_path=sip%3A999%40184.147.21.228%3A5060%3Btransport%3Dudp&gt;</contact>
        <agent>Linphone-Desktop/5.0.17 (andres.okay.com.mx) mageia/9 Qt/5.15.7 LinphoneSDK/5.2.70</agent>
        <status>Registered(UDP-NAT)(unknown) exp(2025-05-13 18:29:38) expsecs(3598)</status>
        <ping-status>Reachable</ping-status>
        <ping-time>0.00</ping-time>
        <host>chi-pbx-dev.hornblower.com</host>
        <network-ip>184.147.21.228</network-ip>
        <network-port>5060</network-port>
        <sip-auth-user>999</sip-auth-user>
        <sip-auth-realm>hornblower.tel</sip-auth-realm>
        <mwi-account>999@hornblower.tel</mwi-account>
    </registration>
  </registrations>
</profile>
XML,
            'external' => <<<XML
<profile>
  <registrations>
    <registration>
        <call-id>G6sckKCTIN</call-id>
        <user>999@hornblower.tel</user>
        <contact>&quot;&quot; sip:999@184.147.21.228;transport=udp;fs_nat=yes;fs_path=sip%3A999%40184.147.21.228%3A5060%3Btransport%3Dudp</contact>
        <agent>Linphone-Desktop/5.0.17 (andres.okay.com.mx) mageia/9 Qt/5.15.7 LinphoneSDK/5.2.70</agent>
        <status>Registered(UDP-NAT)(unknown) exp(2025-05-13 20:17:38) expsecs(2538)</status>
        <ping-status>Reachable</ping-status>
        <ping-time>0.00</ping-time>
        <host>chi-pbx-dev.hornblower.com</host>
        <network-ip>184.147.21.228</network-ip>
        <network-port>5060</network-port>
        <sip-auth-user>999</sip-auth-user>
        <sip-auth-realm>hornblower.tel</sip-auth-realm>
        <mwi-account>999@hornblower.tel</mwi-account>
    </registration>
  </registrations>
</profile>

XML
        ];

        return $mockResponses[$profile] ?? '<profile><registrations></registrations></profile>';
    }

    private function getMockActionResponse(string $action, string $user): string
    {
        $user = explode('@', $user)[0] ?? $user;

        $responses = [
            'unregister' => "+OK Unregistered $user",
            'provision' => "+OK Provision request sent to $user",
            'reboot' => "+OK Reboot request sent to $user"
        ];

        return $responses[$action] ?? '-ERR no reply';
    }
}
