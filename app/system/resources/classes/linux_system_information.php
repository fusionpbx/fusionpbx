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
 * Description of linux_system_information
 *
 * @author Tim Fry <tim@fusionpbx.com>
 */
class linux_system_information extends system_information {

	public function get_cpu_cores(): int {
		$result = @trim(shell_exec("grep -P '^processor' /proc/cpuinfo"));
		$cpu_cores = count(explode("\n", $result));
		return $cpu_cores;
	}

	//get the CPU details
	public function get_cpu_percent(): float {
		$stat1 = file_get_contents('/proc/stat');
		usleep(500000);
		$stat2 = file_get_contents('/proc/stat');

		$lines1 = explode("\n", trim($stat1));
		$lines2 = explode("\n", trim($stat2));

		$percent_cpu = 0;
		$core_count = 0;

		foreach ($lines1 as $i => $line1) {
			if (strpos($line1, 'cpu') !== 0 || $line1 === 'cpu') continue;

			$parts1 = preg_split('/\s+/', $line1);
			$parts2 = preg_split('/\s+/', $lines2[$i]);

			$total1 = array_sum(array_slice($parts1, 1));
			$total2 = array_sum(array_slice($parts2, 1));

			$idle1 = $parts1[4];
			$idle2 = $parts2[4];

			$total_delta = $total2 - $total1;
			$idle_delta = $idle2 - $idle1;

			$cpu_usage = ($total_delta - $idle_delta) / $total_delta * 100;
			$percent_cpu += $cpu_usage;
			$core_count++;
		}

		return round($percent_cpu / $core_count, 2);
	}

	public function get_uptime() {
		return shell_exec('uptime');
	}
}
