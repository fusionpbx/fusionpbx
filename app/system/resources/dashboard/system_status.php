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
		$tmp = shell_exec("df / 2>&1");
		$tmp = explode("\n", $tmp);
		$tmp = preg_replace('!\s+!', ' ', $tmp[1]); // multiple > single space
		$tmp = explode(' ', $tmp);
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
		echo "<th class='hud_heading' width='50%'>".$text['label-item']."</th>\n";
		echo "<th class='hud_heading' style='text-align: right;'>".$text['label-value']."</th>\n";
		echo "</tr>\n";

		//pbx version
			echo "<tr class='tr_link_void'>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text'>".(isset($_SESSION['theme']['title']['text'])?$_SESSION['theme']['title']['text']:'FusionPBX')."</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".software::version()."</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;

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

		//memory usage (for available memory, use "free | awk 'FNR == 3 {print $4/($3+$4)*100}'" instead)
			if (stristr(PHP_OS, 'Linux')) {
				$free = shell_exec("/usr/bin/which free");
				$awk = shell_exec("/usr/bin/which awk");
				$percent_memory = round((float)shell_exec(escapeshellcmd($free." | ".$awk." 'FNR == 3 {print $3/($3+$4)*100}'")), 1);
				if (!empty($percent_memory)) {
					echo "<tr class='tr_link_void'>\n";
					echo "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-memory_usage']."</td>\n";
					echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$percent_memory."%</td>\n";
					echo "</tr>\n";
					$c = ($c) ? 0 : 1;
				}
			}

		//memory available
			if (stristr(PHP_OS, 'Linux')) {
				$result = trim(shell_exec('free -hw | grep \'Mem:\' | cut -d\' \' -f 55-64'));
				if (!empty($result)) {
					echo "<tr class='tr_link_void'>\n";
					echo "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-memory_available']."</td>\n";
					echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$result."</td>\n";
					echo "</tr>\n";
					$c = ($c) ? 0 : 1;
				}
			}

		//disk usage
			if (stristr(PHP_OS, 'Linux')) {
				//calculated above
				if (!empty($percent_disk_usage)) {
					echo "<tr class='tr_link_void'>\n";
					echo "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-disk_usage']."</td>\n";
					echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$percent_disk_usage."%</td>\n";
					echo "</tr>\n";
					$c = ($c) ? 0 : 1;
				}
			}

		//db connections
			switch ($db_type) {
				case 'pgsql':
					$sql = "select count(*) from pg_stat_activity";
					break;
				case 'mysql':
					$sql = "show status where `variable_name` = 'Threads_connected'";
					break;
				default:
					unset($sql);
					if (!empty($db_path) && !empty($dbfilename)) {
						$tmp =  shell_exec("lsof ".realpath($db_path).'/'.$dbfilename);
						$tmp = explode("\n", $tmp);
						$connections = sizeof($tmp) - 1;
					}
			}
			if (!empty($sql)) {
				if (!isset($database)) { $database = new database; }
				$connections = $database->select($sql, null, 'column');
				unset($sql);
			}
			if (!empty($connections)) {
				echo "<tr class='tr_link_void'>\n";
				echo "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-database_connections']."</td>\n";
				echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$connections."</td>\n";
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
