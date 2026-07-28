<?php
/*
 * Canonicalization and baseline-lookup helpers for dialplan XML.
 *
 * Shared between:
 *   - app/dialplans/dialplans.php  (per-request UI compare)
 *   - app/dialplans/app_defaults.php (populate v_dialplans.dialplan_hash
 *     during install / `php core/upgrade/upgrade.php`)
 *
 * This file must be safe to include multiple times and from both the web
 * request path and the CLI upgrade path.
 */

if (!function_exists('dialplan_normalize_name')) {

	/**
	 * Normalize a dialplan name to a lowercase alphanumeric string suitable
	 * for use in a shipped baseline XML filename.
	 *
	 * @param string $name Dialplan name
	 *
	 * @return string Normalized name
	 */
	function dialplan_normalize_name(string $name): string {
		return preg_replace('/[^a-z0-9]/', '', strtolower($name));
	}

	/**
	 * Produce a canonical, whitespace-independent representation of a dialplan
	 * XML fragment so semantically equivalent documents compare equal.
	 *
	 * The FusionPBX save path normalizes the XML it writes back to v_dialplans
	 * in ways that differ cosmetically from the shipped baseline files:
	 *   - boolean attributes rendered as "1"/"" instead of "true"/"false"
	 *   - extension-level enabled attr may be omitted in DB XML while also
	 *     tracked separately by v_dialplans.dialplan_enabled
	 *   - wrapper metadata attrs (uuid/app_uuid/order/number/context/global)
	 *     emitted inconsistently on the <extension> element
	 *   - empty-string attributes (data="", field="", expression="")
	 *   - XML comments stripped
	 *   - <action enabled="false"/> entries stripped (they are inert)
	 *   - extension `enabled` omitted in canonical output (enabled state is
	 *     compared separately via dialplan_enabled vs dialplan_enabled_original)
	 *
	 * @param string $xml XML to be canonicalized
	 *
	 * @return string|null Parsed XML or null if not parseable
	 */
	function dialplan_canonicalize_xml(string $xml): ?string {
		if ($xml === '') {
			return '';
		}

		$wrapped = '<?xml version="1.0" encoding="UTF-8"?><_root>' . $xml . '</_root>';

		$prev_errors = libxml_use_internal_errors(true);
		$doc = new DOMDocument();
		$loaded = $doc->loadXML($wrapped, LIBXML_NOBLANKS | LIBXML_NONET);
		libxml_clear_errors();
		libxml_use_internal_errors($prev_errors);
		if (!$loaded) {
			return null;
		}

		$xpath = new DOMXPath($doc);

		foreach (iterator_to_array($xpath->query('//comment()')) as $comment) {
			$comment->parentNode->removeChild($comment);
		}

		foreach (iterator_to_array($xpath->query('//action[@enabled="false"]')) as $node) {
			$node->parentNode->removeChild($node);
		}

		$extension_metadata_attrs = ['uuid', 'app_uuid', 'order', 'number', 'context', 'global'];
		$boolean_attrs = ['continue', 'global', 'enabled', 'inline', 'break'];

		foreach (iterator_to_array($xpath->query('//*')) as $element) {
			/** @var DOMElement $element */
			$is_extension = ($element->nodeName === 'extension');

			$attrs = [];
			foreach ($element->attributes as $attr) {
				$attrs[$attr->nodeName] = $attr->nodeValue;
			}
			foreach (array_keys($attrs) as $name) {
				$element->removeAttribute($name);
			}

			$clean = [];
			foreach ($attrs as $name => $value) {
				if ($value === '') {
					continue;
				}
				if ($is_extension && in_array($name, $extension_metadata_attrs, true)) {
					continue;
				}
				if ($is_extension && $name === 'enabled') {
					continue;
				}
				if (in_array($name, $boolean_attrs, true)) {
					$lc = strtolower($value);
					if (in_array($lc, ['1', 'true', 'yes', 'on'], true)) {
						$value = 'true';
					} elseif (in_array($lc, ['0', 'false', 'no', 'off'], true)) {
						$value = 'false';
					}
					if ($name === 'enabled' && $value === 'true') {
						continue;
					}
					if ($is_extension && $name === 'continue' && $value === 'false') {
						continue;
					}
				}
				$clean[$name] = $value;
			}

			ksort($clean, SORT_STRING);
			foreach ($clean as $name => $value) {
				$element->setAttribute($name, $value);
			}
		}

		$out = '';
		foreach ($doc->documentElement->childNodes as $child) {
			$out .= $doc->saveXML($child);
		}
		$out = preg_replace('/>\s+</', '><', $out);
		return trim($out);
	}

	/**
	 * Return the MD5 of a dialplan XML fragment after canonicalization.
	 *
	 * The expensive DOM canonicalization only runs on a cache miss. The cache
	 * key is md5() of the raw XML, so any edit automatically invalidates the
	 * cached canonical hash. In steady-state operation this cache is no longer
	 * strictly required for the baseline side — baseline hashes are now
	 * stored in v_dialplans.dialplan_hash by the enhanced_dialplans
	 * app_defaults.php (run during `php core/upgrade/upgrade.php`). The cache
	 * remains useful for the current-row side, where v_dialplans rows can be
	 * edited at any time between upgrades.
	 *
	 * @param string $xml XML to be hashed
	 *
	 * @return string|null MD5 of canonicalized XML, or null if not parseable
	 */
	function dialplan_canonical_hash(string $xml) {
		static $cache = null;
		static $cache_dirty = false;
		static $cache_path = null;
		static $shutdown_registered = false;

		if ($cache === null) {
			$cache_path = sys_get_temp_dir() . '/fusionpbx_dialplan_canon_hashes.cache';
			$cache = [];
			if (is_file($cache_path) && is_readable($cache_path)) {
				$raw = @file_get_contents($cache_path);
				if (is_string($raw) && $raw !== '') {
					$decoded = @unserialize($raw, ['allowed_classes' => false]);
					if (is_array($decoded)) {
						$cache = $decoded;
					}
				}
			}
			if (!$shutdown_registered) {
				register_shutdown_function(function () use (&$cache, &$cache_dirty, &$cache_path) {
					if (!$cache_dirty || $cache_path === null) {
						return;
					}
					if (count($cache) > 5000) {
						$cache = array_slice($cache, -5000, null, true);
					}
					@file_put_contents($cache_path, serialize($cache), LOCK_EX);
					@chmod($cache_path, 0640);
				});
				$shutdown_registered = true;
			}
		}

		$raw = (string) $xml;
		$raw_md5 = md5($raw);
		if (array_key_exists($raw_md5, $cache)) {
			return $cache[$raw_md5] === '' ? null : $cache[$raw_md5];
		}

		$canonical = dialplan_canonicalize_xml($raw);
		if ($canonical === null) {
			$cache[$raw_md5] = '';
			$cache_dirty = true;
			return null;
		}

		$hash = md5($canonical);
		$cache[$raw_md5] = $hash;
		$cache_dirty = true;
		return $hash;
	}

	/**
	 * Compare a shipped baseline XML to a stored dialplan XML, treating
	 * `{v_token}` placeholders in the baseline as wildcards that accept any
	 * substituted value in the stored copy.
	 *
	 * @return string|null 'match' / 'diff' / null (unparseable).
	 */
	function dialplan_compare_status(string $file_xml, string $db_xml) {
		static $cache = null;
		static $cache_dirty = false;
		static $cache_path = null;
		static $shutdown_registered = false;

		if ($cache === null) {
			$cache_path = sys_get_temp_dir() . '/fusionpbx_dialplan_compare.cache';
			$cache = [];
			if (is_file($cache_path) && is_readable($cache_path)) {
				$raw = @file_get_contents($cache_path);
				if (is_string($raw) && $raw !== '') {
					$decoded = @unserialize($raw, ['allowed_classes' => false]);
					if (is_array($decoded)) {
						$cache = $decoded;
					}
				}
			}
			if (!$shutdown_registered) {
				register_shutdown_function(function () use (&$cache, &$cache_dirty, &$cache_path) {
					if (!$cache_dirty || $cache_path === null) {
						return;
					}
					if (count($cache) > 5000) {
						$cache = array_slice($cache, -5000, null, true);
					}
					@file_put_contents($cache_path, serialize($cache), LOCK_EX);
					@chmod($cache_path, 0640);
				});
				$shutdown_registered = true;
			}
		}

		$key = md5(md5((string) $file_xml) . '|' . md5((string) $db_xml));
		if (array_key_exists($key, $cache)) {
			$v = $cache[$key];
			return $v === '' ? null : $v;
		}

		$file_canonical = dialplan_canonicalize_xml($file_xml);
		$db_canonical = dialplan_canonicalize_xml($db_xml);
		if ($file_canonical === null || $db_canonical === null) {
			$cache[$key] = '';
			$cache_dirty = true;
			return null;
		}

		if ($file_canonical === $db_canonical) {
			$cache[$key] = 'match';
			$cache_dirty = true;
			return 'match';
		}

		$status = 'diff';
		if (strpos($file_canonical, '{v_') !== false) {
			$pattern = preg_quote($file_canonical, '/');
			$pattern = preg_replace('/\\\\\{v_[a-zA-Z0-9_]+\\\\\}/', '[^"<>]*', $pattern);
			if (preg_match('/\A' . $pattern . '\z/', $db_canonical) === 1) {
				$status = 'match';
			}
		}

		$cache[$key] = $status;
		$cache_dirty = true;
		return $status;
	}

	/**
	 * Build a key for a shipped baseline XML file based on dialplan order/name.
	 *
	 * @param int $dialplan_order Dialplan order
	 * @param string $dialplan_name Dialplan name
	 *
	 * @return string|null Key in the form "order_normalizedname" or null if the name is empty
	 */
	function dialplan_build_original_file_key(int $dialplan_order, string $dialplan_name): ?string {
		$name_normalized = dialplan_normalize_name($dialplan_name);
		if ($name_normalized === '') {
			return null;
		}
		return ((int) $dialplan_order) . '_' . $name_normalized;
	}

	/**
	 * Build a map of shipped baseline XML files keyed by dialplan order/name.
	 *
	 * @param string $dialplan_directory Absolute path to the shipped baseline directory
	 *
	 * @return array<string,string> Map of "order_normalizedname" => absolute file path
	 */
	function dialplan_build_original_file_map(string $dialplan_directory): array {
		$file_map = [];
		$paths = glob($dialplan_directory . '/*.xml') ?: [];
		foreach ($paths as $path) {
			$key = null;

			// Prefer the XML <extension> attributes over filename prefixes.
			// Some shipped files can have a stale numeric prefix that doesn't
			// match extension@order (e.g. 474_*.xml with order="470").
			$xml = @file_get_contents($path);
			if ($xml !== false && $xml !== '') {
				$wrapped = '<?xml version="1.0" encoding="UTF-8"?><_root>' . $xml . '</_root>';
				$prev_errors = libxml_use_internal_errors(true);
				$doc = new DOMDocument();
				$loaded = $doc->loadXML($wrapped, LIBXML_NOBLANKS | LIBXML_NONET);
				libxml_clear_errors();
				libxml_use_internal_errors($prev_errors);

				if ($loaded) {
					$extension = $doc->getElementsByTagName('extension')->item(0);
					if ($extension instanceof DOMElement) {
						$name = (string) $extension->getAttribute('name');
						$order = (string) $extension->getAttribute('order');
						if ($name !== '') {
							$key = dialplan_build_original_file_key((int) $order, $name);
						}
					}
				}
			}

			// Fallback for malformed XML or unexpected content.
			if ($key === null) {
				$filename = basename($path);
				if (preg_match('/^(\d{1,4})_([^.]+)\.xml$/', $filename, $matches)) {
					$key = dialplan_build_original_file_key($matches[1], $matches[2]);
				}
			}

			if ($key !== null && !isset($file_map[$key])) {
				$file_map[$key] = $path;
			}
		}
		return $file_map;
	}

	/**
	 * Build a name-only fallback index (normalized name => file path) derived
	 * from an existing order+name file map. Used to still locate a dialplan's
	 * shipped baseline file when the row's live `dialplan_order` no longer
	 * matches the order recorded in the baseline (e.g. the row was migrated to
	 * a different order, or moved between contexts, without the order being
	 * kept in sync). First file wins on name collisions, same as the primary
	 * map.
	 *
	 * @param array<string,string> $file_map Map produced by dialplan_build_original_file_map()
	 *
	 * @return array<string,string> Map of normalized dialplan name => absolute file path
	 */
	function dialplan_build_original_name_index(array $file_map): array {
		$name_index = [];
		foreach ($file_map as $key => $path) {
			$underscore_pos = strpos($key, '_');
			if ($underscore_pos === false) {
				continue;
			}
			$name = substr($key, $underscore_pos + 1);
			if ($name !== '' && !isset($name_index[$name])) {
				$name_index[$name] = $path;
			}
		}
		return $name_index;
	}

	/**
	 * Lookup the shipped baseline XML file path for a given dialplan order/name.
	 *
	 * When $name_index is supplied and the order+name composite key does not
	 * match (e.g. the row's order was changed relative to the baseline), falls
	 * back to a name-only match so order mismatches can still be detected
	 * instead of showing up as 'missing'.
	 *
	 * @return string|null Absolute path to the shipped baseline XML file, or null if no matching file is found.
	 */
	function dialplan_find_original_file(int $dialplan_order, string $dialplan_name, array $file_map, ?array $name_index = null) {
		$key = dialplan_build_original_file_key($dialplan_order, $dialplan_name);
		if ($key !== null && isset($file_map[$key])) {
			return $file_map[$key];
		}
		if ($name_index !== null) {
			$name_normalized = dialplan_normalize_name($dialplan_name);
			if ($name_normalized !== '' && isset($name_index[$name_normalized])) {
				return $name_index[$name_normalized];
			}
		}
		return null;
	}

	/**
	 * Return the default shipped-baseline directory for dialplan XML files.
	 *
	 * @return string Absolute path to the shipped baseline directory
	 */
	function dialplan_baseline_directory(): string {
		// This file lives at app/enhanced_dialplans/resources/functions/ so
		// dirname(__DIR__, 3) is the app/ root.
		return dirname(__DIR__, 3) . '/dialplans/resources/switch/conf/dialplan';
	}

	/**
	 * Extract the "enabled" attribute of the root <extension> element from a
	 * shipped dialplan XML fragment. FusionPBX's dialplan importer defaults
	 * missing `enabled` attrs to true (see app/dialplans/resources/classes/
	 * dialplan.php ~L300), so we mirror that default here.
	 *
	 * Returns true/false, or null if the XML cannot be parsed (caller should
	 * then leave dialplan_enabled_original untouched).
	 *
	 * @param string $xml XML to be parsed
	 *
	 * @return bool|null Enabled state or null if not parseable
	 */
	function dialplan_baseline_enabled(string $xml) {
		if ($xml === '') {
			return null;
		}
		$wrapped = '<?xml version="1.0" encoding="UTF-8"?><_root>' . $xml . '</_root>';
		$prev_errors = libxml_use_internal_errors(true);
		$doc = new DOMDocument();
		$loaded = $doc->loadXML($wrapped, LIBXML_NOBLANKS | LIBXML_NONET);
		libxml_clear_errors();
		libxml_use_internal_errors($prev_errors);
		if (!$loaded) {
			return null;
		}
		$extension = $doc->getElementsByTagName('extension')->item(0);
		if ($extension === null) {
			return true;
		}
		$value = $extension->getAttribute('enabled');
		if ($value === '') {
			return true;
		}
		$lc = strtolower($value);
		if (in_array($lc, ['0', 'false', 'no', 'off'], true)) {
			return false;
		}
		return true;
	}

	/**
	 * Extract the "context" attribute of the root <extension> element from a
	 * shipped dialplan XML fragment. The raw value is returned as-is, which
	 * may contain the literal `${domain_name}` template token for per-domain
	 * dialplans - callers are responsible for substituting the token with the
	 * actual domain name of the row being compared.
	 *
	 * @param string $xml XML to be parsed
	 *
	 * @return string|null Context value, or null if not parseable/not present
	 */
	function dialplan_baseline_context(string $xml) {
		if ($xml === '') {
			return null;
		}
		$wrapped = '<?xml version="1.0" encoding="UTF-8"?><_root>' . $xml . '</_root>';
		$prev_errors = libxml_use_internal_errors(true);
		$doc = new DOMDocument();
		$loaded = $doc->loadXML($wrapped, LIBXML_NOBLANKS | LIBXML_NONET);
		libxml_clear_errors();
		libxml_use_internal_errors($prev_errors);
		if (!$loaded) {
			return null;
		}
		$extension = $doc->getElementsByTagName('extension')->item(0);
		if ($extension === null) {
			return null;
		}
		$value = $extension->getAttribute('context');
		return $value === '' ? null : $value;
	}

	/**
	 * Extract the "order" attribute of the root <extension> element from a
	 * shipped dialplan XML fragment.
	 *
	 * @param string $xml XML to be parsed
	 *
	 * @return int|null Order value, or null if not parseable/not present
	 */
	function dialplan_baseline_order(string $xml) {
		if ($xml === '') {
			return null;
		}
		$wrapped = '<?xml version="1.0" encoding="UTF-8"?><_root>' . $xml . '</_root>';
		$prev_errors = libxml_use_internal_errors(true);
		$doc = new DOMDocument();
		$loaded = $doc->loadXML($wrapped, LIBXML_NOBLANKS | LIBXML_NONET);
		libxml_clear_errors();
		libxml_use_internal_errors($prev_errors);
		if (!$loaded) {
			return null;
		}
		$extension = $doc->getElementsByTagName('extension')->item(0);
		if ($extension === null) {
			return null;
		}
		$value = $extension->getAttribute('order');
		return $value === '' ? null : (int) $value;
	}
	function dialplan_populate_original_hashes() {
		global $database; /** @var database $database Database Object */

		// Skip if schema upgrade has not been executed yet
		if (!$database->table_exists('v_dialplans')
			|| !$database->column_exists('v_dialplans', 'dialplan_enabled_original')
			|| !$database->column_exists('v_dialplans', 'dialplan_context_original')
			|| !$database->column_exists('v_dialplans', 'dialplan_order_original')
			|| !$database->column_exists('v_dialplans', 'dialplan_hash_file')
			|| !$database->column_exists('v_dialplans', 'dialplan_hash_canonical')) {
			return;
		}

		$baseline_dir = dialplan_baseline_directory();
		if (is_dir($baseline_dir)) {

			// Build order+name -> file/canonical hash map for shipped XML files.
			$file_map  = dialplan_build_original_file_map($baseline_dir);
			$file_hash_file = [];
			$file_hash_canonical = [];
			$file_enabled = [];
			$file_context = [];
			$file_order = [];
			foreach ($file_map as $key => $path) {
				$xml = @file_get_contents($path);
				if ($xml === false) {
					$error = error_get_last();
					// Check if we are running in CLI mode and debug is enabled
					if (!empty($error) && is_array($error) && is_cli() && (!empty($_ENV['debug']) || (isset($argv[1]) && $argv[1] === 'debug'))) {
						// Show the error message in the console
						$error_message = $error['message'] ?? '';
						echo "	dialplans: failed to read baseline XML file {$path}: {$error_message}\n";
					}
					continue;
				}
				$canonical = dialplan_canonicalize_xml($xml);
				if ($canonical === null) {
					// Unparseable baseline - skip; affected rows will fall through
					// to the slow-path compare in the UI.
					continue;
				}
				// Store the hash of the original XML file and canonicalized XML.
				$file_hash_file[$key] = md5($xml);
				$file_hash_canonical[$key] = md5($canonical);
				$enabled = dialplan_baseline_enabled($xml);
				if ($enabled !== null) {
					$file_enabled[$key] = $enabled;
				}
				$context = dialplan_baseline_context($xml);
				if ($context !== null) {
					$file_context[$key] = $context;
				}
				$order = dialplan_baseline_order($xml);
				if ($order !== null) {
					$file_order[$key] = $order;
				}
			}

			// Name-only fallback index so rows whose live dialplan_order no longer
			// matches the order recorded in the baseline (e.g. moved to a different
			// order) can still be matched to their shipped baseline file.
			$file_name_index = dialplan_build_original_name_index($file_map);

			if (!empty($file_hash_canonical)) {
				// Select only the columns we need; no point loading dialplan_xml
				// (could be hundreds of KB across the whole table).
				$sql = "SELECT
							dialplan_uuid
							,dialplan_name
							,dialplan_order
							,dialplan_hash_file
							,dialplan_hash_canonical
							,dialplan_enabled_original
							,dialplan_context_original
							,dialplan_order_original
						FROM
							v_dialplans";
				$rows = $database->select($sql, null, 'all');
				$updates = 0;
				if (is_array($rows)) {
					foreach ($rows as $row) {
						$key = dialplan_build_original_file_key($row['dialplan_order'] ?? null, $row['dialplan_name'] ?? null);
						if ($key === null || !isset($file_hash_canonical[$key])) {
							// Order mismatch fallback: look the baseline file up by name only.
							$name_normalized = dialplan_normalize_name((string) ($row['dialplan_name'] ?? ''));
							$fallback_path = $name_normalized !== '' ? ($file_name_index[$name_normalized] ?? null) : null;
							$key = null;
							if ($fallback_path !== null) {
								$key = array_search($fallback_path, $file_map, true) ?: null;
							}
							if ($key === null || !isset($file_hash_canonical[$key])) {
								continue;
							}
						}
						$new_hash_file = $file_hash_file[$key] ?? null;
						$new_hash_canonical = $file_hash_canonical[$key];
						$new_enabled = $file_enabled[$key] ?? null;
						$new_context = $file_context[$key] ?? null;
						$new_order = $file_order[$key] ?? null;

						// Existing values, normalized: PostgreSQL can return 't' or 'f'.
						$existing_enabled_raw = $row['dialplan_enabled_original'] ?? null;
						$existing_enabled = null;
						if ($existing_enabled_raw !== null && $existing_enabled_raw !== '') {
							$existing_enabled = in_array(strtolower((string) $existing_enabled_raw), ['1', 't', 'true', 'yes', 'on', true], true);
						}

						$file_hash_ok = ($new_hash_file === null) || (($row['dialplan_hash_file'] ?? null) === $new_hash_file);
						$canonical_hash_ok = (($row['dialplan_hash_canonical'] ?? null) === $new_hash_canonical);
						$enabled_ok = ($new_enabled === null) || ($existing_enabled === $new_enabled);
						$context_ok = ($new_context === null) || (($row['dialplan_context_original'] ?? null) === $new_context);
						$existing_order_raw = $row['dialplan_order_original'] ?? null;
						$existing_order = ($existing_order_raw === null || $existing_order_raw === '') ? null : (int) $existing_order_raw;
						$order_ok = ($new_order === null) || ($existing_order === $new_order);

						// Skip rows that already have the correct values - no database update needed
						if ($file_hash_ok && $canonical_hash_ok && $enabled_ok && $context_ok && $order_ok) {
							continue;
						}

						$sets = [
							'dialplan_hash_file = :dialplan_hash_file',
							'dialplan_hash_canonical = :dialplan_hash_canonical'
						];
						$params = [
							'dialplan_hash_file' => $new_hash_file,
							'dialplan_hash_canonical' => $new_hash_canonical,
							'dialplan_uuid' => $row['dialplan_uuid']
						];
						if ($new_enabled !== null) {
							$sets[] = 'dialplan_enabled_original = :dialplan_enabled_original';
							$params['dialplan_enabled_original'] = $new_enabled ? 'true' : 'false';
						}
						if ($new_context !== null) {
							$sets[] = 'dialplan_context_original = :dialplan_context_original';
							$params['dialplan_context_original'] = $new_context;
						}
						if ($new_order !== null) {
							$sets[] = 'dialplan_order_original = :dialplan_order_original';
							$params['dialplan_order_original'] = $new_order;
						}
						$database->execute(
							'update v_dialplans set ' . implode(', ', $sets) . ' where dialplan_uuid = :dialplan_uuid',
							$params
						);
						$updates++;
					}
				}

				// Show a message in the console if any rows were updated and we are running in CLI mode with debug enabled
				if ($updates > 0  && is_cli() && (!empty($_ENV['debug']) || (isset($argv[1]) && $argv[1] === 'debug'))) {
					echo "    dialplans: populated dialplan_hash_file and dialplan_hash_canonical for {$updates} row(s).\n";
				}
			}
		}

	}

	/**
	 * Find shipped baseline dialplans (parsed from the static XML templates in
	 * app/dialplans/resources/switch/conf/dialplan/) that have no matching
	 * live row left in v_dialplans for the given domain (i.e. the user
	 * deleted the row entirely). Used by dialplans.php to render "ghost" rows
	 * hinting that running the upgrade will restore the missing entry.
	 *
	 * Applies the same app_uuid/context/search visibility rules used by the
	 * live-row list query so the ghost rows only appear on the same
	 * tab/filtered view where the real row would have appeared.
	 *
	 * @param database $database Database object
	 * @param string $domain_uuid Current domain UUID
	 * @param array $dialplan_templates Baseline templates keyed by dialplan_name (see dialplans.php)
	 * @param string $app_uuid Selected app_uuid tab filter (empty = default Dialplan Manager tab)
	 * @param string $context Selected dialplan_context filter, if any
	 * @param string $search Selected search term, if any
	 * @param array $excluded_app_uuids app_uuids that are UI-managed and have no static 1:1 baseline file
	 *
	 * @return array<int,array> List of synthetic dialplan rows flagged with 'is_missing_dialplan' => true
	 */
	function dialplan_find_missing_dialplans(database $database, string $domain_uuid, array $dialplan_templates, string $app_uuid, string $context, string $search, array $excluded_app_uuids): array {
		if (empty($dialplan_templates)) {
			return [];
		}

		$sql = "select distinct dialplan_name from v_dialplans ";
		$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$existing_rows = $database->select($sql, ['domain_uuid' => $domain_uuid], 'all');
		$existing_dialplan_names = [];
		if (is_array($existing_rows)) {
			foreach ($existing_rows as $existing_row) {
				$existing_dialplan_names[$existing_row['dialplan_name']] = true;
			}
		}

		$missing_dialplans = [];
		foreach ($dialplan_templates as $template_name => $template) {
			//skip templates that already have a live row for this domain (or a shared/global row)
			if (isset($existing_dialplan_names[$template_name])) {
				continue;
			}


			//skip apps that are managed entirely through their own UI (no static 1:1 baseline file)
			if (in_array($template['uuid'], $excluded_app_uuids, true)) {
				continue;
			}

			//apply the same app_uuid visibility rules used for the live rows in dialplans.php
			if (empty($app_uuid)) {
				if ($template['uuid'] === 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4' || $template['context'] === 'public') {
					continue;
				}
			}
			else if ($app_uuid === 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4') {
				if ($template['uuid'] !== $app_uuid && $template['context'] !== 'public') {
					continue;
				}
			}
			else if ($template['uuid'] !== $app_uuid) {
				continue;
			}

			//apply the context filter, if any
			if ($context !== '' && $template['context'] !== $context) {
				continue;
			}

			//apply the search filter, if any
			if ($search !== '' && stripos($template_name, $search) === false) {
				continue;
			}

			$missing_dialplans[] = [
				'is_missing_dialplan' => true,
				'app_uuid' => $template['uuid'],
				'dialplan_name' => $template_name,
				'dialplan_context' => $template['context'],
				'dialplan_order' => $template['order'],
				'dialplan_enabled' => (in_array(strtolower((string) $template['enabled']), ['1', 't', 'true', 'yes', 'on'], true) ? 'true' : 'false'),
			];
		}

		return $missing_dialplans;
	}

	/**
	 * Merge synthetic "missing" dialplan ghost rows into the current page of
	 * live dialplan rows, inserting them into their correct sorted position
	 * (rather than appending at the end) so a deleted baseline entry appears
	 * where the user expects it - e.g. domain-variables (order 20) shows up
	 * near the top instead of being lost at the bottom of the list.
	 *
	 * The combined list is ordered using the same criteria the list SQL uses:
	 * when no explicit column is selected it sorts by dialplan_order asc then
	 * dialplan_name asc; otherwise it honours the selected column/direction.
	 *
	 * @param array $dialplans Current page of live dialplan rows (already sorted by SQL)
	 * @param array $missing_dialplans Synthetic ghost rows from dialplan_find_missing_dialplans()
	 * @param string $order_by Selected sort column, if any
	 * @param string $order Selected sort direction ('asc' or 'desc')
	 *
	 * @return array Combined, correctly ordered list of dialplan rows
	 */
	function dialplan_merge_missing_sorted(array $dialplans, array $missing_dialplans, string $order_by, string $order): array {
		if (empty($missing_dialplans)) {
			return $dialplans;
		}
		if (empty($dialplans)) {
			return $missing_dialplans;
		}

		$combined = array_merge($dialplans, $missing_dialplans);
		$direction = (strtolower($order) === 'desc') ? -1 : 1;

		//numeric columns are compared as numbers, everything else case-insensitively
		$numeric_columns = ['dialplan_order', 'dialplan_number'];

		usort($combined, function ($a, $b) use ($order_by, $direction, $numeric_columns) {
			//default ordering: dialplan_order asc, then dialplan_name asc
			if ($order_by === '') {
				$order_a = (int) ($a['dialplan_order'] ?? 0);
				$order_b = (int) ($b['dialplan_order'] ?? 0);
				if ($order_a !== $order_b) {
					return $order_a <=> $order_b;
				}
				return strcasecmp((string) ($a['dialplan_name'] ?? ''), (string) ($b['dialplan_name'] ?? ''));
			}

			$value_a = $a[$order_by] ?? '';
			$value_b = $b[$order_by] ?? '';

			if (in_array($order_by, $numeric_columns, true)) {
				$result = ((int) $value_a) <=> ((int) $value_b);
			}
			else {
				$result = strcasecmp((string) $value_a, (string) $value_b);
			}

			return $result * $direction;
		});

		return $combined;
	}
}
