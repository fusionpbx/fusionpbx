<?php
// Load FusionPBX bootstrap even when the API directory is symlinked
if (!class_exists('database')) {
    $bootstrap_path = null;
    $search_dir = __DIR__;
    for ($i = 0; $i < 6; $i++) {
        $candidate = $search_dir . '/resources/require.php';
        if (file_exists($candidate)) {
            $bootstrap_path = $candidate;
            break;
        }
        $parent = dirname($search_dir);
        if ($parent === $search_dir) {
            break; // reached filesystem root
        }
        $search_dir = $parent;
    }

    if ($bootstrap_path === null) {
        http_response_code(500);
        echo 'FusionPBX bootstrap (resources/require.php) not found.';
        exit;
    }

    require_once $bootstrap_path;
}

require_once __DIR__ . '/response.php';

function get_api_secret_key() {
    $settings = new settings(['database' => new database]);
    return $settings->get('api', 'secret_key', '');
}

function validate_api_key() {
    global $domain_uuid, $domain_name;
    static $api_authenticated = false;

    // Already authenticated - skip duplicate calls
    if ($api_authenticated) {
        return;
    }

    // Reset domain vars set by FusionPBX bootstrap (based on HTTP host)
    // API must authenticate via X-API-Key, not rely on host-based resolution
    $domain_uuid = null;
    $domain_name = null;

    $provided_key = $_SERVER['HTTP_X_API_KEY'] ?? '';
    $provided_domain = $_SERVER['HTTP_X_DOMAIN'] ?? '';

    if (empty($provided_key)) {
        api_error('UNAUTHORIZED', 'API key is required', null, 401);
    }

    $api_config = require __DIR__ . '/config.php';
    $auth_mode = $api_config['security']['auth_mode'] ?? 'global_key';

    $authenticated = false;

    // --- Per-key authentication (v_api_keys table) ---
    if ($auth_mode === 'per_key' || $auth_mode === 'both') {
        $database = new database;
        $sql = "SELECT api_key_uuid, domain_uuid FROM v_api_keys
                WHERE api_key = :api_key AND api_key_enabled = 'true'";
        $key_record = $database->select($sql, ['api_key' => $provided_key], 'row');

        if (!empty($key_record)) {
            $key_domain_uuid = $key_record['domain_uuid'];

            // If X-Domain header provided, verify it matches the key's domain
            if (!empty($provided_domain)) {
                if (is_uuid($provided_domain)) {
                    if ($provided_domain !== $key_domain_uuid) {
                        api_error('DOMAIN_MISMATCH', 'API key does not have access to this domain', null, 403);
                    }
                } else {
                    $check_sql = "SELECT domain_uuid FROM v_domains WHERE domain_name = :domain_name AND domain_enabled = 'true'";
                    $resolved_uuid = $database->select($check_sql, ['domain_name' => $provided_domain], 'column');
                    if (empty($resolved_uuid) || $resolved_uuid !== $key_domain_uuid) {
                        api_error('DOMAIN_MISMATCH', 'API key does not have access to this domain', null, 403);
                    }
                }
            }

            // Resolve domain_name from the key's domain_uuid, require domain_enabled
            $domain_uuid = $key_domain_uuid;
            $sql = "SELECT domain_name FROM v_domains WHERE domain_uuid = :domain_uuid AND domain_enabled = 'true'";
            $domain_name = $database->select($sql, ['domain_uuid' => $domain_uuid], 'column');

            if (empty($domain_name)) {
                api_error('INVALID_DOMAIN', 'Domain not found or disabled', null, 400);
            }

            $authenticated = true;
        }
    }

    // --- Global key authentication (fallback or primary) ---
    if (!$authenticated && ($auth_mode === 'global_key' || $auth_mode === 'both')) {
        $valid_key = get_api_secret_key();
        if (empty($valid_key)) {
            api_error('CONFIG_ERROR', 'API key not configured', null, 500);
        }

        if (!hash_equals($valid_key, $provided_key)) {
            api_error('UNAUTHORIZED', 'Invalid API key', null, 401);
        }

        if (empty($provided_domain)) {
            api_error('MISSING_DOMAIN', 'X-Domain header is required', null, 400);
        }

        $database = new database;
        if (is_uuid($provided_domain)) {
            $domain_uuid = $provided_domain;
            $sql = "SELECT domain_name FROM v_domains WHERE domain_uuid = :domain_uuid AND domain_enabled = 'true'";
            $domain_name = $database->select($sql, ['domain_uuid' => $domain_uuid], 'column');
        } else {
            $domain_name = $provided_domain;
            $sql = "SELECT domain_uuid FROM v_domains WHERE domain_name = :domain_name AND domain_enabled = 'true'";
            $domain_uuid = $database->select($sql, ['domain_name' => $domain_name], 'column');
        }

        if (empty($domain_uuid) || empty($domain_name)) {
            api_error('INVALID_DOMAIN', 'Domain not found or disabled', null, 400);
        }

        $authenticated = true;
    }

    if (!$authenticated) {
        api_error('UNAUTHORIZED', 'Invalid API key', null, 401);
    }

    $api_authenticated = true;
    $_SESSION['domain_uuid'] = $domain_uuid;
    $_SESSION['domain_name'] = $domain_name;
}
