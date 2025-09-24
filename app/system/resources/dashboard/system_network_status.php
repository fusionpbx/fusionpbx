<?php

	//includes files
	require_once dirname(__DIR__, 4) . "/resources/require.php";

	//check permisions
	require_once "resources/check_auth.php";
	if (permission_exists('system_view_network')) {
		//access granted
	}
	else {
		return;
	}

	//add multi-lingual support
	$language = new text;
	$text = $language->get($_SESSION['domain']['language']['code'], 'app/system');

	//set the row style class names
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//	//get the network details
//	if (stristr(PHP_OS, 'Linux')) {
//		$result = shell_exec("ls /sys/class/net | tr '\n' ' '");
//		$cards = array_map('trim', explode(' ', $result));
//		$selected_card = $settings->get('system', 'network_interface', '');
//		if (!in_array($selected_card, $cards, true)) {
//			// Selected card not in list
//			return;
//		}
//
//	}

	$token = (new token())->create($_SERVER['PHP_SELF']);

	// Register as a subscriber for the dashboard information service
	subscriber::save_token($token, [system_dashboard_service::get_service_name()]);

	//system network status
	echo "<div class='hud_box'>\n";
	echo "<input id='token' type='hidden' name='" . $token['name'] . "' value='" . $token['hash'] . "'>\n";
		echo "<div class='hud_content'>\n";
			echo "<span class='hud_title'>\n";
				echo "<a onclick=\"document.location.href='".PROJECT_PATH."/app/system/system.php'\">".$text['label-network_usage']."</a>\n";
			echo "</span>\n";
			//if ($dashboard_chart_type === 'line') { ?>
			<div class='hud_chart' style='width: 100%; height: 100%'>
				<canvas id='system_network_status_chart'></canvas>
			</div>

			<script>
				const network_status_auth_token = {
					name: "<?= $token['name']; ?>",
					hash: "<?= $token['hash']; ?>"
				};

				const network_status_subject = '<?php echo system_dashboard_service::NETWORK_STATUS_TOPIC; ?>';

				// Use your theme colors: [main, accent1, accent2]
				const dashboard_network_usage_chart_main_color = [
					'<?php echo ($settings->get('theme', 'dashboard_network_usage_chart_main_color', [0=>'#03c04a'])[0]); ?>',  // green default
					'<?php echo ($settings->get('theme', 'dashboard_network_usage_chart_main_color', [1=>'#ff9933'])[1]); ?>'   // orange default
				];

				// ---- Chart state ----
				const max_network_points = 60;			// seconds of history (one point per update)
				let ring_index = 0;
				const rx_history = Array.from({ length: max_network_points }, () => 0);
				const tx_history = Array.from({ length: max_network_points }, () => 0);

				function to_units(bps) {
					if (bps == null || isNaN(bps)) return { val: 0, unit: "bps" }
					if (bps < 1e3) return { val: bps, unit: "bps" };
					if (bps < 1e6) return { val: bps / 1e3, unit: "Kbps" };
					if (bps < 1e9) return { val: bps / 1e6, unit: "Mbps" };
					return { val: bps / 1e9, unit: "Gbps" };
				}

				function human_units(val, unit) {
					return `${val.toFixed(2)} ${unit}`;
				}

				// --- helpers: keep raw bps, format for display ---
				function format_bitrate(bps) {
					if (bps == null || isNaN(bps)) return '0 bps';
					const abs = Math.abs(bps);
					if (abs < 1)       return bps.toFixed(2) + ' bps';    // sub-1 bps
					if (abs < 1e3)     return Math.round(bps) + ' bps';
					if (abs < 1e6)     return (bps / 1e3).toFixed(2) + ' Kbps';
					if (abs < 1e9)     return (bps / 1e6).toFixed(2) + ' Mbps';
					return (bps / 1e9).toFixed(2) + ' Gbps';
				}

				// Optional: round tiny float noise to 0 so scales don't jitter
				function sanitize_bps(bps) {
					if (bps == null || isNaN(bps)) return 0;
					const v = Number(bps);
					return Math.abs(v) < 1e-6 ? 0 : v;
				}

				// ---- Chart init ----
				const ctx = document.getElementById('system_network_status_chart').getContext('2d');
				const rxColor = dashboard_network_usage_chart_main_color[0];
				const txColor = dashboard_network_usage_chart_main_color[1];
				var current_unit = 'bps';

				window.system_network_status_chart = new Chart(ctx, {
					type: 'line',
					data: {
						labels: Array.from({ length: max_network_points }, () => ''),
						datasets: [
							{
								label: 'RX',
								data: [...rx_history],           // store raw bps here
								borderColor: rxColor,
								backgroundColor: rxColor + '33',
								fill: true,
								tension: 0.3,
								pointRadius: 0
							},
							{
								label: 'TX',
								data: [...tx_history],           // store raw bps here
								borderColor: txColor,
								backgroundColor: txColor + '33',
								fill: true,
								tension: 0.3,
								pointRadius: 0
							}
						]
					},
					options: {
						animation: false,
						plugins: {
							legend: { display: true },
							tooltip: {
								mode: 'index',
								intersect: false,
								callbacks: {
									// use raw value to format with the right unit per-point
									label: (ctx) => `${ctx.dataset.label}: ${format_bitrate(ctx.raw ?? ctx.parsed.y)}`
								}
							}
						},
						scales: {
							y: {
								beginAtZero: true,
								// let Chart.js auto-fit; no max/suggestedMax
								ticks: {
									// format axis tick values adaptively (bps/Kbps/Mbps/…)
									callback: (value) => format_bitrate(value)
								},
								title: { display: false }
							},
							x: {
								grid: { drawOnChartArea: false },
								ticks: { autoSkip: true, maxTicksLimit: 6 }
							}
						}
					}
				});

				// ---- Live updates ----
				function update_network_chart(payload) {
					const chart = window.system_network_status_chart;
					if (!chart) return;

					const rx_bps = sanitize_bps(payload?.network_status?.rx_bps);
					const tx_bps = sanitize_bps(payload?.network_status?.tx_bps);

					// write raw bps into ring buffers
					rx_history[ring_index] = rx_bps;
					tx_history[ring_index] = tx_bps;

					ring_index = (ring_index + 1) % max_network_points;

					const rotate = (arr) => arr.slice(ring_index).concat(arr.slice(0, ring_index));
					chart.data.datasets[0].data = rotate(rx_history);
					chart.data.datasets[1].data = rotate(tx_history);

					chart.update();
				}

				function connect_network_status_websocket() {
					network_client = new ws_client(`wss://${window.location.hostname}/websockets/`, network_status_auth_token);

					network_client.ws.addEventListener('open', async () => {
						try {
							await network_client.request('authentication');
							network_client.onEvent(network_status_subject, update_network_chart);
						} catch (err) {
							console.error('WS setup failed: ', err);
						}
					});

					network_client.ws.addEventListener('close', () => {
						console.warn('Network websocket disconnected');
					});
				}

				connect_network_status_websocket();
			</script><?php	//	}
		echo "</div>\n";
	echo "</div>\n";
