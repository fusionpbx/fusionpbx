<?php
/**
 * FusionPBX REST API - List Registrations
 * GET /api/v1/registrations/list.php
 *
 * Returns all active SIP registrations for the authenticated domain
 */

require_once __DIR__ . '/../base.php';

api_require_method('GET');

// Check if event_socket class is available
if (!class_exists('event_socket')) {
    api_error('SERVICE_UNAVAILABLE', 'Event Socket Library not available', null, 503);
}

$registrations = [];

// Create event socket connection
$esl = event_socket::create();
if (!$esl->is_connected()) {
    api_error('SERVICE_UNAVAILABLE', 'Cannot connect to FreeSWITCH Event Socket', null, 503);
}

// Get all enabled SIP profiles
$database = new database;
$sql = "SELECT sip_profile_name FROM v_sip_profiles WHERE sip_profile_enabled = 'true'";
$sip_profiles = $database->select($sql, null, 'all');

if (!empty($sip_profiles)) {
    foreach ($sip_profiles as $row) {
        $profile = $row['sip_profile_name'];

        // Get registrations for this profile
        $response = event_socket::api("sofia status profile " . $profile . " reg");

        if (!empty($response)) {
            $lines = explode("\n", $response);
            foreach ($lines as $line) {
                $line = trim($line);

                // Skip empty lines and headers
                if (empty($line) || strpos($line, '=====') !== false || strpos($line, 'Registrations:') !== false) {
                    continue;
                }

                // Filter by domain (sip_realm matches domain_name)
                if (strpos($line, '@' . $domain_name) !== false || strpos($line, $domain_name) !== false) {
                    // Parse registration line
                    // Format: user@domain contact agent status [ping_time] [network_ip:port]
                    $parts = preg_split('/\s+/', $line);

                    if (count($parts) >= 4) {
                        // Extract user and domain from user@domain format
                        $user_domain = $parts[0];
                        $user_parts = explode('@', $user_domain);
                        $user = $user_parts[0] ?? '';
                        $sip_realm = $user_parts[1] ?? '';

                        // Only include if sip_realm matches domain_name
                        if ($sip_realm !== $domain_name) {
                            continue;
                        }

                        $contact = $parts[1] ?? '';
                        $agent = $parts[2] ?? '';
                        $status = $parts[3] ?? '';

                        // Extract network info from contact if available
                        $network_ip = '';
                        $network_port = '';
                        if (preg_match('/@([^:;]+):(\d+)/', $contact, $matches)) {
                            $network_ip = $matches[1];
                            $network_port = $matches[2];
                        }

                        // Extract ping time if available (usually last part)
                        $ping_time = '';
                        if (count($parts) > 4 && is_numeric($parts[count($parts) - 1])) {
                            $ping_time = $parts[count($parts) - 1];
                        }

                        $registrations[] = [
                            'user' => $user,
                            'sip_realm' => $sip_realm,
                            'contact' => $contact,
                            'agent' => $agent,
                            'status' => $status,
                            'ping_time' => $ping_time,
                            'network_ip' => $network_ip,
                            'network_port' => $network_port,
                            'profile' => $profile
                        ];
                    }
                }
            }
        }
    }
}

api_success($registrations);
