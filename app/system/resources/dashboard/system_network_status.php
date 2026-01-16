<?php

//includes files
require_once dirname(__DIR__, 4) . "/resources/require.php";

//check permisions
require_once "resources/check_auth.php";
if (permission_exists('system_view_network')) {
	//access granted
} else {
	return;
}

//convert to a key
$widget_key = str_replace(' ', '_', strtolower($widget_name));

//add multi-lingual support
$language = new text;
$text = $language->get($settings->get('domain', 'language', 'en-us'), 'app/system');

//get the dashboard label
$widget_label = $text['label-'.$widget_key] ?? $widget_name;

//set the row style class names
$c = 0;
$row_style["0"] = "row_style0";
$row_style["1"] = "row_style1";

//create token
$token = (new token())->create($_SERVER['PHP_SELF']);

// Register as a subscriber for the dashboard information service
subscriber::save_token($token, [system_dashboard_service::get_service_name()]);

//system network status
echo "<div class='hud_box'>\n";
echo "<input id='token' type='hidden' name='" . $token['name'] . "' value='" . $token['hash'] . "'>\n";
echo "<div class='hud_content'>\n";
echo "<span class='hud_title'>\n";
echo "<a onclick=\"document.location.href='" . PROJECT_PATH . "/app/system/system.php'\">" . escape($widget_label) . "</a>\n";
echo "</span>\n";
//if ($dashboard_chart_type === 'line') { ?>
	<div class='hud_chart' style='width: 100%; height: 85%'>
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
			'<?php echo($settings->get('theme', 'dashboard_network_usage_chart_main_color', [0 => '#03c04a'])[0]); ?>',  // green default
			'<?php echo($settings->get('theme', 'dashboard_network_usage_chart_main_color', [1 => '#ff9933'])[1]); ?>'   // orange default
		];

		function format_bitrate(v) {
			if (v == null) return '';
			const u = ['bps', 'Kbps', 'Mbps', 'Gbps', 'Tbps'];
			let i = 0, n = Number(v);
			while (n >= 1000 && i < u.length - 1) {
				n /= 1000;
				i++;
			}
			return n.toFixed(1) + ' ' + u[i];
		}

		function sanitize_bps(bps) {
			if (bps == null || isNaN(bps)) return 0;
			const v = Number(bps);
			return Math.abs(v) < 1e-6 ? 0 : v;
		}

		// 3) ---- Chart init ----
		const ctx = document.getElementById('system_network_status_chart').getContext('2d');
		const rxColor = dashboard_network_usage_chart_main_color[0];
		const txColor = dashboard_network_usage_chart_main_color[1];

		// IMPORTANT: assign to window.system_network_status_chart so that it is globally accessible
		const chartConfig = {
			type: 'line',
			data: {
				datasets: [
					{
						label: 'RX',
						borderColor: rxColor,
						backgroundColor: rxColor + '33',
						fill: true,
						tension: 0.3,
						pointRadius: 0,
						pointHoverRadius: 4,
						pointHoverBackgroundColor: rxColor,
						pointHoverBorderColor: '#fff',
						pointHoverBorderWidth: 2,
						spanGaps: true,
						data: []
					},
					{
						label: 'TX',
						borderColor: txColor,
						backgroundColor: txColor + '33',
						fill: true,
						tension: 0.3,
						pointRadius: 0,
						pointHoverRadius: 4,
						pointHoverBackgroundColor: txColor,
						pointHoverBorderColor: '#fff',
						pointHoverBorderWidth: 2,
						spanGaps: true,
						data: []
					}
				]
			},
			options: {
				animation: false,
				parsing: { xAxisKey: 'x', yAxisKey: 'y' },
				maintainAspectRatio: false,
				interaction: {
					mode: 'index',
					intersect: false
				},
				scales: {
					x: {
						type: 'realtime',
						realtime: {
							duration: 60000,   // last 60s
							refresh: 1000,     // redraw every 1s
							delay: 2000        // 2s render delay to handle late packets
						},
						grid: {drawOnChartArea: false},
						ticks: {display: false},
					},
					y: {
						beginAtZero: true,
						grace: '5%',
						ticks: {callback: (v) => format_bitrate(v)}
					}
				},
				plugins: {
					legend: {display: false},
					tooltip: {
						enabled: true,
						mode: 'index',
						intersect: false,
						callbacks: {
							label: (ctx) => `${ctx.dataset.label}: ${format_bitrate(ctx.parsed.y)}`
						}
					}
				}
			}
		};
		
		window.system_network_status_chart = new Chart(ctx, chartConfig);
		
		if (window.system_network_status_chart.tooltip) {
			window.system_network_status_chart.tooltip._chart = window.system_network_status_chart;
		}

		function update_network_chart(payload) {
			const chart = window.system_network_status_chart;
			if (!chart) return;

			const rx_bps = sanitize_bps(payload?.network_status?.rx_bps);
			const tx_bps = sanitize_bps(payload?.network_status?.tx_bps);
			const now = Date.now();

			chart.data.datasets[0].data.push({x: now, y: rx_bps});
			chart.data.datasets[1].data.push({x: now, y: tx_bps});
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
			network_client.ws.addEventListener('close', () => console.warn('Network websocket disconnected'));
		}

		connect_network_status_websocket();
	</script><?php //	}
echo "</div>\n";
echo "</div>\n";
