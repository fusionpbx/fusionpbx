<?php
/*
 * FusionPBX API Cache Helper
 * Provides file-based caching for API responses
 */

class ApiCache {
    private $cache_dir;
    private $default_ttl = 3600; // 1 hour default

    // Endpoints that should never be cached (real-time data)
    const EXCLUDED_ENDPOINTS = [
        'active-calls',
        'registrations',
        'active-conferences'
    ];

    public function __construct() {
        $this->cache_dir = '/tmp/fusionpbx_api_cache';
        $this->ensure_cache_dir();
    }

    /**
     * Ensure cache directory exists with proper permissions
     */
    private function ensure_cache_dir() {
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }

    /**
     * Get cached value by key
     *
     * @param string $key Cache key
     * @return mixed|null Cached value or null if not found/expired
     */
    public function cache_get($key) {
        $cache_file = $this->get_cache_file($key);

        if (!file_exists($cache_file)) {
            return null;
        }

        $cache_data = @file_get_contents($cache_file);
        if ($cache_data === false) {
            return null;
        }

        $data = json_decode($cache_data, true);
        if ($data === null) {
            // Invalid cache data, delete it
            @unlink($cache_file);
            return null;
        }

        // Check if expired
        if (isset($data['expires']) && $data['expires'] < time()) {
            @unlink($cache_file);
            return null;
        }

        return $data['value'] ?? null;
    }

    /**
     * Set cache value with TTL
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds (default: 3600)
     * @return bool Success status
     */
    public function cache_set($key, $value, $ttl = null) {
        if ($ttl === null) {
            $ttl = $this->default_ttl;
        }

        $cache_file = $this->get_cache_file($key);
        $cache_data = [
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time()
        ];

        $serialized = json_encode($cache_data);
        $result = @file_put_contents($cache_file, $serialized, LOCK_EX);

        return $result !== false;
    }

    /**
     * Delete cache entry by key
     *
     * @param string $key Cache key
     * @return bool Success status
     */
    public function cache_delete($key) {
        $cache_file = $this->get_cache_file($key);

        if (file_exists($cache_file)) {
            return @unlink($cache_file);
        }

        return true;
    }

    /**
     * Clear cache entries matching a pattern
     *
     * @param string $pattern Pattern to match (e.g., 'extensions_*')
     * @return int Number of files deleted
     */
    public function cache_clear_pattern($pattern) {
        $deleted = 0;
        $pattern_regex = '/^' . str_replace(['*', '?'], ['.*', '.'], preg_quote($pattern, '/')) . '$/';

        $files = @scandir($this->cache_dir);
        if ($files === false) {
            return 0;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            if (preg_match($pattern_regex, $file)) {
                if (@unlink($this->cache_dir . '/' . $file)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    /**
     * Clear all expired cache entries
     *
     * @return int Number of files deleted
     */
    public function cache_clear_expired() {
        $deleted = 0;
        $files = @scandir($this->cache_dir);

        if ($files === false) {
            return 0;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $cache_file = $this->cache_dir . '/' . $file;
            $cache_data = @file_get_contents($cache_file);

            if ($cache_data === false) {
                continue;
            }

            $data = json_decode($cache_data, true);
            if ($data !== null && isset($data['expires']) && $data['expires'] < time()) {
                if (@unlink($cache_file)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    /**
     * Generate cache key from endpoint and parameters
     *
     * @param string $endpoint API endpoint name
     * @param array $params Query parameters
     * @return string Cache key
     */
    public function get_cache_key($endpoint, $params = []) {
        // Sort params for consistent keys
        ksort($params);

        // Remove sensitive/variable parameters
        unset($params['api_key']);
        unset($params['timestamp']);
        unset($params['signature']);

        $key_parts = [$endpoint];

        foreach ($params as $name => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $key_parts[] = $name . '=' . $value;
        }

        $key_string = implode('_', $key_parts);
        return md5($key_string);
    }

    /**
     * Check if an endpoint should be cached
     *
     * @param string $endpoint API endpoint name
     * @return bool True if cacheable
     */
    public function is_cacheable($endpoint) {
        // Check against excluded endpoints
        foreach (self::EXCLUDED_ENDPOINTS as $excluded) {
            if (stripos($endpoint, $excluded) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get full path to cache file for a given key
     *
     * @param string $key Cache key
     * @return string Full file path
     */
    private function get_cache_file($key) {
        // Sanitize key to prevent directory traversal
        $safe_key = preg_replace('/[^a-zA-Z0-9_-]/', '', $key);
        return $this->cache_dir . '/' . $safe_key . '.cache';
    }

    /**
     * Get cache statistics
     *
     * @return array Statistics about cache usage
     */
    public function get_stats() {
        $stats = [
            'total_files' => 0,
            'total_size' => 0,
            'expired_files' => 0,
            'valid_files' => 0
        ];

        $files = @scandir($this->cache_dir);
        if ($files === false) {
            return $stats;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $cache_file = $this->cache_dir . '/' . $file;
            $stats['total_files']++;
            $stats['total_size'] += @filesize($cache_file);

            $cache_data = @file_get_contents($cache_file);
            if ($cache_data !== false) {
                $data = json_decode($cache_data, true);
                if ($data !== null && isset($data['expires'])) {
                    if ($data['expires'] < time()) {
                        $stats['expired_files']++;
                    } else {
                        $stats['valid_files']++;
                    }
                }
            }
        }

        return $stats;
    }
}

// Global helper functions for easy access

/**
 * Get global cache instance
 *
 * @return ApiCache
 */
function get_api_cache() {
    static $cache = null;
    if ($cache === null) {
        $cache = new ApiCache();
    }
    return $cache;
}

/**
 * Get cached value
 *
 * @param string $key Cache key
 * @return mixed|null
 */
function cache_get($key) {
    return get_api_cache()->cache_get($key);
}

/**
 * Set cache value
 *
 * @param string $key Cache key
 * @param mixed $value Value to cache
 * @param int $ttl Time to live in seconds
 * @return bool
 */
function cache_set($key, $value, $ttl = 3600) {
    return get_api_cache()->cache_set($key, $value, $ttl);
}

/**
 * Delete cache entry
 *
 * @param string $key Cache key
 * @return bool
 */
function cache_delete($key) {
    return get_api_cache()->cache_delete($key);
}

/**
 * Clear cache by pattern
 *
 * @param string $pattern Pattern to match
 * @return int Number of files deleted
 */
function cache_clear_pattern($pattern) {
    return get_api_cache()->cache_clear_pattern($pattern);
}

/**
 * Generate cache key from endpoint and params
 *
 * @param string $endpoint API endpoint
 * @param array $params Query parameters
 * @return string
 */
function get_cache_key($endpoint, $params = []) {
    return get_api_cache()->get_cache_key($endpoint, $params);
}

/**
 * Check if endpoint is cacheable
 *
 * @param string $endpoint API endpoint
 * @return bool
 */
function is_cacheable($endpoint) {
    return get_api_cache()->is_cacheable($endpoint);
}
