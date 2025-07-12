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
 * Description of windows_system_information
 *
 * @author Tim Fry <tim@fusionpbx.com>
 */
class windows_system_information extends system_information {

	public function get_disk_usage(): array {
		return [
			'total' => disk_total_space("C:"),
			'free' => disk_free_space("C:"),
			'used' => disk_total_space("C:") - disk_free_space("C:")
		];
	}

	public function get_memory_details(): array {
		$info = shell_exec('wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /Value');
		preg_match('/TotalVisibleMemorySize=(\d+)/', $info, $total);
		preg_match('/FreePhysicalMemory=(\d+)/', $info, $free);
		$total = (int) ($total[1] ?? 0) * 1024;
		$free = (int) ($free[1] ?? 0) * 1024;
		return [
			'total' => $total,
			'available' => $free,
			'used' => $total - $free
		];
	}

	public function get_uptime(): string {
		$boot = shell_exec('wmic os get LastBootUpTime /Value');
		if (preg_match('/LastBootUpTime=(\d{14})/', $boot, $matches)) {
			$boot_time = DateTime::createFromFormat('YmdHis', substr($matches[1], 0, 14));
			if ($boot_time) {
				$interval = (new DateTime())->diff($boot_time);
				return $interval->format('%a days %h hours %i minutes %s seconds');
			}
		}
		return 'unknown';
	}

	public function get_cpu_cores(): int {
		$output = shell_exec('wmic CPU Get NumberOfLogicalProcessors /Value');
		if (preg_match('/NumberOfLogicalProcessors=(\d+)/', $output, $matches)) {
			return (int) $matches[1];
		}
		return 0;
	}

	public function get_cpu_percent(): float {
		$output = shell_exec('powershell -Command "Get-Counter \'\\Processor(_Total)\\% Processor Time\' | Select-Object -ExpandProperty CounterSamples | ForEach-Object { $_.CookedValue }"');
		return is_numeric(trim($output)) ? round((float) $output, 2) : 0.0;
	}

	public function get_cpu_percent_per_core(): array {
		$output = shell_exec('powershell -Command "Get-Counter \'\\Processor(*)\\% Processor Time\' | Select -ExpandProperty CounterSamples | ForEach-Object { $_.InstanceName + \':\' + $_.CookedValue }"');
		$lines = explode("\n", trim($output));
		$results = [];

		foreach ($lines as $line) {
			if (preg_match('/^(\d+):([\d\.]+)/', trim($line), $m)) {
				$results[(int) $m[1]] = round((float) $m[2], 2);
			}
		}

		return $results;
	}

	public function get_network_speed(string $interface = 'eth0'): array {
		// Get first interface if none provided
		if ($interface === null) {
			$list = shell_exec('powershell "Get-NetAdapter | Select -First 1 -ExpandProperty Name"');
			$interface = trim($list);
		}

		$output = shell_exec("powershell -Command \"Get-Counter -Counter '\\Network Interface({$interface})\\Bytes Received/sec','\\Network Interface({$interface})\\Bytes Sent/sec' | ForEach-Object { \$_.CounterSamples.CookedValue }\"");
		$parts = explode("\n", trim($output));
		if (count($parts) >= 2) {
			return [
				'rx_bps' => round((float) trim($parts[0]), 2),
				'tx_bps' => round((float) trim($parts[1]), 2)
			];
		}

		return ['rx_bps' => 0, 'tx_bps' => 0];
	}
}
