<?php
/**
 * FusionPBX REST API Configuration
 * Central configuration for all API settings
 */

return [
    // API Version
    'version' => '1.0',

    // Rate Limiting
    'rate_limit' => [
        'enabled' => true,
        'requests_per_minute' => 60,
        'requests_per_hour' => 1000,
        'by_key' => true,  // Rate limit per API key
    ],

    // Pagination Defaults
    'pagination' => [
        'default_per_page' => 50,
        'max_per_page' => 100,
        'min_per_page' => 1,
    ],

    // Caching
    'cache' => [
        'enabled' => true,
        'ttl' => 300,  // 5 minutes default TTL
        'exclude_endpoints' => [
            'active-calls',
            'registrations',
            'active-conferences',
            'sip-status',
            'dashboard/stats',
            'dashboard/active-calls',
        ],
    ],

    // Database
    'database' => [
        'table_prefix' => 'v_',
    ],

    // Response Format
    'response' => [
        'include_timestamp' => true,
        'date_format' => 'Y-m-d H:i:s',
        'timezone' => 'UTC',
    ],

    // Security
    'security' => [
        'require_https' => false,  // Set to true in production
        'allowed_origins' => [],  // CORS origins - add specific domains for production
        'max_request_size' => 10485760,  // 10MB
        // Auth mode: 'global_key' (api/secret_key setting), 'per_key' (v_api_keys table), 'both' (per_key first, fallback to global)
        'auth_mode' => 'global_key',
        // IP whitelist: only these IPs can access the API (empty = allow all)
        'allowed_ips' => [],  // e.g. ['10.0.0.5', '192.168.1.100']
    ],

    // FreeSWITCH Integration
    'freeswitch' => [
        'auto_regenerate_xml' => true,
        'clear_cache_on_update' => true,
    ],

    // Logging
    'logging' => [
        'enabled' => true,
        'log_requests' => true,
        'log_errors' => true,
        'log_slow_requests' => true,
        'slow_request_threshold' => 1000,  // milliseconds
    ],

    // Endpoints Configuration
    'endpoints' => [
        // Tier 1 - Core Telephony
        'ring-groups' => ['enabled' => true, 'cache' => true],
        'ivr-menus' => ['enabled' => true, 'cache' => true],
        'call-flows' => ['enabled' => true, 'cache' => true],
        'conferences' => ['enabled' => true, 'cache' => true],
        'fax' => ['enabled' => true, 'cache' => false],

        // Tier 2 - Advanced Features
        'call-centers' => ['enabled' => true, 'cache' => true],
        'call-block' => ['enabled' => true, 'cache' => true],
        'call-recordings' => ['enabled' => true, 'cache' => false],
        'recordings' => ['enabled' => true, 'cache' => true],
        'music-on-hold' => ['enabled' => true, 'cache' => true],

        // Tier 3 - Configuration
        'access-controls' => ['enabled' => true, 'cache' => true],
        'sip-profiles' => ['enabled' => true, 'cache' => true],
        'number-translations' => ['enabled' => true, 'cache' => true],

        // Tier 4 - Real-time (no cache)
        'active-calls' => ['enabled' => true, 'cache' => false],
        'active-conferences' => ['enabled' => true, 'cache' => false],
        'registrations' => ['enabled' => true, 'cache' => false],
    ],
];
