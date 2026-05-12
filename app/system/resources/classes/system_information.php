<?php

/*
 * The MIT License
 *
 * Copyright 2025 Tim Fry <tim@fusionpbx.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Description of system_information
 *
 * @author Tim Fry <tim@fusionpbx.com>
 */
abstract class system_information {

	abstract public function get_cpu_cores(): int;
	abstract public function get_uptime();
	abstract public function get_cpu_percent(): float;
	abstract public function get_cpu_percent_per_core(): array;
	abstract public function get_network_speed(string $interface = 'eth0'): array;
	abstract public function get_network_card(?string $default_value = null): ?string;
	abstract public function is_running(string $name): array;
	abstract protected function get_service_identifier(string $file): string;
	abstract protected function select_preferred_service_file(array $module_files): ?string;

	/**
	 * Returns the system load average.
	 *
	 * @return array Three most recent one-minute load averages.
	 */
	public function get_load_average() {
		return sys_getloadavg();
	}

	/**
	 * Builds installed service status entries from grouped service definition files.
	 *
	 * @param array $grouped_service_files Grouped service files keyed by module directory.
	 * @param array $service_labels Optional map of service key to display label.
	 *
	 * @return array Installed services keyed by service name.
	 */
	public function get_installed_services(array $grouped_service_files, array $service_labels = []): array {
		$services = [];

		foreach ($grouped_service_files as $module_files) {
			if (!is_array($module_files) || empty($module_files)) {
				continue;
			}

			$selected_file = $this->select_preferred_service_file($module_files);
			if (empty($selected_file)) {
				continue;
			}

			$service = $this->get_service_identifier($selected_file);
			if (!empty($service)) {
				$basename = basename($service, '.php');
				$info = $this->is_running($basename);
				$info['label'] = $service_labels[$basename] ?? ucwords(str_replace('_', ' ', $basename));
				$services[$basename] = $info;
			}
		}

		return $services;
	}

	/**
	 * Returns a system information object based on the underlying operating system.
	 *
	 * @return ?system_information The system information object for the current OS, or null if not supported.
	 */
	public static function new(): ?system_information {
		// Compatibility with PHP 7.1 and below
		if (!defined('PHP_OS_FAMILY')) {
			if (stripos(PHP_OS, 'linux') === 0) {
				define('PHP_OS_FAMILY', 'Linux');
			} elseif (stripos(PHP_OS, 'bsd') !== false) {
				define('PHP_OS_FAMILY', 'BSD');
			} elseif (stripos(PHP_OS, 'dar') === 0) {
				define('PHP_OS_FAMILY', 'Darwin');
			} elseif (stripos(PHP_OS, 'sunos') === 0) {
				define('PHP_OS_FAMILY', 'Solaris');
			} elseif (stripos(PHP_OS, 'win') === 0) {
				define('PHP_OS_FAMILY', 'Windows');
			} else {
				define('PHP_OS_FAMILY', 'Unknown');
			}
		}

		// Determine the class name based on the OS family
		$class = strtolower(PHP_OS_FAMILY) . '_system_information';

		if (class_exists($class)) {
			// linux_system_information or bsd_system_information object
			return new $class();
		}

		// Unsupported OS
		return null;
	}
}
