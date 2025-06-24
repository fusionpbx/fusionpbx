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
}
