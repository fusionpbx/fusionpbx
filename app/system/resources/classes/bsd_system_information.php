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

	public function get_cpu_cores(): int {
		$result = shell_exec("dmesg | grep -i --max-count 1 CPUs | sed 's/[^0-9]*//g'");
		$cpu_cores = trim($result);
		return $cpu_cores;
	}

	//get the CPU details
	public function get_cpu_percent(): float {
		$result = shell_exec('ps -A -o pcpu');
		$percent_cpu = 0;
		foreach (explode("\n", $result) as $value) {
			if (is_numeric($value)) {
				$percent_cpu = $percent_cpu + $value;
			}
		}
		return $percent_cpu;
	}

	public function get_uptime() {
		return shell_exec('uptime');
	}

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
	 *
	 * @staticvar array $last
	 * @param string $interface
	 * @return array
	 * @depends FreeBSD Version 12
	 */
	public function get_network_speed(string $interface = 'em0'): array {
		static $last = [];

		// Run netstat for the interface
		$output = shell_exec("netstat -bI {$interface} 2>/dev/null");
		if (!$output)
			return ['rx_bps' => 0, 'tx_bps' => 0];

		$lines = explode("\n", trim($output));
		if (count($lines) < 2)
			return ['rx_bps' => 0, 'tx_bps' => 0];

		$cols = preg_split('/\s+/', $lines[1]);
		$rx_bytes = (int) $cols[6]; // Ibytes
		$tx_bytes = (int) $cols[9]; // Obytes
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
