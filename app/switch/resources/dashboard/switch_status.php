<?php

//includes files
	require_once dirname(__DIR__, 4) . "/resources/require.php";

//check permisions
	require_once "resources/check_auth.php";
	if (permission_exists("switch_version")
		|| permission_exists("switch_uptime")
		|| permission_exists("switch_channels")
		|| permission_exists("switch_registrations")
		||  permission_exists("registration_all")) {
		//access granted
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get($_SESSION['domain']['language']['code'], 'app/switch');

//switch status
	echo "<div class='hud_box'>\n";

//set the row style class names
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//connect to event socket
	$esl = event_socket::create();

//switch version
	if (permission_exists('switch_version') && $esl->is_connected()) {
		$switch_version = event_socket::api('version');
		preg_match("/FreeSWITCH Version (\d+\.\d+\.\d+(?:\.\d+)?).*\(.*?(\d+\w+)\s*\)/", $switch_version, $matches);
		$switch_version = $matches[1];
		$switch_bits = $matches[2];
	}

//switch uptime
	if (permission_exists('switch_uptime') && $esl->is_connected()) {
		$tmp = event_socket::api('status');
		$tmp = explode("\n", $tmp);
		$tmp = $tmp[0];
		$tmp = explode(' ', $tmp);
		$uptime = (($tmp[1]) ? $tmp[1].'y ' : null);
		$uptime .= (($tmp[3]) ? $tmp[3].'d ' : null);
		$uptime .= (($tmp[5]) ? $tmp[5].'h ' : null);
		$uptime .= (($tmp[7]) ? $tmp[7].'m ' : null);
		$uptime .= (($tmp[9]) ? $tmp[9].'s' : null);
		if (permission_exists('system_status_sofia_status') || permission_exists('system_status_sofia_status_profile') || if_group("superadmin")) {
			$tr_link_sip_status = "href='".PROJECT_PATH."/app/sip_status/sip_status.php'";
		}
	}

//channel count
	$channels = '';
	$tr_link_channels = '';
	if (permission_exists('switch_channels') && $esl->is_connected()) {
		$tmp = event_socket::api('status');
		$matches = Array();
		preg_match("/(\d+)\s+session\(s\)\s+\-\speak/", $tmp, $matches);
		$channels = $matches[1] ? $matches[1] : 0;
		if (permission_exists('call_active_view')) {
			$tr_link_channels = "href='".PROJECT_PATH."/app/calls_active/calls_active.php'";
		}
	}

//registration count
	$registrations = '';
	if (permission_exists('switch_registrations') && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/registrations/")) {
		$registration = new registrations;
		if (permission_exists("registration_all")) {
			$registration->show = 'all';
			$tr_link_registrations = "href='".PROJECT_PATH."/app/registrations/registrations.php'";
		}
		$registrations = $registration->count();
	}

//show the content
	echo "<div class='hud_content' ".($dashboard_details_state == "disabled" ?: "onclick=\"$('#hud_switch_status_details').slideToggle('fast');\"").">\n";
	echo "	<span class='hud_title'>".$text['label-switch_status']."</span>\n";

	if (!isset($dashboard_chart_type) || $dashboard_chart_type == "doughnut") {
		//add doughnut chart
		?>
		<div class='hud_chart' style='width: 175px;'><canvas id='switch_status_chart'></canvas></div>

		<script>
			const switch_status_chart = new Chart(
				document.getElementById('switch_status_chart').getContext('2d'),
				{
					type: 'doughnut',
					data: {
						datasets: [{
							data: ['<?php echo $registrations; ?>', 0.00001],
							backgroundColor: [
								'<?php echo ($settings->get('theme', 'dashboard_switch_status_chart_main_color') ?? '#2a9df4'); ?>',
								'<?php echo ($settings->get('theme', 'dashboard_switch_status_chart_sub_color') ?? '#d4d4d4'); ?>'
							],
							borderColor: '<?php echo $settings->get('theme', 'dashboard_chart_border_color'); ?>',
							borderWidth: '<?php echo $settings->get('theme', 'dashboard_chart_border_width'); ?>',
						}]
					},
					options: {
						plugins: {
							chart_number: {
								text: '<?php echo $registrations; ?>'
							}
						}
					},
					plugins: [{
						id: 'chart_number',
						beforeDraw(chart, args, options){
							const {ctx, chartArea: {top, right, bottom, left, width, height} } = chart;
							ctx.font = chart_text_size + ' ' + chart_text_font;
							ctx.textBaseline = 'middle';
							ctx.textAlign = 'center';
							ctx.fillStyle = '<?php echo $dashboard_number_text_color; ?>';
							ctx.fillText(options.text, width / 2, top + (height / 2));
							ctx.save();
						}
					}]
				}
			);
		</script>
		<?php
	}
	if ($dashboard_chart_type == "number") {
		echo "	<span class='hud_stat'>".$registrations."</span>";
	}
	echo "	</div>\n";

	if ($dashboard_details_state != 'disabled') {
		echo "<div class='hud_details hud_box' id='hud_switch_status_details'>";
		echo "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
		echo "<tr>\n";
		echo "<th class='hud_heading' width='50%'>".$text['label-name']."</th>\n";
		echo "<th class='hud_heading' style='text-align: right;'>".$text['label-value']."</th>\n";
		echo "</tr>\n";

		//switch version
		if (permission_exists('switch_version') && !empty($switch_version)) {
			echo "<tr class='tr_link' ".$tr_link_sip_status.">\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-switch']."</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'><a ".$tr_link_sip_status.">".$switch_version." (".$switch_bits.")</a></td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}

		//switch uptime
		if (permission_exists('switch_uptime') && !empty($uptime)) {
			echo "<tr class='tr_link' ".$tr_link_sip_status.">\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-switch_uptime']."</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'><a ".$tr_link_sip_status.">".$uptime."</a></td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}

		//switch channels
		if (permission_exists('switch_channels')) {
			echo "<tr class='tr_link' ".$tr_link_channels.">\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-channels']."</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'><a ".$tr_link_channels.">".$channels."</a></td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}

		//switch registrations
		if (permission_exists('switch_registrations')) {
			echo "<tr class='tr_link' ".$tr_link_registrations.">\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-registrations']."</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'><a ".$tr_link_registrations.">".$registrations."</a></td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}

		echo "</table>\n";
		echo "</div>";
		//$n++;

		echo "<span class='hud_expander' onclick=\"$('#hud_switch_status_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
	}
	echo "</div>\n";

?>
