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

	//system network status
	echo "<div class='hud_box'>\n";

	//set the row style class names
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	//get the CPU details
	if (stristr(PHP_OS, 'BSD') || stristr(PHP_OS, 'Linux')) {

		$result = shell_exec('ps -A -o pnetwork');
		$percent_network = 0;
		foreach (explode("\n", $result) as $value) {
			if (is_numeric($value)) { $percent_network = $percent_network + $value; }
		}
		if (stristr(PHP_OS, 'BSD')) {
			$result = shell_exec("dmesg | grep -i --max-count 1 CPUs | sed 's/[^0-9]*//g'");
			$network_cores = trim($result);
		}
		if (stristr(PHP_OS, 'Linux')) {
			$result = @trim(shell_exec("grep -P '^processor' /proc/networkinfo"));
			$network_cores = count(explode("\n", $result));
		}
		if ($network_cores > 1) { $percent_network = $percent_network / $network_cores; }
		$percent_network = round($percent_network, 2);

		//uptime
		$result = shell_exec('uptime');
		$load_average = sys_getloadavg();

	}

	//show the content
	echo "<div class='hud_content' ".($dashboard_details_state == "disabled" ?: "onclick=\"$('#hud_system_network_status_details').slideToggle('fast'); toggle_grid_row_end('".$dashboard_name."')\"").">\n";
	echo "	<span class='hud_title'><a onclick=\"document.location.href='".PROJECT_PATH."/app/system/system.php'\">".$text['label-network_usage']."</a></span>\n";

	$token = (new token())->create($_SERVER['PHP_SELF']);

	echo "	<input id='token' type='hidden' name='" . $token['name'] . "' value='" . $token['hash'] . "'>\n";

	subscriber::save_token($token, [system_dashboard_service::get_service_name()]);

	if ($dashboard_chart_type === 'line') { ?>
		<div class='hud_chart' style='width: 90%;'><canvas id='system_network_status_chart'></canvas></div>

		<script>
			const network_status_auth_token = {
				name: "<?= $token['name']; ?>",
				hash: "<?= $token['hash']; ?>"
			}

			const network_status_subject = '<?php echo system_dashboard_service::NETWORK_STATUS_TOPIC; ?>';
			const dashboard_network_usage_chart_main_color = [
				'<?php echo ($settings->get('theme', 'dashboard_network_usage_chart_main_color')[0] ?? '#03c04a'); ?>',
				'<?php echo ($settings->get('theme', 'dashboard_network_usage_chart_main_color')[1] ?? '#ff9933'); ?>',
				'<?php echo ($settings->get('theme', 'dashboard_network_usage_chart_main_color')[2] ?? '#ea4c46'); ?>'
			];

			function connect_network_status_websocket() {
				network_client = new ws_client(`wss://${window.location.hostname}/websockets/`, network_status_auth_token);
				network_client.ws.addEventListener("open", async () => {
					try {
						console.log('Connected');
						console.log('Requesting authentication');

						// Wait until we are authenticated
						await network_client.request('authentication');
						console.log('authenticated');

						// Bind event handler so websocket_client.js can call the function when it
						// receives the network_status event
						network_client.onEvent(network_status_subject, update_network_chart);

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
			function update_network_chart(payload) {
				const tx = payload.network_status?.tx_rate;
				if (!Array.isArray(cores) || cores.length !== num_cores) return;

				const chart = window.system_network_status_chart;
				if (!chart) return;

				// Store into ring buffer
				cores.forEach((val, i) => {
					network_history[i][network_index] = Math.round(val);
				});

				network_index = (network_index + 1) % max_points;

				// Rotate each dataset's ring buffer to match chart order
				chart.data.datasets.forEach((dataset, i) => {
					const rotated = network_history[i].slice(network_index).concat(network_history[i].slice(0, network_index));
					dataset.data = rotated;
				});

				chart.update();

				// Optional: update total CPU %
				const td_network_status = document.getElementById('td_system_network_status_chart');
				if (td_network_status && payload.network_status?.total !== undefined) {
					td_network_status.textContent = `${Math.round(payload.network_status.total)}%`;
				}
			}

			// Set chart options
			const max_points = 60;
			const num_cores = <?= $network_cores ?>;

			let network_history = Array.from({ length: num_cores }, () => new Array(max_points).fill(null));
			let network_index = 0;

			// Color palette (distinct and visually stacked)
			const network_colors = ['#00bcd4', '#8bc34a', '#ffc107', '#e91e63'];

			// Initialize the chart
			window.system_network_status_chart = new Chart(
				document.getElementById('system_network_status_chart').getContext('2d'),
				{
					type: 'line',
					data: {
						labels: Array.from({ length: max_points }, (_, i) => i + 1),
						datasets: Array.from({ length: num_cores }, (_, i) => ({
							label: `NETWORK ${i}`,
							data: [...network_history[i]],
							fill: true,
							borderColor: network_colors[i % network_colors.length],
							backgroundColor: network_colors[i % network_colors.length],
							tension: 0.3,
							pointRadius: 0
						}))
					},
					options: {
						animation: false,
						scales: {
							y: {
								beginAtZero: true,
								stacked: true,
								min: 0,
								max: num_cores * 100,
								ticks: {
									stepSize: 100
								}
							},
							x: {
								ticks: {
									autoSkip: true,
									callback: function (val, index) {
										return (index % 100 === 0 ? ' ' : ' ');
									}
								},
								grid: {
									drawOnChartArea: false
								}
							}
						},
						plugins: {
							tooltip: {
								mode: 'index',
								intersect: false
							},
							legend: {
								display: false
							}
						}
					}
				}
			);

			connect_network_status_websocket();
		</script>

	<?php }
	//add half doughnut chart
	if (!isset($dashboard_chart_type) || $dashboard_chart_type == "doughnut") { ?>
		<div class='hud_chart' style='width: 175px;'><canvas id='system_network_status_chart'></canvas></div>

		<script>
			const network_status_auth_token = {
				name: "<?= $token['name']; ?>",
				hash: "<?= $token['hash']; ?>"
			}

			const network_status_subject = '<?php echo system_dashboard_service::NETWORK_STATUS_TOPIC; ?>';
			const dashboard_network_usage_chart_main_color = [
				'<?php echo ($settings->get('theme', 'dashboard_network_usage_chart_main_color')[0] ?? '#03c04a'); ?>',
				'<?php echo ($settings->get('theme', 'dashboard_network_usage_chart_main_color')[1] ?? '#ff9933'); ?>',
				'<?php echo ($settings->get('theme', 'dashboard_network_usage_chart_main_color')[2] ?? '#ea4c46'); ?>'
			];

			function connect_network_status_websocket() {
				client = new ws_client(`wss://${window.location.hostname}/websockets/`, network_status_auth_token);
				client.ws.addEventListener("open", async () => {
					try {
						console.log('Connected');
						console.log('Requesting authentication');

						// Wait until we are authenticated
						await client.request('authentication');
						console.log('authenticated');

						// Bind event handler so websocket_client.js can call the function when it
						// receives the network_status event
						client.onEvent(network_status_subject, update_network_chart);

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
			function update_network_chart(payload) {
				let network_status = Math.round(payload.network_status.total);
				const chart = window.system_network_status_chart;

				if (!chart) return;

				// Update chart data
				network_rounded = Math.round(network_status);
				chart.data.datasets[0].data = [network_rounded, 100 - network_rounded];

				// Update color based on threshold
				if (network_rounded <= 60) {
					chart.data.datasets[0].backgroundColor[0] = '<?php echo ($settings->get('theme', 'dashboard_network_usage_chart_main_color')[0] ?? '#03c04a'); ?>';
				} else if (network_rounded <= 80) {
					chart.data.datasets[0].backgroundColor[0] = '<?php echo ($settings->get('theme', 'dashboard_network_usage_chart_main_color')[1] ?? '#ff9933'); ?>';
				} else {
					chart.data.datasets[0].backgroundColor[0] = '<?php echo ($settings->get('theme', 'dashboard_network_usage_chart_main_color')[2] ?? '#ea4c46'); ?>';
				}

				chart.options.plugins.chart_number_2.text = network_rounded;
				chart.update();

				// Update the row data
				const td_network_status = document.getElementById('td_system_network_status_chart');
				if (!td_network_status) { return; }
				td_network_status.textContent = `${payload.network_status}%`;
			}

			window.system_network_status_chart = new Chart(
				document.getElementById('system_network_status_chart').getContext('2d'),
				{
					type: 'doughnut',
					data: {
						datasets: [{
							data: ['<?php echo $percent_network; ?>', 100 - '<?php echo $percent_network; ?>'],
							backgroundColor: [
								<?php
								if ($percent_network <= 60) {
									echo "'".($settings->get('theme', 'dashboard_network_usage_chart_main_color')[0] ?? '#03c04a')."',\n";
								} else if ($percent_network <= 80) {
									echo "'".($settings->get('theme', 'dashboard_network_usage_chart_main_color')[1] ?? '#ff9933')."',\n";
								} else if ($percent_network > 80) {
									echo "'".($settings->get('theme', 'dashboard_network_usage_chart_main_color')[2] ?? '#ea4c46')."',\n";
								}
								?>
								'<?php echo ($settings->get('theme', 'dashboard_network_usage_chart_sub_color') ?? '#d4d4d4'); ?>'
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
								text: '<?php echo round($percent_network); ?>'
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
							ctx.fillStyle = '<?php echo $dashboard_number_text_color; ?>';
							ctx.fillText(options.text + '%', width / 2, top + (height / 2) + 35);
							ctx.save();
						}
					}]
				}
			);

			connect_network_status_websocket();
		</script>
	<?php }
	if ($dashboard_chart_type == "number") {
		echo "<span class='hud_stat'>".round($percent_network)."%</span>";
	}
	echo "</div>\n";

	if ($dashboard_details_state != 'disabled') {
		echo "<div class='hud_details hud_box' id='hud_system_network_status_details'>";
		echo "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
		echo "<tr>\n";
		echo "<th class='hud_heading' width='50%'>".$text['label-name']."</th>\n";
		echo "<th class='hud_heading' style='text-align: right;'>".$text['label-value']."</th>\n";
		echo "</tr>\n";

		if (PHP_OS == 'FreeBSD' || PHP_OS == 'Linux') {
			if (!empty($percent_network)) {
				echo "<tr class='tr_link_void'>\n";
				echo "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-network_usage']."</td>\n";
				echo "<td id='td_system_network_status_chart' valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$percent_network."%</td>\n";
				echo "</tr>\n";
				$c = ($c) ? 0 : 1;
			}

			if (!empty($network_cores)) {
				echo "<tr class='tr_link_void'>\n";
				echo "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-network_cores']."</td>\n";
				echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$network_cores."</td>\n";
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

		echo "<span class='hud_expander' onclick=\"$('#hud_system_network_status_details').slideToggle('fast'); toggle_grid_row_end('".$dashboard_name."')\"><span class='fas fa-ellipsis-h'></span></span>";
	}
	echo "</div>\n";
