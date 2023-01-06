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
			$result = trim(shell_exec("dmesg | grep -i --max-count 1 CPUs | sed 's/[^0-9]*//g'"));
			$cpu_cores = trim($result);
		}
		if (stristr(PHP_OS, 'Linux')) {
			$result = trim(shell_exec("grep -P '^processor' /proc/cpuinfo"));
			$cpu_cores = count(explode("\n", $result));
		}
		if ($cpu_cores > 1) { $percent_cpu = $percent_cpu / $cpu_cores; }
		$percent_cpu = round($percent_cpu, 2);

		//uptime
		$result = shell_exec('uptime');
		$load_average = sys_getloadavg();
		
	}

//add half doughnut chart
	?>
	<div style='display: flex; flex-wrap: wrap; justify-content: center; padding-bottom: 20px'>
		<div><canvas id='system_cpu_status_chart' width='175px' height='175px' ></canvas></div>
	</div>

	<script>
		var system_cpu_status_chart_background_color;
		if ('<?php echo $percent_cpu; ?>' <= 60) {
			system_cpu_status_chart_background_color = '<?php echo $_SESSION['dashboard']['cpu_usage_chart_main_background_color'][0]; ?>';
		} else if ('<?php echo $percent_cpu; ?>' <= 80) {
			system_cpu_status_chart_background_color = '<?php echo $_SESSION['dashboard']['cpu_usage_chart_main_background_color'][1]; ?>';
		} else if ('<?php echo $percent_cpu; ?>' > 80) {
			system_cpu_status_chart_background_color = '<?php echo $_SESSION['dashboard']['cpu_usage_chart_main_background_color'][2]; ?>';
		}

		const system_cpu_status_chart = new Chart(
			document.getElementById('system_cpu_status_chart').getContext('2d'),
			{
				type: 'doughnut',
				data: {
					datasets: [{
						data: ['<?php echo $percent_cpu; ?>', 100 - '<?php echo $percent_cpu; ?>'],
						backgroundColor: [
							system_cpu_status_chart_background_color,
							'<?php echo $_SESSION['dashboard']['cpu_usage_chart_sub_background_color']['text']; ?>'
						],
						borderColor: '<?php echo $_SESSION['dashboard']['cpu_usage_chart_border_color']['text']; ?>',
						borderWidth: '<?php echo $_SESSION['dashboard']['cpu_usage_chart_border_width']['text']; ?>',
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
							chart_text: '<?php echo $percent_cpu; ?>'
						},
						legend: {
							display: false,
						},
						tooltip: {
							yAlign: 'bottom',
							displayColors: false,
						},
						title: {
							display: true,
							text: '<?php echo $text['label-cpu_usage']; ?>'
						}
					}
				},
				plugins: [chart_counter_2],
			}
		);
	</script>

<?php

//show the content
	echo "<div class='hud_details hud_box' id='hud_system_cpu_status_details'>";
	echo "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "<tr>\n";
	echo "<th class='hud_heading' width='50%'>".$text['label-name']."</th>\n";
	echo "<th class='hud_heading' style='text-align: right;'>".$text['label-value']."</th>\n";
	echo "</tr>\n";

	if (PHP_OS == 'FreeBSD' || PHP_OS == 'Linux') {
		if ($percent_cpu != '') {
			echo "<tr class='tr_link_void'>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-cpu_usage']."</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$percent_cpu."%</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}

		if ($cpu_cores != '') {
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
	$n++;

	echo "<span class='hud_expander' onclick=\"$('#hud_system_cpu_status_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
	echo "</div>\n";

?>
