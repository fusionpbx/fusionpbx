<?php

/**
 * Single Point of Entry
 *
 * Routes all HTTP requests while maintaining backward compatibility.
 * Designed for future HMVC architecture evolution.
 *
 * @version 1.0
 * @security Enhanced with input validation, CSRF hooks, and security headers
 */

/**
 * Single Point of Entry Path
 */
define('ENTRY_PATH', '/public/index.php');

/**
 * Maximum request path length (prevents path-based attacks)
 */
define('MAX_PATH_LENGTH', 255);

/**
 * Allowed HTTP methods for routing
 */
define('ALLOWED_METHODS', ['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS']);

/**
 * Initialize FusionPBX core
 * Loads: autoloader, database, session, settings, security headers
 */
require_once dirname(__DIR__, 1) . '/resources/require.php';

/**
 * Router class
 *
 * @property $name Alias of app_name
 */
class router {

	/**
	 * Get the clean request path
	 *
	 * @return string The normalized request path
	 */
	public function get_request_path(): string {
		$request_uri = $_SERVER['REQUEST_URI'] ?? '/';

		// Decode URL-encoded characters for security analysis
		$path = parse_url($request_uri, PHP_URL_PATH);
		if ($path === false) {
			$path = '/';
		}

		// Request Path Length Limit - prevent path-based attacks
		if (strlen($path) > MAX_PATH_LENGTH) {
			error_log('[SECURITY] Request path exceeds maximum length: ' . strlen($path));
			return '/';
		}

		// Remove script name prefix if present
		$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
		if (strpos($path, $script_name) === 0 && $script_name !== '/') {
			$path = substr($path, strlen($script_name));
		}

		// Normalize path
		$path = trim($path, '/') === '' ? '/' : '/' . trim($path, '/');

		return $path;
	}

	/**
	 * Validate HTTP method
	 *
	 * @return bool True if method is allowed
	 */
	private function validate_method(): bool {
		$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
		if (!in_array($method, ALLOWED_METHODS)) {
			error_log("[SECURITY] Invalid HTTP method attempted: $method");
			return false;
		}
		return true;
	}

	/**
	 * Sanitize and validate path components
	 *
	 * @param string $input The input string to validate
	 * @return bool True if safe, false otherwise
	 */
	private function is_safe_component(string $input): bool {
		// Check for path traversal attempts
		if (strpos($input, '..') !== false) {
			return false;
		}

		// Check for null bytes
		if (strpos($input, "\0") !== false) {
			return false;
		}
		return true;
	}

	/**
	 * Route resolver for app, core, or modules requests
	 *
	 * @param string $request_path The request path (e.g., "/core/domains" or "/core/domains/domains.php")
	 * @return array|null Route result or null if not found
	 */
	private function resolve_route(string $request_path): ?array {
		// Decode URL-encoded characters before validation
		$decoded_path = rawurldecode($request_path);

		// Remove trailing slash (except for root) to handle URLs like /core/dashboard/
		$decoded_path = rtrim($decoded_path, '/');

		// Set the script name
		$script_name = $decoded_path;

		// Extract path details
		$path_array = explode('/', $decoded_path);
		$path_count = count($path_array);
		$prefix_name = $path_array[1];
		$app_name = $path_array[2];
		$file_name = array_pop($path_array) ?? 'index.php';

		// Initialize the variable
		$target_file = null;
		$file_path = null;

		// Prevent using an unsafe script_name (after URL decoding)
		if (!$this->is_safe_component($script_name)) {
			error_log("[SECURITY] Unsafe path detected: " . htmlspecialchars($script_name));
			return null;
		}

		// Validate file_name if present
		if ($file_name !== null && !$this->is_safe_component($file_name)) {
			error_log("[SECURITY] Unsafe file_name detected: " . htmlspecialchars($file_name));
			return null;
		}

		// Calculate singular form for app_name
		$app_name_singular = database::singular($app_name ?? '');

		// Determine the target file based on routing rules
		if (empty($app_name)) {
		    // Fallback for missing app_name
		    $file_path = $script_name;
		}
		if (!empty($file_name) && $app_name == 'provision') {
			// App name equals file name (e.g., /app/extensions/extensions -> extensions_list.php)
			$action_name = 'list';
			$file_path = $prefix_name . '/' . $app_name . '/' . $app_name . '.php';
			view_array($path_array);
		}
		elseif (!empty($file_name) && $app_name == $file_name) {
			// App name equals file name (e.g., /app/extensions/extensions -> extensions_list.php)
			$action_name = 'list';
			$file_path = $prefix_name . '/' . $app_name . '/' . $app_name . '.php';
		}
		elseif ($path_count <= 3) {
			// Set the default index in 2 directories - core/dashboard and others
			$action_name = 'list';
			$file_path = $prefix_name . '/' . $app_name . '/index.php';
		}
		elseif (!empty($file_name) && ($file_name == 'edit' || $file_name == $app_name_singular . '_edit')) {
			// Edit action (e.g., /app/extensions/edit -> extension_edit.php
			$action_name = 'edit';
			$file_path = $prefix_name . '/' . $app_name . '/' . $app_name_singular . '_edit.php';
		}
		elseif (!empty($file_name) && ($file_name == 'delete' || $file_name == $app_name_singular . '_delete')) {
			// Delete action (e.g., /app/extensions/extension_delete -> extension_delete.php)
			$action_name = 'delete';
			$file_path = $prefix_name . '/' . $app_name . '/' . $app_name_singular . '_delete.php';
		}
		elseif (!empty($file_name) && $file_name == 'index.php') {
			// Index file
			$action_name = 'index';
			$file_path = $prefix_name . '/' . $app_name . '/' . 'index.php';
		}
		else {
			// Other files (images, videos, css, etc.)
			$file_path = $script_name;
		}

		// Verify and set the target file with strict path validation
		if (!empty($file_path)) {
			// Double-check for path traversal
			if (strpos($file_path, '..') === false) {
				$full_path = PROJECT_ROOT . '/' . $file_path;
				if (file_exists($full_path) && is_file($full_path)) {
					$resolved_path = realpath($full_path);
					// Ensure the resolved path is within the app directory
					if ($resolved_path !== null && strpos($resolved_path, realpath(PROJECT_ROOT)) === 0) {
						$target_file = $resolved_path;
					}
				}
			}
		}

		// No target_file found - return null for 404
		if ($target_file === null || !file_exists($target_file)) {
			http_response_code(405);
			return null;
		}

		// Return an array of values
		return [
			'target' => $target_file,
			'app_name' => $app_name,
			'action' => $action_name,
			'file' => $file_path ?? 'index.php',
		];
	}

	/**
	 * Main router - resolves any route
	 *
	 * @param string $request_path The request path
	 * @return array|null Route result or null (triggers 404 or default)
	 */
	public function route(string $request_path): ?array {
		// Validate HTTP method first
		if (!$this->validate_method()) {
			http_response_code(405);
			echo '405 - Method Not Allowed';
			exit;
		}

		// Check for routes starting with /app/, /core/, or /modules/
		try {
			return $this->resolve_route($request_path);
		} catch (\Exception $e) {
			error_log("Routing error: " . $e->getMessage());
			return null;
		}

		return null;
	}

	/**
	 * Follow the resolved route
	 *
	 * @param array $route The route array from route()
	 */
	public function follow_route(array $route): void {
		global $config, $database, $settings;

		// Set the target file
		$target_file = $route['target'];

		// Send 404 not found if the file doesn't exist
		if (!file_exists($target_file)) {
			http_response_code(404);
			echo '404 - Not Found ' . __line__ . ' ' . $target_file;
			exit;
		}

		// Set the file extension
		$file_ext = strtolower(pathinfo($target_file, PATHINFO_EXTENSION) ?? 'php');

		// Define MIME types for common files
		$mime_types = [
			'png'   => 'image/png',
			'jpg'   => 'image/jpeg',
			'jpeg'  => 'image/jpeg',
			'gif'   => 'image/gif',
			'webp'  => 'image/webp',
			'svg'   => 'image/svg+xml',
			'ico'   => 'image/png',
			'js'    => 'application/javascript',
			'css'   => 'text/css',
			'json'  => 'application/json',
			'txt'   => 'text/plain',
			'csv'   => 'text/csv',
			'pdf'   => 'application/pdf',
			'xml'   => 'application/xml',
			'html'  => 'text/html',
			'map'   => 'application/json',
			'txt'   => 'text/plain',
			'woff2' => 'font/woff2',
			'ttf'   => 'font/sfnt',
			'htm'   => 'text/html',
			'mp4'   => 'video/mp4',
			'webm'  => 'video/webm',
			'ogg'   => 'video/ogg',
		];

		// Detect the MIME type using finfo
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$detected_mime = $finfo->file($target_file);

		// Override detected mime type for javascript
		if ($detected_mime == 'text/plain' && $file_ext == 'js') {
			$detected_mime = 'application/javascript';
		}
		if ($detected_mime == 'text/html' && $file_ext == 'js') {
			$detected_mime = 'application/javascript';
		}
		if ($detected_mime == 'text/x-c++' && $file_ext == 'js') {
			$detected_mime = 'application/javascript';
		}

		// Validate static asset MIME types (PHP files are processed, not served)
		if ($file_ext !== 'php' && !isset($mime_types[$file_ext])) {
			error_log("[SECURITY] Unsupported file type attempted: file_ext $file_ext ($detected_mime)");
			http_response_code(403);
			echo '403 - Forbidden';
			exit;
		}

        // Additional MIME type verification for static assets
        // if ($file_ext !== 'php' && $detected_mime !== $mime_types[$file_ext]) {
        // 	error_log("[SECURITY] MIME type mismatch: target_file $target_file file_ext $file_ext expected {$mime_types[$file_ext]}, got $detected_mime");
        // 	http_response_code(404);
        // 	echo '404 - Not Found ' . __line__ . ' ' . $detected_mime;
        // 	exit;
        // }

		// Set SCRIPT_FILENAME for proper app context detection
		// This allows the text class to auto-detect the app path
		if (!isset($_SERVER['SCRIPT_FILENAME'])) {
			$_SERVER['SCRIPT_FILENAME'] = $target_file;
		}

		// Set security headers
		//$this->set_security_headers($file_ext);

		// Set the headers and stream the file (for static assets)
		if ($file_ext !== 'php') {
			header("Content-Type: {$mime_types[$file_ext]}");
			header('Content-Length: ' . filesize($target_file));

			// Cache headers for static assets
			header('Cache-Control: public, max-age=31536000, immutable');
			header('Expires: ' . gmdate('D, d M Y H:i:s', strtotime('+1 year')) . ' GMT');

			readfile($target_file);
			exit;
		}

		// Load the PHP target file
		if ($file_ext === 'php') {
			// Include the target file
			include $target_file;
		}
	}

	/**
	 * Set security headers
	 *
	 * @param string $file_ext The file extension
	 */
	private function set_security_headers(string $file_ext): void {
		// X-Frame-Options: Prevent clickjacking
		header('X-Frame-Options: SAMEORIGIN');

		// X-Content-Type-Options: Prevent MIME type sniffing
		header('X-Content-Type-Options: nosniff');

		// X-XSS-Protection: Legacy XSS filter (deprecated but still useful for older browsers)
		header('X-XSS-Protection: 1; mode=block');

		// Referrer-Policy: Control referrer information
		header('Referrer-Policy: strict-origin-when-cross-origin');

		// Permissions-Policy (formerly Feature-Policy)
		header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

		/**
		 * Content Security Policy
		 *
		 * Note: This is a restrictive default. Adjust based on your application's needs.
		 * Common adjustments:
		 * - Add 'unsafe-inline' for inline scripts/styles (not recommended)
		 * - Add external domains for CDNs, analytics, etc.
		 * - Add nonce or hash values for specific inline scripts
		 */
		$csp = [
			'default-src' => "'self'",
			'script-src' => "'self' 'unsafe-inline'",
			'style-src' => "'self' 'unsafe-inline'",
			'img-src' => "'self' data: https:",
			'font-src' => "'self' data:",
			'connect-src' => "'self'",
			'frame-src' => "'self'",
			'object-src' => "'none'",
			'base-uri' => "'self'",
			'form-action' => "'self'",
		];

		// Build CSP header string
		$csp_header = implode('; ', array_map(function ($key, $value) {
			return "$key $value";
		}, array_keys($csp), $csp));

		header("Content-Security-Policy: $csp_header");

		// Cache-Control for dynamic content
		if ($file_ext === 'php') {
			header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
			header('Pragma: no-cache');
		}
	}

	/**
	 * Handle 404 Not Found
	 */
	public function handle_404(): void {
		http_response_code(404);
		header('Content-Type: text/html; charset=utf-8');

		echo '<!DOCTYPE html>
		<html lang="en">

		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>404 - Not Found | FusionPBX</title>
			<style>
				* {
					margin: 0;
					padding: 0;
					box-sizing: border-box;
				}

				body {
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
					background-color: #f5f5f5;
					color: #333;
					display: flex;
					align-items: center;
					justify-content: center;
					min-height: 100vh;
					padding: 20px;
				}

				.error-container {
					text-align: center;
					padding: 40px;
					max-width: 500px;
				}

				.error-code {
					font-size: 120px;
					font-weight: 700;
					color: #e74c3c;
					line-height: 1;
					margin: 0;
				}

				.error-title {
					font-size: 24px;
					font-weight: 300;
					color: #666;
					margin: 20px 0 10px;
				}

				.error-message {
					color: #999;
					font-size: 14px;
					margin-bottom: 30px;
				}

				.home-link {
					display: inline-block;
					padding: 12px 24px;
					background-color: #3498db;
					color: white;
					text-decoration: none;
					border-radius: 4px;
					transition: background-color 0.3s;
				}

				.home-link:hover {
					background-color: #2980b9;
				}
			</style>
		</head>

		<body>
			<div class="error-container">
				<h1 class="error-code">404</h1>
				<h2 class="error-title">Not Found</h2>
				<p class="error-message">The requested resource was not found on this server.</p>
				<a href="/" class="home-link">Return to Home</a>
			</div>
		</body>

		</html>';
	}

	/**
	 * Handle health check endpoint
	 *
	 * @return void
	 */
	public function handle_health_check(): string {
		http_response_code(200);
		header('Content-Type: application/json');

		$health_data = [
			'status' => 'healthy',
			'timestamp' => time(),
			//'uptime' => uptime() ?? 0,
			//'memory_usage' => memory_get_usage(true),
			//'php_version' => PHP_VERSION,
		];

		// Check database connectivity if possible
		if (isset($database) && $database instanceof database) {
			try {
				$health_data['database'] = 'connected';
			} catch (\Exception $e) {
				$health_data['database'] = 'disconnected';
				$health_data['status'] = 'degraded';
			}
		}

		return json_encode($health_data, JSON_PRETTY_PRINT);
	}
}

/**
 * Main routing logic with error handling
 */
try {
	// Create router instance
	$router = new router();

	// Get the clean request path
	$request_path = $router->get_request_path();

	// Health check endpoint (for load balancers and monitoring)
	if ($request_path === '/health' || $request_path === '/healthz') {
		echo $router->handle_health_check();
	} else {
		// Attempt to resolve the route
		$resolved_route = $router->route($request_path);
		if ($resolved_route !== null) {
			// Route found - follow
			$router->follow_route($resolved_route);
		} elseif ($request_path === '/' || $request_path === '/public' || $request_path === '/public/') {
			// Root path - load main index
			include PROJECT_ROOT . '/index.php';
		} else {
			// No route found - handle 404
			$router->handle_404();
		}
	}
} catch (\Throwable $e) {
	// Catch-all error handler
	error_log("Routing error: " . $e->getMessage());
	error_log("Stack trace: " . $e->getTraceAsString());

	// In production, show generic error; in debug mode, show details
	if (defined('DEBUG') && DEBUG) {
		http_response_code(500);
		echo '<pre>';
		echo 'Fatal Error: ' . htmlspecialchars($e->getMessage()) . "\n";
		echo 'File: ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . "\n\n";
		echo 'Stack Trace:' . "\n";
		echo htmlspecialchars($e->getTraceAsString());
		echo '</pre>';
	} else {
		$router->handle_404();
	}
}
