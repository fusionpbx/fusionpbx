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

//convert to a key
	$widget_key = str_replace(' ', '_', strtolower($widget_name));

	//add multi-lingual support
	$language = new text;
	$text = $language->get($settings->get('domain', 'language', 'en-us'), 'app/system');

//get the dashboard label
	$widget_label = $text['label-'.$widget_key] ?? $widget_name;

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
	echo "<div class='hud_content' ".($widget_details_state == "disabled" ?: "onclick=\"$('#hud_system_cpu_status_details').slideToggle('fast');\"").">\n";
	echo "	<span class='hud_title'><a onclick=\"document.location.href='".PROJECT_PATH."/app/system/system.php'\">".escape($widget_label)."</a></span>\n";

	$token = (new token())->create($_SERVER['PHP_SELF']);

	echo "	<input id='token' type='hidden' name='" . $token['name'] . "' value='" . $token['hash'] . "'>\n";

	subscriber::save_token($token, [system_dashboard_service::get_service_name()]);

	if ($widget_chart_type === 'line') { ?>
		<div class='hud_chart' style='width: 90%; height: 85%'><canvas id='system_cpu_status_chart'></canvas></div>
		<!--<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>-->
		<script src="https://cdn.jsdelivr.net/npm/luxon/build/global/luxon.min.js"></script>
		<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon"></script>

		<script>
			const cpu_status_auth_token = {
				name: "<?= $token['name']; ?>",
				hash: "<?= $token['hash']; ?>"
			}

			const cpu_status_subject = '<?php echo system_dashboard_service::CPU_STATUS_TOPIC; ?>';
			const dashboard_cpu_usage_chart_main_color = [
				'<?php echo ($settings->get('theme', 'dashboard_cpu_usage_chart_main_color')[0] ?? '#03c04a'); ?>',
				'<?php echo ($settings->get('theme', 'dashboard_cpu_usage_chart_main_color')[1] ?? '#ff9933'); ?>',
				'<?php echo ($settings->get('theme', 'dashboard_cpu_usage_chart_main_color')[2] ?? '#ea4c46'); ?>'
			];

			function connect_cpu_status_websocket() {
				client = new ws_client(`wss://${window.location.hostname}/websockets/`, cpu_status_auth_token);
				client.ws.addEventListener("open", async () => {
					try {
						console.log('Connected');
						console.log('Requesting authentication');

						// Wait until we are authenticated
						await client.request('authentication');
						console.log('authenticated');

						// Bind event handler so websocket_client.js can call the function when it
						// receives the cpu_status event
						client.onEvent(cpu_status_subject, update_cpu_chart);

					} catch (err) {
						console.error("WS setup failed: ", err);
						return;
					}
				});

				client.ws.addEventListener("close", async () => {
					console.warn("Websocket Disconnected");
				});
			}

			// Function is called automatically by the websocket_client.js when there is a CPU status update
			function update_cpu_chart(payload) {
				const cores = payload.cpu_status?.per_core;
				if (!Array.isArray(cores) || cores.length !== num_cores) return;

				const chart = window.system_cpu_status_chart;
				if (!chart) return;

				const now = Date.now();

				// push one point per core dataset
				chart.data.datasets.forEach((ds, i) => {
					const y = Math.max(0, Math.min(100, Math.round(cores[i] ?? 0)));
					ds.data.push({ x: now, y });
				});

				// Optional: update total CPU %
				const td_cpu_status = document.getElementById('td_system_cpu_status_chart');
				if (td_cpu_status && payload.cpu_status?.total !== undefined) {
					td_cpu_status.textContent = `${Math.round(payload.cpu_status.total)}%`;
				}
			}

			// Set chart options
			const max_points = 60;
			const num_cores = <?= $cpu_cores ?>;

			let cpu_history = Array.from({ length: num_cores }, () => new Array(max_points).fill(null));
			let cpu_index = 0;

			// Color palette (distinct and visually stacked)
			const cpu_colors = ['#00bcd4', '#8bc34a', '#ffc107', '#e91e63'];

			window.system_cpu_status_chart = new Chart(
				document.getElementById('system_cpu_status_chart').getContext('2d'),
				{
					type: 'line',
					data: {
						datasets: Array.from({ length: num_cores }, (_, i) => ({
							label: `CPU ${i}`,
							data: [],
							fill: true,
							borderColor: cpu_colors[i % cpu_colors.length],
							backgroundColor: (cpu_colors[i % cpu_colors.length]) + '33', // light fill
							tension: 0.3,
							pointRadius: 0,
							spanGaps: true,
							// enable stacking
							stack: 'cpu',
						}))
					},
					options: {
						animation: false,
						parsing: { xAxisKey: 'x', yAxisKey: 'y' },
						maintainAspectRatio: false,
						scales: {
							x: {
								type: 'realtime',
								realtime: {
									duration: 60000,   // show last 60s
									refresh: 1000,     // redraw/add points every 1s
									delay: 2000        // draw with slight delay
								},
								grid: { drawOnChartArea: false },
								ticks: { display: false },
								title: { display: false }
							},
							y: {
								beginAtZero: true,
								stacked: true,
								min: 0,
								max: num_cores * 100,
								ticks: {
									stepSize: 100,
									autoSkip: true,
									callback: (v) => v + '%'
								}
							}
						},
						plugins: {
							legend: { display: false },
							tooltip: {
								mode: 'index',
								intersect: false,
								callbacks: {
									label: (ctx) => `${ctx.dataset.label}: ${Math.round(ctx.parsed.y)}%`
								}
							},
						}
					}
				}
			);

			connect_cpu_status_websocket();
		</script>

	<?php }
	//add half doughnut chart
	if (!isset($widget_chart_type) || $widget_chart_type == "doughnut") { ?>
		<div class='hud_chart' style='width: 175px;'><canvas id='system_cpu_status_chart'></canvas></div>

		<script>
			const cpu_status_auth_token = {
				name: "<?= $token['name']; ?>",
				hash: "<?= $token['hash']; ?>"
			}

			const cpu_status_subject = '<?php echo system_dashboard_service::CPU_STATUS_TOPIC; ?>';
			const dashboard_cpu_usage_chart_main_color = [
				'<?php echo ($settings->get('theme', 'dashboard_cpu_usage_chart_main_color')[0] ?? '#03c04a'); ?>',
				'<?php echo ($settings->get('theme', 'dashboard_cpu_usage_chart_main_color')[1] ?? '#ff9933'); ?>',
				'<?php echo ($settings->get('theme', 'dashboard_cpu_usage_chart_main_color')[2] ?? '#ea4c46'); ?>'
			];

			function connect_cpu_status_websocket() {
				client = new ws_client(`wss://${window.location.hostname}/websockets/`, cpu_status_auth_token);
				client.ws.addEventListener("open", async () => {
					try {
						console.log('Connected');
						console.log('Requesting authentication');

						// Wait until we are authenticated
						await client.request('authentication');
						console.log('authenticated');

						// Bind event handler so websocket_client.js can call the function when it
						// receives the cpu_status event
						client.onEvent(cpu_status_subject, update_cpu_chart);

					} catch (err) {
						console.error("WS setup failed: ", err);
						return;
					}
				});

				client.ws.addEventListener("close", async () => {
					console.warn("Websocket Disconnected");
				});
			}

			// Function is called automatically by the websocket_client.js when there is a CPU status update
			function update_cpu_chart(payload) {
				let cpu_status = Math.round(payload.cpu_status.total);
				const chart = window.system_cpu_status_chart;

				if (!chart) return;

				// Update chart data
				cpu_rounded = Math.round(cpu_status);
				chart.data.datasets[0].data = [cpu_rounded, 100 - cpu_rounded];

				// Update color based on threshold
				if (cpu_rounded <= 60) {
					chart.data.datasets[0].backgroundColor[0] = '<?php echo ($settings->get('theme', 'dashboard_cpu_usage_chart_main_color')[0] ?? '#03c04a'); ?>';
				} else if (cpu_rounded <= 80) {
					chart.data.datasets[0].backgroundColor[0] = '<?php echo ($settings->get('theme', 'dashboard_cpu_usage_chart_main_color')[1] ?? '#ff9933'); ?>';
				} else {
					chart.data.datasets[0].backgroundColor[0] = '<?php echo ($settings->get('theme', 'dashboard_cpu_usage_chart_main_color')[2] ?? '#ea4c46'); ?>';
				}

				chart.options.plugins.chart_number_2.text = cpu_rounded;
				chart.update();

				// Update the row data
				const td_cpu_status = document.getElementById('td_system_cpu_status_chart');
				if (!td_cpu_status) { return; }
				td_cpu_status.textContent = `${payload.cpu_status}%`;
			}

			window.system_cpu_status_chart = new Chart(
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
							borderWidth: '<?php echo $settings->get('theme', 'dashboard_chart_border_width'); ?>'
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
								displayColors: false
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
							ctx.fillStyle = '<?php echo $widget_number_text_color; ?>';
							ctx.fillText(options.text + '%', width / 2, top + (height / 2) + 35);
							ctx.save();
						}
					}]
				}
			);

			connect_cpu_status_websocket();
		</script>
	<?php }
	if ($widget_chart_type == "number") {
		echo "<span class='hud_stat'>".round($percent_cpu)."%</span>";
	}
	echo "</div>\n";

	if ($widget_details_state != 'disabled') {
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
				echo "<td id='td_system_cpu_status_chart' valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$percent_cpu."%</td>\n";
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
	}
	echo "</div>\n";

?>
