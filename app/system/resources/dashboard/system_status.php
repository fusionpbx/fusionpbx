<?php

//includes files
	require_once dirname(__DIR__, 4) . "/resources/require.php";

//check permisions
	require_once "resources/check_auth.php";
	if (permission_exists('xml_cdr_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get($_SESSION['domain']['language']['code'], 'core/user_settings');

//system status
	echo "<div class='hud_box'>\n";

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//get the CPU details
	if (stristr(PHP_OS, 'BSD') || stristr(PHP_OS, 'Linux')) {

		$result = shell_exec('ps -A -o pcpu');
		$percent_cpu = 0;
		foreach (explode("\n", $result) as $value) {
			if (is_numeric($value)) { $percent_cpu = $percent_cpu + $value; }
		}
		if (stristr(PHP_OS, 'BSD')) {
			$result = shell_exec("dmesg | grep -i --max-count 1 CPUs | sed 's/[^0-9]*//g'");
			$cpu_cores = trim($result);
		}
		if (stristr(PHP_OS, 'Linux')) {
			$result = @trim(shell_exec("grep -P '^processor' /proc/cpuinfo"));
			$cpu_cores = count(explode("\n", $result));
		}
		if ($cpu_cores > 1) { $percent_cpu = $percent_cpu / $cpu_cores; }
		$percent_cpu = round($percent_cpu, 2);

		//uptime
		$result = shell_exec('uptime');
		$load_average = sys_getloadavg();

	}

//get memory details
	$memory_details = get_memory_details();

//disk usage
	if (PHP_OS == 'FreeBSD' || PHP_OS == 'Linux') {
		$tmp = shell_exec("df -h / 2>&1"); // Added -h for human-readable sizes
		$tmp = explode("\n", $tmp);
		$tmp = preg_replace('!\s+!', ' ', $tmp[1]); // multiple > single space
		$tmp = explode(' ', $tmp);

		// Extract values (columns may vary slightly by OS)
		$used_space = $tmp[2] ?? '-';
		$total_space = $tmp[1] ?? '-';
		$percent_disk_usage = '';
		
		foreach ($tmp as $stat) {
			if (substr_count($stat, '%') > 0) { $percent_disk_usage = rtrim($stat,'%'); break; }
		}
	}


//show the results
	echo "	<div class='hud_content' ".($dashboard_details_state == "disabled" ?: "onclick=\"$('#hud_system_status_details').slideToggle('fast'); toggle_grid_row_end('".$dashboard_name."')\"").">\n";
	echo "		<span class='hud_title'><a onclick=\"document.location.href='".PROJECT_PATH."/app/system/system.php'\">".$text['label-system_status']."</a></span>\n";

	if ($dashboard_chart_type == "doughnut") {
		?>
		<div class='hud_chart' style='width: 175px;'><canvas id='system_status_chart'></canvas></div>

		<script>
			const system_status_chart = new Chart(
				document.getElementById('system_status_chart').getContext('2d'),
				{
					type: 'doughnut',
					data: {
						datasets: [{
							data: ['<?php echo $percent_disk_usage; ?>', 100 - '<?php echo $percent_disk_usage; ?>'],
							backgroundColor: [
								<?php
								if ($percent_disk_usage <= 80) {
									echo "'".($settings->get('theme', 'dashboard_disk_usage_chart_main_color')[0] ?? '#03c04a')."',\n";
								} else if ($percent_disk_usage <= 90) {
									echo "'".($settings->get('theme', 'dashboard_disk_usage_chart_main_color')[1] ?? '#ff9933')."',\n";
								} else if ($percent_disk_usage > 90) {
									echo "'".($settings->get('theme', 'dashboard_disk_usage_chart_main_color')[2] ?? '#ea4c46')."',\n";
								}
								?>
								'<?php echo ($settings->get('theme', 'dashboard_disk_usage_chart_sub_color') ?? '#d4d4d4'); ?>'
							],
							borderColor: '<?php echo $settings->get('theme', 'dashboard_chart_border_color'); ?>',
							borderWidth: '<?php echo $settings->get('theme', 'dashboard_chart_border_width'); ?>',
						}]
					},
					options: {
						circumference: 180,
						rotation: 270,
						plugins: {
							chart_number_2: {
								text: '<?php echo round($percent_disk_usage); ?>'
							}
						}
					},
					plugins: [{
						id: 'chart_number_2',
						beforeDraw(chart, args, options){
							const {ctx, chartArea: {top, right, bottom, left, width, height} } = chart;
							ctx.font = chart_text_size + ' ' + chart_text_font;
							ctx.textBaseline = 'middle';
							ctx.textAlign = 'center';
							ctx.fillStyle = '<?php echo $dashboard_number_text_color; ?>';
							ctx.fillText(options.text + '%', width / 2, top + (height / 2) + 35);
							ctx.save();
						}
					}]
				}
			);
		</script>
		<?php
	}

	if ($dashboard_chart_type == "number") {
		echo "	<span class='hud_stat'>".round($percent_disk_usage)."%</span>";
	}
	if (!isset($dashboard_chart_type) || $dashboard_chart_type == "progress_bar") {
		//cpu usage
		if ($dashboard_row_span > 1) {
			echo "	<span class='hud_title cpu_usage' style='text-align: left; font-size: 11px; line-height: 1.8; font-weight: unset; padding-left: 10%;'>".$text['label-processor_usage']."</span>\n";
			echo "	<div class='progress_container' style='width: 80%; height: 15px; border-radius: 10px; background: ".($settings->get('theme', 'dashboard_cpu_usage_chart_sub_color') ?? '#d4d4d4').";'>\n";
			echo "		<div class='progress_bar' style='width: ".($percent_cpu > 100 ? 100 : $percent_cpu)."%; height: 15px; border-radius: 10px; font-size: x-small; color: ".$row['dashboard_number_text_color']."; background: ".($settings->get('theme', 'dashboard_cpu_usage_chart_main_color') ?? '#03c04a').";'>".($percent_cpu > 100 ? 100 : round($percent_cpu))."%</div>\n";
			echo "	</div>\n";
			echo "	<div style='width: 100%; height: 15px'>&nbsp;</div>\n";
		}

		//disk usage
		if ($dashboard_row_span >= 1) {
			echo "	<span class='hud_title' style='text-align: left; font-size: 11px; line-height: 1.8; font-weight: unset; padding-left: 10%;'>".$text['label-disk_usage']."</span>\n";
			echo "	<div class='progress_container' style='width: 80%; height: 15px; border-radius: 10px; background: ".($settings->get('theme', 'dashboard_disk_usage_chart_sub_color') ?? '#d4d4d4').";'>\n";
			echo "		<div class='progress_bar' style='width: ".$percent_disk_usage."%; height: 15px; border-radius: 10px; font-size: x-small; color: ".$row['dashboard_number_text_color']."; background: ".($settings->get('theme', 'dashboard_disk_usage_chart_main_color') ?? '#03c04a').";'>".round($percent_disk_usage)."%</div>\n";
			echo "	</div>\n";
			echo "	<div style='width: 100%; height: 15px'>&nbsp;</div>\n";
		}

		//percent memory
		if ($dashboard_row_span > 1) {
			echo "	<span class='hud_title' style='text-align: left; font-size: 11px; line-height: 1.8; font-weight: unset; padding-left: 10%;'>".$text['label-memory_usage']."</span>\n";
			echo "	<div class='progress_container' style='width: 80%; height: 15px; border-radius: 10px; background: ".($settings->get('theme', 'dashboard_disk_usage_chart_sub_color') ?? '#d4d4d4').";'>\n";
			echo "		<div class='progress_bar' style='width: ".round((int)$memory_details['memory_percent'])."%; height: 15px; border-radius: 10px; font-size: x-small; color: ".$row['dashboard_number_text_color']."; background: ".($settings->get('theme', 'dashboard_disk_usage_chart_main_color') ?? '#03c04a').";'>".round((int)$memory_details['memory_percent'])."%</div>\n";
			echo "	</div>\n";
		}
	}

	echo "	</div>\n";


	if ($dashboard_details_state != 'disabled') {
		echo "<div class='hud_details hud_box' id='hud_system_status_details'>";
		echo "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
		echo "<tr>\n";
		echo "<th class='hud_heading' width='35%'>".$text['label-item']."</th>\n";
		echo "<th class='hud_heading' style='text-align: right;'>".$text['label-value']."</th>\n";
		echo "</tr>\n";

		//pbx version
			echo "<tr class='tr_link_void'>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text'>".(isset($_SESSION['theme']['title']['text'])?$_SESSION['theme']['title']['text']:'FusionPBX')."</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".software::version()."</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;

		// OS Type and Version (for Linux)
			if (stristr(PHP_OS, 'Linux')) {
				// Try to get pretty OS name
				$os_info = '';
				if (file_exists('/etc/os-release')) {
					$os_release = parse_ini_file('/etc/os-release');
					$os_info = $os_release['PRETTY_NAME'] ?? '';
				} 
				// Fallback to basic uname info
				elseif (function_exists('php_uname')) {
					$os_info = php_uname('s') . ' ' . php_uname('r'); // e.g. "Linux 5.10.0"
				}
				
				// Clean up the output
				$os_info = str_replace('"', '', $os_info); // Remove quotes if present
				
				if (!empty($os_info)) {
					echo "<tr class='tr_link_void'>\n";
					echo "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-os_version']."</td>\n";
					echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$os_info."</td>\n";
					echo "</tr>\n";
					$c = ($c) ? 0 : 1;
				}
			}

		//os uptime
			if (stristr(PHP_OS, 'Linux')) {
				$prefix = 'up ';
				$linux_uptime = shell_exec('uptime  -p');
				$uptime = substr($linux_uptime, strlen($prefix));
				if (!empty($uptime)) {
					echo "<tr class='tr_link_void'>\n";
					echo "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-system_uptime']."</td>\n";
					echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$uptime."</td>\n";
					echo "</tr>\n";
					$c = ($c) ? 0 : 1;
				}
			}

		//memory usage
			if (stristr(PHP_OS, 'Linux')) {
				// Get memory usage percentage
				$meminfo = shell_exec('free -b | grep Mem');
				if (!empty($meminfo)) {
					$meminfo = preg_replace('/\s+/', ' ', trim($meminfo));
					$parts = explode(' ', $meminfo);
					
					$total = $parts[1];
					$used = $parts[2];
					$percent_memory = round(($used / $total) * 100, 1);

					// Set style color based on thresholds
					$style = ($percent_memory > 90) ? "color: red;" : (($percent_memory > 75) ? "color: orange;" : "");
					
					// Format with used/total (e.g. "40% (3.2G/8G)")
					$total_h = round($total / (1024*1024*1024), 1) . 'G';
					$used_h = round($used / (1024*1024*1024), 1) . 'G';
					
					echo "<tr class='tr_link_void'>\n";
					echo "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-memory_usage']."</td>\n";
					echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right; $style'>".$percent_memory."% (".$used_h." / ".$total_h.")"."</td>\n";
					echo "</tr>\n";
					$c = ($c) ? 0 : 1;
				}
			}

		//swap usage
			if (stristr(PHP_OS, 'Linux')) {
				$swapinfo = shell_exec('free -b | grep Swap');
				if (!empty($swapinfo)) {
					$swapinfo = preg_replace('/\s+/', ' ', trim($swapinfo));
					$parts = explode(' ', $swapinfo);
					
					$swap_total = $parts[1];
					$swap_used = $parts[2];
					
					// Only show swap if it exists (total > 0)
					if ($swap_total > 0) {
						$percent_swap = round(($swap_used / $swap_total) * 100, 1);
						$swap_total_h = round($swap_total / (1024*1024*1024), 1) . 'G';
						$swap_used_h = round($swap_used / (1024*1024*1024), 1) . 'G';

						// Set style color based on thresholds
						$style = ($percent_swap > 90) ? "color: red;" : (($percent_swap > 75) ? "color: orange;" : "");
						
						echo "<tr class='tr_link_void'>\n";
						echo "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-swap_usage']."</td>\n";
						echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right; $style'>".$percent_swap."% (".$swap_used_h." / ".$swap_total_h.")"."</td>\n";
						echo "</tr>\n";
						$c = ($c) ? 0 : 1;
					}
				}
			}

		//disk usage display
			if (stristr(PHP_OS, 'Linux') || stristr(PHP_OS, 'FreeBSD')) {
					  
				if (!empty($percent_disk_usage) && $used_space != '-' && $total_space != '-') {
					// Set style color based on thresholds
					$style = ($percent_disk_usage > 90) ? "color: red;" : (($percent_disk_usage > 75) ? "color: orange;" : "");
					
					echo "<tr class='tr_link_void'>\n";
					echo "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-disk_usage']."</td>\n";
					echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right; $style'>".$percent_disk_usage."% (".$used_space." / ".$total_space.")"."</td>\n";
					echo "</tr>\n";
					$c = ($c) ? 0 : 1;
				}
			}

		//db connections
			switch ($db_type) {
				case 'pgsql':
					$sql_current = "SELECT count(*) FROM pg_stat_activity";
					$sql_max = "SHOW max_connections";
					break;
				case 'mysql':
					$sql_current = "SHOW STATUS WHERE `variable_name` = 'Threads_connected'";
					$sql_max = "SHOW VARIABLES LIKE 'max_connections'";
					break;
				default:
					unset($sql_current, $sql_max);
					if (!empty($db_path) && !empty($dbfilename)) {
						$tmp = shell_exec("lsof " . realpath($db_path) . '/' . $dbfilename);
						$tmp = explode("\n", $tmp);
						$connections = sizeof($tmp) - 1;
					}
			}
	
			if (!empty($sql_current) && !empty($sql_max)) {
				if (!isset($database)) { $database = new database; }
				
				// Get current connections
				$current_connections = $database->select($sql_current, null, 'column');
				
				// Get max connections (handles both PostgreSQL & MySQL)
				$max_result = $database->select($sql_max, null, ($db_type == 'pgsql') ? 'column' : 'row');
				$max_connections = ($db_type == 'mysql') ? $max_result['Value'] : $max_result;
				
				// Format as "current/max"
				$connections = ($current_connections !== false && $max_connections !== false) 
					? "Current: " . $current_connections . ", Max: " . $max_connections 
					: "N/A";
				
				unset($sql_current, $sql_max);
			}
	
			if (!empty($connections)) {
				// Set style color based on thresholds
				$ratio = $current_connections / $max_connections;
				$style = ($ratio > 0.9) ? "color: red;" : (($ratio > 0.75) ? "color: orange;" : "");
				
				echo "<tr class='tr_link_void'>\n";
				echo "<td valign='top' class='" . $row_style[$c] . " hud_text'>" . $text['label-database_connections'] . "</td>\n";
				echo "<td valign='top' class='" . $row_style[$c] . " hud_text' style='text-align: right; $style'>" . $connections . "</td>\n";
				echo "</tr>\n";
				$c = ($c) ? 0 : 1;
			}

		//channel count
			if ($esl == null) {
				$esl = event_socket::create();
			}
			if ($esl->is_connected()) {
				$tmp = event_socket::api('status');
				$matches = Array();
				preg_match("/(\d+)\s+session\(s\)\s+\-\speak/", $tmp, $matches);
				$channels = !empty($matches[1]) ? $matches[1] : 0;
				$tr_link = "href='".PROJECT_PATH."/app/calls_active/calls_active.php'";
				echo "<tr ".$tr_link.">\n";
				echo "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-channels']."</a></td>\n";
				echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$channels."</td>\n";
				echo "</tr>\n";
				$c = ($c) ? 0 : 1;
			}

		echo "</table>\n";
		echo "</div>";
		//$n++;

		echo "<span class='hud_expander' onclick=\"$('#hud_system_status_details').slideToggle('fast'); toggle_grid_row_end('".$dashboard_name."')\"><span class='fas fa-ellipsis-h'></span></span>";
	}
	echo "</div>\n";

?>
