<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$registrations = [];

if (!class_exists('event_socket')) {
    api_error('ESL_NOT_AVAILABLE', 'Event Socket Library not available', null, 503);
}

$esl = event_socket::create();
if (!$esl || !$esl->is_connected()) {
    api_error('ESL_CONNECTION_FAILED', 'Failed to connect to FreeSWITCH', null, 503);
}

if ($esl->is_connected()) {
    // Get all SIP profiles
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
                    // Filter by domain
                    if (strpos($line, '@' . $domain_name) !== false) {
                        $parts = preg_split('/\s+/', trim($line));
                        if (count($parts) >= 4) {
                            // Extract user from user@domain format
                            $user_parts = explode('@', $parts[0]);

                            $registrations[] = [
                                'user' => $user_parts[0] ?? '',
                                'domain' => $user_parts[1] ?? '',
                                'contact' => $parts[1] ?? '',
                                'agent' => $parts[2] ?? '',
                                'status' => $parts[3] ?? '',
                                'profile' => $profile
                            ];
                        }
                    }
                }
            }
        }
    }
}

api_success($registrations);
