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
 * Description of darwin_system_information
 *
 * @author Tim Fry <tim@fusionpbx.com>
 */
class darwin_system_information extends system_information {

	public function get_disk_usage(): array {
		return [
			'total' => disk_total_space("/"),
			'free' => disk_free_space("/"),
			'used' => disk_total_space("/") - disk_free_space("/")
		];
	}

	public function get_memory_details(): array {
		$total = (int) shell_exec("sysctl -n hw.memsize");
		$vm_stat = shell_exec("vm_stat");
		preg_match('/Pages free:\s+(\d+)\./', $vm_stat, $free);
		preg_match('/Pages inactive:\s+(\d+)\./', $vm_stat, $inactive);
		preg_match('/Page size of (\d+) bytes/', $vm_stat, $pagesize);

		$page_size = (int) ($pagesize[1] ?? 4096);
		$free_pages = (int) ($free[1] ?? 0);
		$inactive_pages = (int) ($inactive[1] ?? 0);

		$available = ($free_pages + $inactive_pages) * $page_size;
		$used = $total - $available;

		return [
			'total' => $total,
			'available' => $available,
			'used' => $used
		];
	}

	public function get_uptime(): string {
		$boot_time = (int) shell_exec("sysctl -n kern.boottime | awk '{print $4}' | sed 's/,//'");

		if ($boot_time > 0) {
			$uptime = time() - $boot_time;
			return gmdate("H:i:s", $uptime);
		}
		return 'unknown';
	}

	public function get_cpu_cores(): int {
		return (int) shell_exec("sysctl -n hw.ncpu");
	}

	public function get_cpu_percent(): float {
		$output = shell_exec("top -l 2 | grep 'CPU usage' | tail -n 1");
		if (preg_match('/(\d+\.\d+)% user, (\d+\.\d+)% sys, (\d+\.\d+)% idle/', $output, $matches)) {
			$user = (float) $matches[1];
			$sys = (float) $matches[2];
			return round($user + $sys, 2);
		}
		return 0.0;
	}

	public function get_cpu_percent_per_core(): array {
		static $last = [];
		$output = shell_exec('sysctl -n kern.cp_times');
		$parts = array_map('intval', preg_split('/\s+/', trim($output)));
		$num_cores = count($parts) / 5;
		$results = [];

		for ($core = 0; $core < $num_cores; $core++) {
			$offset = $core * 5;
			$total = array_sum(array_slice($parts, $offset, 5));
			$idle = $parts[$offset + 4];

			if (!isset($last[$core])) {
				$last[$core] = ['total' => $total, 'idle' => $idle];
				$results[$core] = 0;
			} else {
				$dt = $total - $last[$core]['total'];
				$di = $idle - $last[$core]['idle'];
				$results[$core] = $dt > 0 ? round((1 - $di / $dt) * 100, 2) : 0;
				$last[$core] = ['total' => $total, 'idle' => $idle];
			}
		}

		return $results;
	}

	public function get_network_speed(string $interface = 'em0'): array {
		static $last = [];

		$output = shell_exec("netstat -bI {$interface} 2>/dev/null");
		$lines = explode("\n", trim($output));
		if (count($lines) < 2)
			return ['rx_bps' => 0, 'tx_bps' => 0];

		$cols = preg_split('/\s+/', $lines[1]);
		$rx = (int) $cols[6];
		$tx = (int) $cols[9];
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
