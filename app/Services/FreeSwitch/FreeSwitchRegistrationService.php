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

            if (empty($xml_response) || $xml_response == "Invalid Profile!") {
                continue;
            }

            $xml_response = $this->normalizeXmlResponse($xml_response);

            try {
                $xml = new SimpleXMLElement($xml_response);
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


    private function normalizeXmlResponse(string $xml_response): string
    {
        $xml_response = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]|\xEF\xBB\xBF/', '', $xml_response);

        $xml_response = str_replace("<profile-info>", "<profile>", $xml_response);
        $xml_response = str_replace("</profile-info>", "</profile>", $xml_response);

        $xml_response = str_replace("&", "&amp;", $xml_response);

        $xml_response = str_replace("&amp;lt;", "&lt;", $xml_response);
        $xml_response = str_replace("&amp;gt;", "&gt;", $xml_response);
        $xml_response = str_replace("&amp;amp;", "&amp;", $xml_response);

        $xml_response = str_replace("&lt;", "<", $xml_response);
        $xml_response = str_replace("&gt;", ">", $xml_response);

        $xml_response = preg_replace_callback('/<contact>(.*?)<\/contact>/', function ($matches) {
            $content = $matches[1];
            $content = preg_replace('/"([^"]*)"(\s+)<sip:/', '&quot;$1&quot;$2&lt;sip:', $content);
            $content = str_replace("></", ">&lt;/", $content);
            return "<contact>" . $content . "</contact>";
        }, $xml_response);

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
<profile>
  <name>internal</name>
  <domain-name>example.com</domain-name>
  <registrations>   
    <registration>
      <call-id>4a95c9d3-57c5dcaa@192.168.1.100</call-id>
      <user>1000@example.com</user>
      <contact>"1000" <sip:1000@192.168.1.100:5060;rinstance=9dcea4fc3e5d9e33></contact>
      <agent>Grandstream GXP1620 1.0.4.55</agent>
      <status>Registered(UDP)(unknown) exp(2021-05-05 12:28:41) rx(31) tx(0)</status>
      <ping-status>OPTIONS keepalive status: OK</ping-status>
      <ping-time>250</ping-time>
      <host>192.168.1.1</host>
      <network-ip>192.168.1.100</network-ip>
      <network-port>5060</network-port>
      <sip-auth-user>1000</sip-auth-user>
      <sip-auth-realm>example.com</sip-auth-realm>
      <mwi-account>1000@example.com</mwi-account>
    </registration>
    <registration>
      <call-id>2b84f5c7-a56b4dce@192.168.1.101</call-id>
      <user>2000@example.com</user>
      <contact>"2000" <sip:2000@192.168.1.101:5060;rinstance=7ea3b5fc1e4d8e22></contact>
      <agent>Yealink SIP-T21P 52.84.0.20</agent>
      <status>Registered(UDP)(unknown) exp(2021-05-05 12:30:15) rx(15) tx(0)</status>
      <ping-status>OPTIONS keepalive status: OK</ping-status>
      <ping-time>150</ping-time>
      <host>192.168.1.1</host>
      <network-ip>192.168.1.101</network-ip>
      <network-port>5060</network-port>
      <sip-auth-user>2000</sip-auth-user>
      <sip-auth-realm>example.com</sip-auth-realm>
      <mwi-account>2000@example.com</mwi-account>
    </registration>
  </registrations>
</profile>
XML,
            'external' => <<<XML
<profile>
  <name>external</name>
  <domain-name>example.com</domain-name>
  <registrations>
    <registration>
      <call-id>6c42a1e5-89fb3ec2@203.0.113.10</call-id>
      <user>3000@example.com</user>
      <contact>"3000" <sip:3000@203.0.113.10:5060;rinstance=1ac2e3fc4d5b6e77></contact>
      <agent>X-Lite release 5.5.0 stamp 97576</agent>
      <status>Registered(UDP)(unknown) exp(2021-05-05 12:25:10) rx(42) tx(0)</status>
      <ping-status>OPTIONS keepalive status: OK</ping-status>
      <ping-time>320</ping-time>
      <host>203.0.113.1</host>
      <network-ip>203.0.113.10</network-ip>
      <network-port>5060</network-port>
      <sip-auth-user>3000</sip-auth-user>
      <sip-auth-realm>example.com</sip-auth-realm>
      <mwi-account>3000@example.com</mwi-account>
    </registration>
    <registration>
      <call-id>9d8e7c6b-5a4f3e2d@10.0.0.5</call-id>
      <user>CL750A4000@example.com</user>
      <contact>"4000" <sip:4000@10.0.0.5:5060;rinstance=2bd4e6fc8a9c1d33></contact>
      <agent>CL750A/2.3.0.0</agent>
      <status>Registered(UDP)(unknown) exp(2021-05-05 12:35:22) rx(8) tx(0)</status>
      <ping-status>OPTIONS keepalive status: OK</ping-status>
      <ping-time>175</ping-time>
      <host>10.0.0.1</host>
      <network-ip>10.0.0.5</network-ip>
      <network-port>5060</network-port>
      <sip-auth-user>4000</sip-auth-user>
      <sip-auth-realm>example.com</sip-auth-realm>
      <mwi-account>4000@example.com</mwi-account>
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
