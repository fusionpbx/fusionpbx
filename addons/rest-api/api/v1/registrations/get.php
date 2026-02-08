<?php
/**
 * FusionPBX REST API - Get Registration Status
 * GET /api/v1/registrations/get.php?extension=1001
 *
 * Returns registration status for a specific extension
 */

require_once __DIR__ . '/../base.php';

api_require_method('GET');

// Get extension parameter
$extension = $_GET['extension'] ?? '';
if (empty($extension)) {
    api_error('VALIDATION_ERROR', 'Extension parameter is required', 'extension', 400);
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

$registration = null;

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
                            'registered' => true
                        ];

                        break 2; // Exit both loops
                    }
                }
            }
        }
    }
}

if ($registration === null) {
    api_not_found('Registration for extension ' . $extension);
}

api_success($registration);
