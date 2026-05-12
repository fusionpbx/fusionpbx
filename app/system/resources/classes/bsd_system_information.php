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
 * Description of bsd_system_information
 *
 * @author Tim Fry <tim@fusionpbx.com>
 */
class bsd_system_information extends system_information {

	/**
	 * Selects the preferred service definition file for BSD.
	 *
	 * @param array $module_files Service definition files for a module.
	 *
	 * @return string|null Preferred file path.
	 */
	protected function select_preferred_service_file(array $module_files): ?string {
		$selected_file = $module_files[0] ?? null;
		if ($selected_file === null) {
			return null;
		}

		foreach ($module_files as $candidate) {
			if (basename($candidate) === 'freebsd.service') {
				$selected_file = $candidate;
				break;
			}
		}

		return $selected_file;
	}

	/**
	 * Retrieves a BSD service identifier from an rc.d-style service definition.
	 *
	 * @param string $file Path to the service file.
	 *
	 * @return string Service identifier, or empty string if not found.
	 */
	protected function get_service_identifier(string $file): string {
		if (!file_exists($file)) {
			return '';
		}

		$content = file_get_contents($file);
		if ($content === false) {
			return '';
		}

		if (preg_match('/^#\s*PROVIDE:\s*(\S+)/mi', $content, $matches)) {
			return trim($matches[1]);
		}

		if (preg_match('/^name\s*=\s*["\']([^"\']+)["\']/mi', $content, $matches)) {
			return trim($matches[1]);
		}

		return '';
	}

	/**
	 * Checks if a process with the given name is currently running on BSD.
	 *
	 * @param string $name The name of the process to check for.
	 *
	 * @return array Process status including running flag, PID, and elapsed time.
	 */
	public function is_running(string $name): array {
		$name = trim($name);
		$safe_name = escapeshellarg($name);
		$running = false;
		$pid = null;
		$etime = null;

		$rc = 1;
		exec("service $safe_name onestatus >/dev/null 2>&1", $out, $rc);
		if ($rc === 0) {
			$running = true;
			$status_line = shell_exec("service $safe_name status 2>/dev/null");
			if (preg_match('/pid\s+([0-9]+)/i', (string)$status_line, $m)) {
				$pid = $m[1];
			}
		}

		// Fallback for services with non-standard status output or process names.
		if (!$running) {
			$proc_name = ($name === 'postgresql') ? 'postgres' : $name;
			$safe_proc = escapeshellarg($proc_name);
			$pid_guess = trim((string)shell_exec("pgrep -f $safe_proc | head -n 1"));
			if ($pid_guess !== '' && preg_match('/^\d+$/', $pid_guess)) {
				$running = true;
				$pid = $pid_guess;
			}
		}

		if ($running && !empty($pid)) {
			$etime = trim((string)shell_exec("ps -p " . escapeshellarg($pid) . " -o etime= | tr -d '\n'"));
		}

		return ['running' => $running, 'pid' => $pid, 'etime' => $etime];
	}

	/**
	 * Returns the network card information.
	 *
	 * @param string|null $default_value The default value to return if the network card information cannot be determined.
	 *
	 * @return string|null The network card information or the default value.
	 */
    public function get_network_card(?string $default_value = null): ?string {
		// Implementation for BSD systems - get first non-loopback interface
		$result = shell_exec("ifconfig -l 2>/dev/null | awk '{print $1}' | head -n1");
		$network_card = trim($result);
		if (!$network_card) {
			// Fallback: try em0 first (common on VMs), then other common BSD interfaces
			foreach (['em0', 'igb0', 'ixl0', 're0', 'bge0'] as $iface) {
				if (@file_exists("/sys/class/net/$iface") || shell_exec("ifconfig $iface 2>/dev/null") !== null) {
					return $iface;
				}
			}
		}
		return $network_card ?: $default_value;
    }

	/**
	 * Returns the number of CPU cores available on the system.
	 *
	 * @return int The number of CPU cores.
	 */
	public function get_cpu_cores(): int {
		// Try sysctl first (more reliable on FreeBSD)
		$result = @shell_exec("sysctl -n hw.ncpu 2>/dev/null");
		if ($result && is_numeric(trim($result))) {
			return intval(trim($result));
		}
		// Fallback to dmesg parsing
		$result = @shell_exec("dmesg | grep -i --max-count 1 CPUs | sed 's/[^0-9]*//g' 2>/dev/null");
		$cpu_cores = intval(trim($result));
		return $cpu_cores > 0 ? $cpu_cores : 1;
	}

	//get the CPU details

	/**
	 * Returns the current CPU usage percentage.
	 *
	 * @return float The current CPU usage percentage.
	 */
	public function get_cpu_percent(): float {
		static $last = null;

		$read_cp_time = static function (): ?array {
			$raw = @trim((string) shell_exec('sysctl -n kern.cp_time 2>/dev/null'));
			if ($raw === '') {
				return null;
			}
			$parts = array_map('intval', preg_split('/\s+/', $raw));
			if (count($parts) < 5) {
				return null;
			}
			return [
				'user' => $parts[0],
				'nice' => $parts[1],
				'sys' => $parts[2],
				'intr' => $parts[3],
				'idle' => $parts[4],
				'total' => array_sum(array_slice($parts, 0, 5)),
			];
		};

		$current = $read_cp_time();
		if ($current === null) {
			return 0;
		}

		// Prime baseline on first call so we can calculate a meaningful delta.
		if ($last === null) {
			$last = $current;
			usleep(200000);
			$current = $read_cp_time();
			if ($current === null) {
				return 0;
			}
		}

		$delta_total = $current['total'] - $last['total'];
		$delta_idle = $current['idle'] - $last['idle'];
		$last = $current;

		if ($delta_total <= 0) {
			return 0;
		}

		$usage = (1 - ($delta_idle / $delta_total)) * 100;
		if ($usage < 0) {
			$usage = 0;
		}
		if ($usage > 100) {
			$usage = 100;
		}

		return round($usage, 2);
	}

	/**
	 * Returns the system uptime in seconds.
	 *
	 * @return string The system uptime in seconds.
	 */
	public function get_uptime() {
		$result = @shell_exec('uptime 2>/dev/null');
		return $result ?: 'unknown';
	}

	/**
	 * Returns the current CPU usage percentage per core.
	 *
	 * @return array An associative array where keys are core indices and values are their respective CPU usage percentages.
	 */
	public function get_cpu_percent_per_core(): array {
		static $last = [];
		$results = [];

		// Read the raw CPU time ticks from sysctl (returns flat array of cores)
		$raw = trim(shell_exec('sysctl -n kern.cp_times'));
		if (!$raw)
			return [];

		$parts = array_map('intval', preg_split('/\s+/', $raw));
		$num_cores = count($parts) / 5;

		for ($core = 0; $core < $num_cores; $core++) {
			$offset = $core * 5;
			$user = $parts[$offset];
			$nice = $parts[$offset + 1];
			$sys = $parts[$offset + 2];
			$intr = $parts[$offset + 3];
			$idle = $parts[$offset + 4];

			$total = $user + $nice + $sys + $intr + $idle;

			if (!isset($last[$core])) {
				$last[$core] = ['total' => $total, 'idle' => $idle];
				$results[$core] = 0;
				continue;
			}

			$delta_total = $total - $last[$core]['total'];
			$delta_idle = $idle - $last[$core]['idle'];

			$usage = $delta_total > 0 ? (1 - ($delta_idle / $delta_total)) * 100 : 0;
			$results[$core] = round($usage, 2);

			$last[$core] = ['total' => $total, 'idle' => $idle];
		}

		return $results;
	}

	/**
	 * Returns the current network speed for a given interface.
	 *
	 * @param string $interface The network interface to query (default: 'em0')
	 *
	 * @return array An array containing the current receive and transmit speeds in bytes per second.
	 */
	public function get_network_speed(string $interface = 'em0'): array {
		static $last = [];

		// Validate interface exists by running netstat
		$output = shell_exec("netstat -bI {$interface} 2>/dev/null");
		if (!$output) {
			// Interface doesn't exist or error - return zeros and try to detect correct interface
			if (!isset($last[$interface])) {
				// Try to auto-detect valid interface
				$fallback = $this->get_network_card();
				if ($fallback && $fallback !== $interface) {
					$output = shell_exec("netstat -bI {$fallback} 2>/dev/null");
					if ($output) {
						// Use fallback interface for future calls
						$interface = $fallback;
					} else {
						return ['rx_bps' => 0, 'tx_bps' => 0];
					}
				} else {
					return ['rx_bps' => 0, 'tx_bps' => 0];
				}
			} else {
				return ['rx_bps' => 0, 'tx_bps' => 0];
			}
		}

		$lines = explode("\n", trim($output));
		if (count($lines) < 2)
			return ['rx_bps' => 0, 'tx_bps' => 0];

		$cols = preg_split('/\s+/', trim($lines[1]));
		if (count($cols) < 11)
			return ['rx_bps' => 0, 'tx_bps' => 0];

		// FreeBSD netstat -bI layout:
		// 0 Name 1 Mtu 2 Network 3 Address 4 Ipkts 5 Ierrs 6 Idrop 7 Ibytes 8 Opkts 9 Oerrs 10 Obytes 11 Coll
		$rx_bytes = (int) $cols[7]; // Ibytes
		$tx_bytes = (int) $cols[10]; // Obytes
		$now = microtime(true);

		if (!isset($last[$interface])) {
			$last[$interface] = ['rx' => $rx_bytes, 'tx' => $tx_bytes, 'time' => $now];
			return ['rx_bps' => 0, 'tx_bps' => 0];
		}

		$delta_time = $now - $last[$interface]['time'];
		$delta_rx = $rx_bytes - $last[$interface]['rx'];
		$delta_tx = $tx_bytes - $last[$interface]['tx'];

		$last[$interface] = ['rx' => $rx_bytes, 'tx' => $tx_bytes, 'time' => $now];

		return [
			'rx_bps' => $delta_rx / $delta_time,
			'tx_bps' => $delta_tx / $delta_time
		];
	}
}
