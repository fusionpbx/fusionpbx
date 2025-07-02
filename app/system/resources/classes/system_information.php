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
	abstract public function get_disk_usage(): array;
	abstract public function get_memory_details(): array;

	public function get_load_average() {
		return sys_getloadavg();
	}

	public static function get_os_name(): string {
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

	public static function new(): ?system_information {
		$os = self::get_os_name();
		if(!empty($os)) {
			$class = "{$os}_system_information";
			return new $class();
		}
		return null;
	}
}
