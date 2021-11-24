<?php

//includes
	require_once "root.php";
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

	//cpu usage
	if (stristr(PHP_OS, 'Linux')) {
		$result = shell_exec('ps -A -o pcpu');
		$percent_cpu = 0;
		foreach (explode("\n", $result) as $value) {
			if (is_numeric($value)) { $percent_cpu = $percent_cpu + $value; }
		}
		$result = trim(shell_exec("grep -P '^processor' /proc/cpuinfo"));
		$cores = count(explode("\n", $result));
		if ($percent_cpu > 1) { $percent_cpu = $percent_cpu / $cores; }
		$percent_cpu = round($percent_cpu, 2);
	}


			//add half doughnut chart
			?>
			<div style='display: flex; flex-wrap: wrap; justify-content: center; padding-bottom: 8px'>
				<div style='width: 175px; height: 175px; margin: 0 auto;'><canvas id='system_cpu_status_chart'></canvas></div>
			</div>
	
			<script>
				var system_cpu_status_chart_context = document.getElementById('system_cpu_status_chart').getContext('2d');
	
				var system_cpu_status_chart_background_color;
				if ('<?php echo $percent_cpu; ?>' <= 50) {
					system_cpu_status_chart_background_color = '<?php echo $_SESSION['dashboard']['cpu_usage_chart_main_background_color'][0]; ?>';
				} else if ('<?php echo $percent_cpu; ?>' <= 70 && '<?php echo $percent_cpu; ?>' > 50) {
					system_cpu_status_chart_background_color = '<?php echo $_SESSION['dashboard']['cpu_usage_chart_main_background_color'][1]; ?>';
				} else if ('<?php echo $percent_cpu; ?>' > 70) {
					system_cpu_status_chart_background_color = '<?php echo $_SESSION['dashboard']['cpu_usage_chart_main_background_color'][2]; ?>';
				}
	
				const system_cpu_status_chart_data = {
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
				};
	
				const system_cpu_status_chart_config = {
					type: 'doughnut',
					data: system_cpu_status_chart_data,
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
								text: '<?php echo $text['label-processor_usage']; ?>'
							}
						}
					},
					plugins: [chart_counter_2],
				};
	
				const system_cpu_status_chart = new Chart(
					system_cpu_status_chart_context,
					system_cpu_status_chart_config
				);
			</script>
			<?php
	echo "<div class='hud_details hud_box' id='hud_system_cpu_status_details'>";
	echo "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "<tr>\n";
	echo "<th class='hud_heading' width='50%'>".$text['label-item']."</th>\n";
	echo "<th class='hud_heading' style='text-align: right;'>".$text['label-value']."</th>\n";
	echo "</tr>\n";

	//cpu usage
		if (stristr(PHP_OS, 'Linux')) {
			if ($percent_cpu != '') {
				echo "<tr class='tr_link_void'>\n";
				echo "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-processor_usage']."</td>\n";
				echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$percent_cpu."%</td>\n";
				echo "</tr>\n";
				$c = ($c) ? 0 : 1;
			}
		}

	echo "</table>\n";
	echo "</div>";
	$n++;

	echo "<span class='hud_expander' onclick=\"$('#hud_system_cpu_status_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
	echo "</div>\n";
?>
