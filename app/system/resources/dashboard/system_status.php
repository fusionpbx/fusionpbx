<?php

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";

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

	//disk usage
	if (PHP_OS == 'FreeBSD' || PHP_OS == 'Linux') {
		$tmp = shell_exec("df /home 2>&1");
		$tmp = explode("\n", $tmp);
		$tmp = preg_replace('!\s+!', ' ', $tmp[1]); // multiple > single space
		$tmp = explode(' ', $tmp);
		foreach ($tmp as $stat) {
			if (substr_count($stat, '%') > 0) { $percent_disk_usage = rtrim($stat,'%'); break; }
		}

		if ($percent_disk_usage != '') {
			//add half doughnut chart
			?>
			<div style='display: flex; flex-wrap: wrap; justify-content: center; padding-bottom: 20px;' onclick="$('#hud_system_status_details').slideToggle('fast');">
				<div><canvas id='system_status_chart' width='175px' height='175px'></canvas></div>
			</div>

			<script>
				var system_status_chart_background_color;
				if ('<?php echo $percent_disk_usage; ?>' <= 80) {
					system_status_chart_background_color = '<?php echo $_SESSION['dashboard']['disk_usage_chart_main_background_color'][0]; ?>';
				} else if ('<?php echo $percent_disk_usage; ?>' <= 90) {
					system_status_chart_background_color = '<?php echo $_SESSION['dashboard']['disk_usage_chart_main_background_color'][1]; ?>';
				} else if ('<?php echo $percent_disk_usage; ?>' > 90) {
					system_status_chart_background_color = '<?php echo $_SESSION['dashboard']['disk_usage_chart_main_background_color'][2]; ?>';
				}

				const system_status_chart = new Chart(
					document.getElementById('system_status_chart').getContext('2d'),
					{
						type: 'doughnut',
						data: {
							datasets: [{
								data: ['<?php echo $percent_disk_usage; ?>', 100 - '<?php echo $percent_disk_usage; ?>'],
								backgroundColor: [system_status_chart_background_color,
								'<?php echo $_SESSION['dashboard']['disk_usage_chart_sub_background_color']['text']; ?>'],
								borderColor: '<?php echo $_SESSION['dashboard']['disk_usage_chart_border_color']['text']; ?>',
								borderWidth: '<?php echo $_SESSION['dashboard']['disk_usage_chart_border_width']['text']; ?>',
								cutout: chart_cutout
							}]
						},
						options: {
							responsive: true,
							maintainAspectRatio: false,
							circumference: 180,
							rotation: 270,
							plugins: {
								chart_counter_2: {
									chart_text: '<?php echo $percent_disk_usage; ?>'
								},
								legend: {
									display: false
								},
								title: {
									display: true,
									text: '<?php echo $text['label-disk_usage']; ?>'
								}
							}
						},
						plugins: [chart_counter_2],
					}
				);
			</script>
			<?php
		}
	}

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
			unset($tmp);
			$cut = shell_exec("/usr/bin/which cut");
			$uptime = trim(shell_exec(escapeshellcmd($cut." -d. -f1 /proc/uptime")));
			$tmp['y'] = floor($uptime/60/60/24/365);
			$tmp['d'] = $uptime/60/60/24%365;
			$tmp['h'] = $uptime/60/60%24;
			$tmp['m'] = $uptime/60%60;
			$tmp['s'] = $uptime%60;
			$uptime = (($tmp['y'] != 0 && $tmp['y'] != '') ? $tmp['y'].'y ' : null);
			$uptime .= (($tmp['d'] != 0 && $tmp['d'] != '') ? $tmp['d'].'d ' : null);
			$uptime .= (($tmp['h'] != 0 && $tmp['h'] != '') ? $tmp['h'].'h ' : null);
			$uptime .= (($tmp['m'] != 0 && $tmp['m'] != '') ? $tmp['m'].'m ' : null);
			$uptime .= (($tmp['s'] != 0 && $tmp['s'] != '') ? $tmp['s'].'s' : null);
			if ($uptime != '') {
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
			$percent_memory = round(shell_exec(escapeshellcmd($free." | ".$awk." 'FNR == 3 {print $3/($3+$4)*100}'")), 1);
			if ($percent_memory != '') {
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
			if ($result != '') {
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
			if ($percent_disk_usage != '') {
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
				if ($db_path != '' && $dbfilename != '') {
					$tmp =  shell_exec("lsof ".realpath($db_path).'/'.$dbfilename);
					$tmp = explode("\n", $tmp);
					$connections = sizeof($tmp) - 1;
				}
		}
		if ($sql != '') {
			if (!isset($database)) { $database = new database; }
			$connections = $database->select($sql, null, 'column');
			unset($sql);
		}
		if ($connections != '') {
			echo "<tr class='tr_link_void'>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-database_connections']."</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$connections."</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}

	//channel count
		if ($fp) {
			$tmp = event_socket_request($fp, 'api status');
			$matches = Array();
			preg_match("/(\d+)\s+session\(s\)\s+\-\speak/", $tmp, $matches);
			$channels = $matches[1] ? $matches[1] : 0;
			$tr_link = "href='".PROJECT_PATH."/app/calls_active/calls_active.php'";
			echo "<tr ".$tr_link.">\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-channels']."</a></td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$channels."</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}

	//registration count
		if ($fp && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/registrations/")) {
			$registration = new registrations;
			$registrations = $registration->count();
			$tr_link = "href='".PROJECT_PATH."/app/registrations/registrations.php'";
			echo "<tr ".$tr_link.">\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-registrations']."</a></td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$registrations."</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}

	echo "</table>\n";
	echo "</div>";
	$n++;

	echo "<span class='hud_expander' onclick=\"$('#hud_system_status_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
	echo "</div>\n";
?>
