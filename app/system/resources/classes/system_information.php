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
	abstract public function get_memory_details(): array;

	public function get_load_average() {
		return sys_getloadavg();
	}

	public function get_disk_used_terabytes() {
		return $this->get_disk_used_gigabytes() / 1024;
	}

	public function get_disk_used_gigabytes() {
		return $this->get_disk_used_megabytes() / 1024;
	}

	public function get_disk_used_megabytes() {
		return $this->get_disk_used_kilobytes() / 1024;
	}

	public function get_disk_used_kilobytes() {
		return ($this->get_disk_usage())['used'] / 1024;
	}

	public function get_disk_free_terabytes() {
		return $this->get_disk_free_gigabytes() / 1024;
	}

	public function get_disk_free_gigabytes() {
		return $this->get_disk_free_megabytes() / 1024;
	}

	public function get_disk_free_megabytes() {
		return $this->get_disk_free_kilobytes() / 1024;
	}

	public function get_disk_free_kilobytes() {
		return ($this->get_disk_usage())['free'] / 1024;
	}

	public function get_disk_total_terabytes() {
		return $this->get_disk_total_gigabytes() / 1024;
	}

	public function get_disk_total_gigabytes() {
		return $this->get_disk_total_megabytes() / 1024;
	}

	public function get_disk_total_megabytes() {
		return $this->get_disk_total_kilobytes() / 1024;
	}

	public function get_disk_total_kilobytes() {
		return ($this->get_disk_usage())['total'] / 1024;
	}

	/**
	 * Returns the OS family as a lowercase string independent of the PHP version
	 * @return string Lowercase name of the detected family
	 * @final
	 */
	public static final function get_os_family(): string {
		// PHP 7.2+
		if (defined(PHP_OS_FAMILY)) {
			return strtolower(PHP_OS_FAMILY);
		}
		// PHP < 7.2
		if (stripos(PHP_OS, 'LINUX') !== false) return 'linux';
		if (stripos(PHP_OS, 'BSD') !== false) return 'bsd';
		if (stripos(PHP_OS, 'DARWIN') !== false) return 'darwin';
		if (stripos(PHP_OS, 'WIN') !== false) return 'windows';
		if (stripos(PHP_OS, 'SUNOS') !== false || stripos(PHP_OS, 'SOLARIS') !== false) return 'solaris';
		return '';
	}

	public function get_disk_usage(): array {
		$free = disk_free_space("/");
		$total = disk_total_space("/");
		$used = $total - $free;
		return ['total' => $total, 'free' => $free, 'used' => $used];
	}

	/**
	 * Returns a new system information object based on the current OS
	 * @return system_information|null
	 */
	public static function new(): ?system_information {

		// Get OS family as a lowercase string independent of the PHP version
		$os = self::get_os_family();

		// Ensure we have the OS family
		if(empty($os)) {
			return null;
		}

		// Set the class name based on the OS
		$class = "{$os}_system_information";

		// Create an instance of the system information object matching the current OS
		return new $class();
	}
}
