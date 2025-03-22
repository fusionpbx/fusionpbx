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
	$text = $language->get($_SESSION['domain']['language']['code'], 'app/system');

//system cpu status
	echo "<div class='hud_box'>\n";

//set the row style class names
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

//show the content
	echo "<div class='hud_content' ".($dashboard_details_state == "disabled" ?: "onclick=\"$('#hud_system_cpu_status_details').slideToggle('fast'); toggle_grid_row_end('".$dashboard_name."')\"").">\n";
	echo "	<span class='hud_title'><a onclick=\"document.location.href='".PROJECT_PATH."/app/system/system.php'\">".$text['label-cpu_usage']."</a></span>\n";

//add half doughnut chart
	if (!isset($dashboard_chart_type) || $dashboard_chart_type == "doughnut") {
		?>
		<div class='hud_chart' style='width: 175px;'><canvas id='system_cpu_status_chart'></canvas></div>

		<script>
			const system_cpu_status_chart = new Chart(
				document.getElementById('system_cpu_status_chart').getContext('2d'),
				{
					type: 'doughnut',
					data: {
						datasets: [{
							data: ['<?php echo $percent_cpu; ?>', 100 - '<?php echo $percent_cpu; ?>'],
							backgroundColor: [
								<?php
								if ($percent_cpu <= 60) {
									echo "'".($settings->get('theme', 'dashboard_cpu_usage_chart_main_color')[0] ?? '#03c04a')."',\n";
								} else if ($percent_cpu <= 80) {
									echo "'".($settings->get('theme', 'dashboard_cpu_usage_chart_main_color')[1] ?? '#ff9933')."',\n";
								} else if ($percent_cpu > 80) {
									echo "'".($settings->get('theme', 'dashboard_cpu_usage_chart_main_color')[2] ?? '#ea4c46')."',\n";
								}
								?>
								'<?php echo ($settings->get('theme', 'dashboard_cpu_usage_chart_sub_color') ?? '#d4d4d4'); ?>'
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
								text: '<?php echo round($percent_cpu); ?>'
							},
							tooltip: {
								yAlign: 'bottom',
								displayColors: false,
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
		echo "<span class='hud_stat'>".round($percent_cpu)."%</span>";
	}
	echo "</div>\n";

	if ($dashboard_details_state != 'disabled') {
		echo "<div class='hud_details hud_box' id='hud_system_cpu_status_details'>";
		echo "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
		echo "<tr>\n";
		echo "<th class='hud_heading' width='50%'>".$text['label-name']."</th>\n";
		echo "<th class='hud_heading' style='text-align: right;'>".$text['label-value']."</th>\n";
		echo "</tr>\n";

		if (PHP_OS == 'FreeBSD' || PHP_OS == 'Linux') {
			if (!empty($percent_cpu)) {
				echo "<tr class='tr_link_void'>\n";
				echo "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-cpu_usage']."</td>\n";
				echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$percent_cpu."%</td>\n";
				echo "</tr>\n";
				$c = ($c) ? 0 : 1;
			}

			if (!empty($cpu_cores)) {
				echo "<tr class='tr_link_void'>\n";
				echo "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-cpu_cores']."</td>\n";
				echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$cpu_cores."</td>\n";
				echo "</tr>\n";
				$c = ($c) ? 0 : 1;
			}

			echo "<tr class='tr_link_void'>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-load_average']." (1)</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$load_average[0]."</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;

			echo "<tr class='tr_link_void'>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-load_average']." (5)</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$load_average[1]."</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;

			echo "<tr class='tr_link_void'>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-load_average']." (15)</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$load_average[2]."</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}

		echo "</table>\n";
		echo "</div>";
		//$n++;

		echo "<span class='hud_expander' onclick=\"$('#hud_system_cpu_status_details').slideToggle('fast'); toggle_grid_row_end('".$dashboard_name."')\"><span class='fas fa-ellipsis-h'></span></span>";
	}
	echo "</div>\n";

?>
