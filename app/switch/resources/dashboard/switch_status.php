<?php

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";

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
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

//switch version
	if (permission_exists('switch_version') && $fp) {
		$switch_version = event_socket_request($fp, 'api version');
		preg_match("/FreeSWITCH Version (\d+\.\d+\.\d+(?:\.\d+)?).*\(.*?(\d+\w+)\s*\)/", $switch_version, $matches);
		$switch_version = $matches[1];
		$switch_bits = $matches[2];
	}

//switch uptime
	if (permission_exists('switch_uptime') && $fp) {
		$tmp = event_socket_request($fp, 'api status');
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
	if (permission_exists('switch_channels') && $fp) {
		$tmp = event_socket_request($fp, 'api status');
		$matches = Array();
		preg_match("/(\d+)\s+session\(s\)\s+\-\speak/", $tmp, $matches);
		$channels = $matches[1] ? $matches[1] : 0;
		if (permission_exists('call_active_view')) {
			$tr_link_channels = "href='".PROJECT_PATH."/app/calls_active/calls_active.php'";
		}
	}

//registration count
	if (permission_exists('switch_registrations') && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/registrations/")) {
		$registration = new registrations;
		if (permission_exists("registration_all")) {
			$registration->show = 'all';
			$tr_link_registrations = "href='".PROJECT_PATH."/app/registrations/registrations.php'";
		}
		$registrations = $registration->count();
	}

//add doughnut chart
	?>
	<div style='display: flex; flex-wrap: wrap; justify-content: center; padding-bottom: 20px;' onclick="$('#hud_switch_status_details').slideToggle('fast');">
		<canvas id='switch_status_chart' width='175px' height='175px'></canvas>
	</div>

	<script>
		const switch_status_chart = new Chart(
			document.getElementById('switch_status_chart').getContext('2d'),
			{
				type: 'doughnut',
				data: {
					datasets: [{
						data: ['<?php echo $registrations; ?>', 0.00001],
						backgroundColor: ['<?php echo $_SESSION['dashboard']['switch_status_chart_main_background_color']['text']; ?>',
						'<?php echo $_SESSION['dashboard']['switch_status_chart_sub_background_color']['text']; ?>'],
						borderColor: '<?php echo $_SESSION['dashboard']['switch_status_chart_border_color']['text']; ?>',
						borderWidth: '<?php echo $_SESSION['dashboard']['switch_status_chart_border_width']['text']; ?>',
						cutout: chart_cutout
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						chart_counter: {
							chart_text: '<?php echo $registrations; ?>'
						},
						legend: {
							display: false
						},
						title: {
							display: true,
							text: '<?php echo $text['label-switch_status']; ?>'
						}
					}
				},
				plugins: [chart_counter],
			}
		);
	</script>
	<?php

//show the content
	echo "<div class='hud_details hud_box' id='hud_switch_status_details'>";
	echo "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "<tr>\n";
	echo "<th class='hud_heading' width='50%'>".$text['label-name']."</th>\n";
	echo "<th class='hud_heading' style='text-align: right;'>".$text['label-value']."</th>\n";
	echo "</tr>\n";

	//switch version
	if (permission_exists('switch_version') && $switch_version != '') {
		echo "<tr class='tr_link' ".$tr_link_sip_status.">\n";
		echo "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-switch']."</td>\n";
		echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'><a ".$tr_link_sip_status.">".$switch_version." (".$switch_bits.")</a></td>\n";
		echo "</tr>\n";
		$c = ($c) ? 0 : 1;
	}

	//switch uptime
	if (permission_exists('switch_uptime') && $uptime != '') {
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
	$n++;

	echo "<span class='hud_expander' onclick=\"$('#hud_switch_status_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
	echo "</div>\n";

?>
