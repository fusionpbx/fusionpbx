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
 * Description of solaris_system_information
 *
 * @author Tim Fry <tim@fusionpbx.com>
 */
class solaris_system_information extends system_information {

	public function get_memory_details(): array {
		$total = (int) shell_exec("prtconf | grep Memory | awk '{print $3 * 1024 * 1024}'");
		$free = (int) shell_exec("vmstat 1 2 | tail -1 | awk '{print $5 * 1024}'");
		$used = $total - $free;

		return [
			'total' => $total,
			'available' => $free,
			'used' => $used
		];
	}

	public function get_uptime(): string {
		$uptime = shell_exec("uptime");
		if (preg_match('/up ([^,]+),/', $uptime, $matches)) {
			return trim($matches[1]);
		}
		return 'unknown';
	}

	public function get_cpu_cores(): int {
		return (int) shell_exec("psrinfo | wc -l");
	}

	public function get_cpu_percent(): float {
		$output = shell_exec("mpstat 1 1 | tail -1");
		if (preg_match('/\s+(\d+\.\d+)\s*$/', $output, $matches)) {
			$idle = (float) $matches[1];
			return round(100 - $idle, 2);
		}
		return 0.0;
	}

	public function get_cpu_percent_per_core(): array {
		$output = shell_exec("mpstat -P ALL 1 1");
		$lines = explode("\n", $output);
		$results = [];

		foreach ($lines as $line) {
			if (preg_match('/^\s*(\d+)\s+\d+\.\d+\s+\d+\.\d+\s+\d+\.\d+\s+\d+\.\d+\s+(\d+\.\d+)/', $line, $m)) {
				$core = (int) $m[1];
				$idle = (float) $m[2];
				$results[$core] = round(100 - $idle, 2);
			}
		}
		return $results;
	}

	public function get_network_speed(string $interface = 'eth0'): array {
		static $last = [];

		$netstat = shell_exec("kstat -p -c net -n {$interface} | grep bytes");
		$rx = $tx = 0;

		foreach (explode("\n", $netstat) as $line) {
			if (strpos($line, "rbytes64") !== false) {
				$rx = (int) explode("\t", $line)[1];
			}
			if (strpos($line, "obytes64") !== false) {
				$tx = (int) explode("\t", $line)[1];
			}
		}

		$now = microtime(true);
		if (!isset($last[$interface])) {
			$last[$interface] = ['rx' => $rx, 'tx' => $tx, 'time' => $now];
			return ['rx_bps' => 0, 'tx_bps' => 0];
		}

		$dt = $now - $last[$interface]['time'];
		$drx = $rx - $last[$interface]['rx'];
		$dtx = $tx - $last[$interface]['tx'];
		$last[$interface] = ['rx' => $rx, 'tx' => $tx, 'time' => $now];

		return ['rx_bps' => $drx / $dt, 'tx_bps' => $dtx / $dt];
	}
}
