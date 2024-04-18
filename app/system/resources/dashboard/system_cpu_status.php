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

//add half doughnut chart
	echo "	<div style='display: flex; flex-wrap: wrap; justify-content: center; padding-bottom: 20px;' onclick=\"$('#hud_system_cpu_status_details').slideToggle('fast');\">\n";
	echo "		<span class='hud_title' style='color: ".$dashboard_heading_text_color.";' onclick=\"document.location.href='".PROJECT_PATH."/app/system/system.php'\">".$text['label-cpu_usage']."</span>\n";

	if ($dashboard_chart_type == "doughnut") {
		?>
		<div style='width: 175px; height: 143px;'><canvas id='system_cpu_status_chart'></canvas></div>

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
									echo "'".$_SESSION['dashboard']['cpu_usage_chart_main_background_color'][0]."',\n";
								} else if ($percent_cpu <= 80) {
									echo "'".$_SESSION['dashboard']['cpu_usage_chart_main_background_color'][1]."',\n";
								} else if ($percent_cpu > 80) {
									echo "'".$_SESSION['dashboard']['cpu_usage_chart_main_background_color'][2]."',\n";
								}
								?>
								'<?php echo $_SESSION['dashboard']['cpu_usage_chart_sub_background_color']['text']; ?>'
							],
							borderColor: '<?php echo $_SESSION['dashboard']['cpu_usage_chart_border_color']['text']; ?>',
							borderWidth: '<?php echo $_SESSION['dashboard']['cpu_usage_chart_border_width']['text']; ?>'
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
							ctx.font = (chart_text_size - 7) + 'px ' + chart_text_font;
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
	if ($dashboard_chart_type == "none") {
		echo "	<span class='hud_stat' style='color: ".$dashboard_number_text_color.";'>".round($percent_cpu)."%</span>";
	}
	echo "	</div>\n";

//show the content
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

	echo "<span class='hud_expander' onclick=\"$('#hud_system_cpu_status_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
	echo "</div>\n";

?>
