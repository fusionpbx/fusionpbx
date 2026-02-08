<?php
/**
 * FusionPBX REST API - Check Registration Status
 * POST /api/v1/registrations/check.php
 *
 * Forces a registration check/refresh for a specific extension
 * and returns current status
 */

require_once __DIR__ . '/../base.php';

api_require_method('POST');

// Get request data
$data = get_request_data();
$extension = $data['extension'] ?? '';

if (empty($extension)) {
    api_error('VALIDATION_ERROR', 'Extension parameter is required', 'extension', 400);
}

// Validate extension format to prevent ESL injection
if (!preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $extension)) {
    api_error('VALIDATION_ERROR', 'Invalid extension format', 'extension', 400);
}

// Check if event_socket class is available
if (!class_exists('event_socket')) {
    api_error('SERVICE_UNAVAILABLE', 'Event Socket Library not available', null, 503);
}

// Create event socket connection
$esl = event_socket::create();
if (!$esl->is_connected()) {
    api_error('SERVICE_UNAVAILABLE', 'Cannot connect to FreeSWITCH Event Socket', null, 503);
}

// Force flush registration cache to get fresh data
$database = new database;
$sql = "SELECT sip_profile_name FROM v_sip_profiles WHERE sip_profile_enabled = 'true'";
$sip_profiles = $database->select($sql, null, 'all');

$registration = null;
$found_profile = null;

if (!empty($sip_profiles)) {
    foreach ($sip_profiles as $row) {
        $profile = $row['sip_profile_name'];

        // Flush registration cache for this profile
        event_socket::api("sofia profile " . $profile . " flush_inbound_reg " . $extension . "@" . $domain_name);

        // Small delay to allow cache flush
        usleep(100000); // 100ms

        // Get fresh registrations
        $response = event_socket::api("sofia status profile " . $profile . " reg");

        if (!empty($response)) {
            $lines = explode("\n", $response);
            foreach ($lines as $line) {
                $line = trim($line);

                // Skip empty lines and headers
                if (empty($line) || strpos($line, '=====') !== false || strpos($line, 'Registrations:') !== false) {
                    continue;
                }

                // Check if this line contains the extension we're looking for
                if (strpos($line, $extension . '@' . $domain_name) === 0) {
                    // Parse registration line
                    $parts = preg_split('/\s+/', $line);

                    if (count($parts) >= 4) {
                        // Extract user and domain
                        $user_domain = $parts[0];
                        $user_parts = explode('@', $user_domain);
                        $user = $user_parts[0] ?? '';
                        $sip_realm = $user_parts[1] ?? '';

                        $contact = $parts[1] ?? '';
                        $agent = $parts[2] ?? '';
                        $status = $parts[3] ?? '';

                        // Extract network info from contact
                        $network_ip = '';
                        $network_port = '';
                        if (preg_match('/@([^:;]+):(\d+)/', $contact, $matches)) {
                            $network_ip = $matches[1];
                            $network_port = $matches[2];
                        }

                        // Extract expiry from contact header if available
                        $expiry = '';
                        if (preg_match('/expires=(\d+)/', $contact, $matches)) {
                            $expiry = $matches[1];
                        }

                        // Extract ping time if available
                        $ping_time = '';
                        if (count($parts) > 4 && is_numeric($parts[count($parts) - 1])) {
                            $ping_time = $parts[count($parts) - 1];
                        }

                        $registration = [
                            'user' => $user,
                            'sip_realm' => $sip_realm,
                            'contact' => $contact,
                            'agent' => $agent,
                            'status' => $status,
                            'ping_time' => $ping_time,
                            'network_ip' => $network_ip,
                            'network_port' => $network_port,
                            'expiry' => $expiry,
                            'profile' => $profile,
                            'registered' => true,
                            'checked_at' => date('Y-m-d H:i:s')
                        ];

                        $found_profile = $profile;
                        break 2; // Exit both loops
                    }
                }
            }
        }
    }
}

// If not found, return unregistered status
if ($registration === null) {
    $registration = [
        'user' => $extension,
        'sip_realm' => $domain_name,
        'registered' => false,
        'status' => 'Not Registered',
        'checked_at' => date('Y-m-d H:i:s')
    ];
}

api_success($registration, 'Registration check completed');
