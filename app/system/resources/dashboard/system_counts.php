<?php

//includes
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

//connect to the database
	if (!isset($database)) {
		$database = new database;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get($_SESSION['domain']['language']['code'], 'core/user_settings');

//system counts
	//domains
	if (permission_exists('domain_view')) {
		$stats['system']['domains']['total'] = sizeof($_SESSION['domains']);
		$stats['system']['domains']['disabled'] = 0;
		foreach ($_SESSION['domains'] as $domain) {
			$stats['system']['domains']['disabled'] += ($domain['domain_enabled'] != 'true') ? 1 : 0;
		}
	}

	//devices
	if (permission_exists('device_view')) {
		$stats['system']['devices']['total'] = 0;
		$stats['system']['devices']['disabled'] = 0;
		$stats['domain']['devices']['total'] = 0;
		$stats['domain']['devices']['disabled'] = 0;
		$sql = "select domain_uuid, device_enabled from v_devices";
		$result = $database->select($sql, null, 'all');
		if (is_array($result) && sizeof($result) != 0) {
			$stats['system']['devices']['total'] = sizeof($result);
			foreach ($result as $row) {
				$stats['system']['devices']['disabled'] += ($row['device_enabled'] != 'true') ? 1 : 0;
				if ($row['domain_uuid'] == $_SESSION['domain_uuid']) {
					$stats['domain']['devices']['total']++;
					$stats['domain']['devices']['disabled'] += ($row['device_enabled'] != 'true') ? 1 : 0;
				}
			}
		}
		unset($sql, $result);
	}

	//extensions
	if (permission_exists('extension_view')) {
		$stats['system']['extensions']['total'] = 0;
		$stats['system']['extensions']['disabled'] = 0;
		$stats['domain']['extensions']['total'] = 0;
		$stats['domain']['extensions']['disabled'] = 0;
		$sql = "select domain_uuid, enabled from v_extensions";
		$result = $database->select($sql, null, 'all');
		if (is_array($result) && sizeof($result) != 0) {
			$stats['system']['extensions']['total'] = sizeof($result);
			foreach ($result as $row) {
				$stats['system']['extensions']['disabled'] += ($row['enabled'] != 'true') ? 1 : 0;
				if ($row['domain_uuid'] == $_SESSION['domain_uuid']) {
					$stats['domain']['extensions']['total']++;
					$stats['domain']['extensions']['disabled'] += ($row['enabled'] != 'true') ? 1 : 0;
				}
			}
		}
		unset($sql, $result);
	}

	//gateways
	if (permission_exists('gateway_view')) {
		$stats['system']['gateways']['total'] = 0;
		$stats['system']['gateways']['disabled'] = 0;
		$stats['domain']['gateways']['total'] = 0;
		$stats['domain']['gateways']['disabled'] = 0;
		$sql = "select domain_uuid, enabled from v_gateways";
		$result = $database->select($sql, null, 'all');
		if (is_array($result) && sizeof($result) != 0) {
			$stats['system']['gateways']['total'] = sizeof($result);
			foreach ($result as $row) {
				$stats['system']['gateways']['disabled'] += ($row['enabled'] != 'true') ? 1 : 0;
				if ($row['domain_uuid'] == $_SESSION['domain_uuid']) {
					$stats['domain']['gateways']['total']++;
					$stats['domain']['gateways']['disabled'] += ($row['enabled'] != 'true') ? 1 : 0;
				}
			}
		}
		unset($sql, $result);
	}

	//users
	if (permission_exists('user_view') || if_group("superadmin")) {
		$stats['system']['users']['total'] = 0;
		$stats['system']['users']['disabled'] = 0;
		$stats['domain']['users']['total'] = 0;
		$stats['domain']['users']['disabled'] = 0;
		$sql = "select domain_uuid, user_enabled from v_users";
		$result = $database->select($sql, null, 'all');
		if (is_array($result) && sizeof($result) != 0) {
			$stats['system']['users']['total'] = sizeof($result);
			foreach ($result as $row) {
				$stats['system']['users']['disabled'] += ($row['user_enabled'] != 'true') ? 1 : 0;
				if ($row['domain_uuid'] == $_SESSION['domain_uuid']) {
					$stats['domain']['users']['total']++;
					$stats['domain']['users']['disabled'] += ($row['user_enabled'] != 'true') ? 1 : 0;
				}
			}
		}
		unset($sql, $result);
	}

	//destinations
	if (permission_exists('destination_view')) {
		$stats['system']['destinations']['total'] = 0;
		$stats['system']['destinations']['disabled'] = 0;
		$stats['domain']['destinations']['total'] = 0;
		$stats['domain']['destinations']['disabled'] = 0;
		$sql = "select domain_uuid, destination_enabled from v_destinations";
		$result = $database->select($sql, null, 'all');
		if (is_array($result) && sizeof($result) != 0) {
			$stats['system']['destinations']['total'] = sizeof($result);
			foreach ($result as $row) {
				$stats['system']['destinations']['disabled'] += ($row['destination_enabled'] != 'true') ? 1 : 0;
				if ($row['domain_uuid'] == $_SESSION['domain_uuid']) {
					$stats['domain']['destinations']['total']++;
					$stats['domain']['destinations']['disabled'] += ($row['destination_enabled'] != 'true') ? 1 : 0;
				}
			}
		}
		unset($sql, $result);
	}

	//call center queues
	if (permission_exists('call_center_active_view')) {
		$stats['system']['call_center_queues']['total'] = 0;
		$stats['system']['call_center_queues']['disabled'] = 0;
		$stats['domain']['call_center_queues']['total'] = 0;
		$stats['domain']['call_center_queues']['disabled'] = 0;
		$sql = "select domain_uuid from v_call_center_queues";
		$result = $database->select($sql, null, 'all');
		if (is_array($result) && sizeof($result) != 0) {
			$stats['system']['call_center_queues']['total'] = sizeof($result);
			foreach ($result as $row) {
				//$stats['system']['call_center_queues']['disabled'] += ($row['queue_enabled'] != 'true') ? 1 : 0;
				if ($row['domain_uuid'] == $_SESSION['domain_uuid']) {
					$stats['domain']['call_center_queues']['total']++;
					//$stats['domain']['call_center_queues']['disabled'] += ($row['queue_enabled'] != 'true') ? 1 : 0;
				}
			}
		}
		unset($sql, $result);
	}

	//ivr menus
	if (permission_exists('ivr_menu_view')) {
		$stats['system']['ivr_menus']['total'] = 0;
		$stats['system']['ivr_menus']['disabled'] = 0;
		$stats['domain']['ivr_menus']['total'] = 0;
		$stats['domain']['ivr_menus']['disabled'] = 0;
		$sql = "select domain_uuid, ivr_menu_enabled from v_ivr_menus";
		$result = $database->select($sql, null, 'all');
		if (is_array($result) && sizeof($result) != 0) {
			$stats['system']['ivr_menus']['total'] = sizeof($result);
			foreach ($result as $row) {
				$stats['system']['ivr_menus']['disabled'] += ($row['ivr_menu_enabled'] != 'true') ? 1 : 0;
				if ($row['domain_uuid'] == $_SESSION['domain_uuid']) {
					$stats['domain']['ivr_menus']['total']++;
					$stats['domain']['ivr_menus']['disabled'] += ($row['ivr_menu_enabled'] != 'true') ? 1 : 0;
				}
			}
		}
		unset($sql, $result);
	}

	//ring groups
	if (permission_exists('ring_group_view')) {
		$stats['system']['ring_groups']['total'] = 0;
		$stats['system']['ring_groups']['disabled'] = 0;
		$stats['domain']['ring_groups']['total'] = 0;
		$stats['domain']['ring_groups']['disabled'] = 0;
		$sql = "select domain_uuid, ring_group_enabled from v_ring_groups";
		$result = $database->select($sql, null, 'all');
		if (is_array($result) && sizeof($result) != 0) {
			$stats['system']['ring_groups']['total'] = sizeof($result);
			foreach ($result as $row) {
				$stats['system']['ring_groups']['disabled'] += ($row['ring_group_enabled'] != 'true') ? 1 : 0;
				if ($row['domain_uuid'] == $_SESSION['domain_uuid']) {
					$stats['domain']['ring_groups']['total']++;
					$stats['domain']['ring_groups']['disabled'] += ($row['ring_group_enabled'] != 'true') ? 1 : 0;
				}
			}
		}
		unset($sql, $result);
	}

	//voicemails
	if (permission_exists('voicemail_view')) {
		$stats['system']['voicemails']['total'] = 0;
		$stats['system']['voicemails']['disabled'] = 0;
		$stats['domain']['voicemails']['total'] = 0;
		$stats['domain']['voicemails']['disabled'] = 0;
		$sql = "select domain_uuid, voicemail_enabled from v_voicemails";
		$result = $database->select($sql, null, 'all');
		if (is_array($result) && sizeof($result) != 0) {
			$stats['system']['voicemails']['total'] = sizeof($result);
			foreach ($result as $row) {
				$stats['system']['voicemails']['disabled'] += ($row['voicemail_enabled'] != 'true') ? 1 : 0;
				if ($row['domain_uuid'] == $_SESSION['domain_uuid']) {
					$stats['domain']['voicemails']['total']++;
					$stats['domain']['voicemails']['disabled'] += ($row['voicemail_enabled'] != 'true') ? 1 : 0;
				}
			}
		}
		unset($sql, $result);
	}

	//voicemail messages
	if (permission_exists('voicemail_message_view')) {
		$stats['system']['messages']['total'] = 0;
		$stats['system']['messages']['new'] = 0;
		$stats['domain']['messages']['total'] = 0;
		$stats['domain']['messages']['new'] = 0;
		$sql = "select domain_uuid, message_status from v_voicemail_messages";
		$result = $database->select($sql, null, 'all');
		if (is_array($result) && sizeof($result) != 0) {
			$stats['system']['messages']['total'] = sizeof($result);
			foreach ($result as $row) {
				$stats['system']['messages']['new'] += ($row['message_status'] != 'saved') ? 1 : 0;
				if ($row['domain_uuid'] == $_SESSION['domain_uuid']) {
					$stats['domain']['messages']['total']++;
					$stats['domain']['messages']['new'] += ($row['message_status'] != 'saved') ? 1 : 0;
				}
			}
		}
		unset($sql, $result);
	}

	//set the row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	//get the domain active and inactive counts
	$sql = "select ";
	$sql .= "(select count(*) from v_domains where domain_enabled = 'true') as active, ";
	$sql .= "(select count(*) from v_domains where domain_enabled = 'false') as inactive; ";
	$row = $database->select($sql, null, 'row');
	$domain_active = $row['active'];
	$domain_inactive = $row['inactive'];
	$domain_total = $domain_active + $domain_inactive;
	unset($sql, $row);

	//set scope: system, domain
	$scope = (permission_exists('dialplan_add')) ? 'system' : 'domain';

	//define the heads up display variables
	$show_stat = true;
	if (permission_exists('domain_view')) {
		$onclick = "onclick=\"document.location.href='".PROJECT_PATH."/core/domains/domains.php'\"";
		$hud_stat = $stats[$scope]['domains']['total'] - $stats[$scope]['domains']['disabled'];
		$hud_stat_title = $text['label-active_domains'];
	}
	else if (permission_exists('extension_view') && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/extensions/")) {
		$onclick = "onclick=\"document.location.href='".PROJECT_PATH."/app/extensions/extensions.php'\"";
		$hud_stat = $stats[$scope]['extensions']['total'] - $stats[$scope]['extensions']['disabled'];
		$hud_stat_title = $text['label-active_extensions'];
	}
	else if ((permission_exists('user_view') || if_group("superadmin")) && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/core/users/")) {
		$onclick = "onclick=\"document.location.href='".PROJECT_PATH."/core/users/users.php'\"";
		$hud_stat = $stats[$scope]['users']['total'] - $stats[$scope]['users']['disabled'];
		$hud_stat_title = $text['label-active_users'];
	}
	else {
		$show_stat = false;
	}

	echo "<div class='hud_box'>\n";
	if ($show_stat) {
		//add doughnut chart
		?>
		<div style='display: flex; flex-wrap: wrap; justify-content: center; padding-bottom: 20px;' onclick="$('#hud_system_counts_details').slideToggle('fast');">
			<div style='width: 250px; height: 175px;'><canvas id='system_counts_chart'></canvas></div>
		</div>

		<script>
			const system_counts_chart = new Chart(
				document.getElementById('system_counts_chart').getContext('2d'),
				{
					type: 'doughnut',
					data: {
						labels: ['<?php echo $text['label-active']; ?>: <?php echo $domain_active; ?>', '<?php echo $text['label-inactive']; ?>: <?php echo $domain_inactive; ?>'],
						datasets: [{
							data: ['<?php echo $domain_active; ?>', '<?php echo $domain_inactive; ?>'],
							backgroundColor: [
								'<?php echo $_SESSION['dashboard']['system_counts_chart_main_background_color']['text']; ?>',
								'<?php echo $_SESSION['dashboard']['system_counts_chart_sub_background_color']['text']; ?>'
							],
							borderColor: '<?php echo $_SESSION['dashboard']['system_counts_chart_border_color']['text']; ?>',
							borderWidth: '<?php echo $_SESSION['dashboard']['system_counts_chart_border_width']['text']; ?>',
							cutout: chart_cutout
						}]
					},
					options: {
					responsive: true,
						maintainAspectRatio: false,
						plugins: {
							chart_counter: {
								chart_text: '<?php echo $domain_total; ?>'
							},
							legend: {
								position: 'right',
								reverse: true,
								labels: {
									usePointStyle: true,
									pointStyle: 'rect'
								}
							},
							title: {
								display: true,
								text: '<?php echo $text['label-system_counts']; ?>'
							}
						}
					},
					plugins: [chart_counter],
				}
			);
		</script>
		<?php
	}

	echo "<div class='hud_details hud_box' id='hud_system_counts_details'>";
	echo "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "<tr>\n";
	echo "<th class='hud_heading' width='50%'>".$text['label-item']."</th>\n";
	echo "<th class='hud_heading' width='50%' style='text-align: center; padding-left: 0; padding-right: 0;'>".$text['label-disabled']."</th>\n";
	echo "<th class='hud_heading' style='text-align: center;'>".$text['label-total']."</th>\n";
	echo "</tr>\n";

	//domains
		if (permission_exists('domain_view')) {
			$tr_link = "href='".PROJECT_PATH."/core/domains/domains.php'";
			echo "<tr ".$tr_link.">\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-domains']."</a></td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['domains']['disabled']."</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['domains']['total']."</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}

	//devices
		if (permission_exists('device_view') && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/devices/")) {
			$tr_link = "href='".PROJECT_PATH."/app/devices/devices.php'";
			echo "<tr ".$tr_link.">\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-devices']."</a></td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['devices']['disabled']."</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['devices']['total']."</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}

	//extensions
		if (permission_exists('extension_view') && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/extensions/")) {
			$tr_link = "href='".PROJECT_PATH."/app/extensions/extensions.php'";
			echo "<tr ".$tr_link.">\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-extensions']."</a></td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['extensions']['disabled']."</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['extensions']['total']."</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}

	//gateways
		if (permission_exists('gateway_view') && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/gateways/")) {
			$tr_link = "href='".PROJECT_PATH."/app/gateways/gateways.php'";
			echo "<tr ".$tr_link.">\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-gateways']."</a></td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['gateways']['disabled']."</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['gateways']['total']."</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}

	//users
		if ((permission_exists('user_view') || if_group("superadmin")) && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/core/users/")) {
			$tr_link = "href='".PROJECT_PATH."/core/users/users.php'";
			echo "<tr ".$tr_link.">\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-users']."</a></td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['users']['disabled']."</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['users']['total']."</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}

	//destinations
		if (permission_exists('destination_view') && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/destinations/")) {
			$tr_link = "href='".PROJECT_PATH."/app/destinations/destinations.php'";
			echo "<tr ".$tr_link.">\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-destinations']."</a></td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['destinations']['disabled']."</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['destinations']['total']."</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}

	//call center queues
		if (permission_exists('call_center_active_view') && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/call_centers/")) {
			$tr_link = "href='".PROJECT_PATH."/app/call_centers/call_center_queues.php'";
			echo "<tr ".$tr_link.">\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-call_center_queues']."</a></td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['call_center_queues']['disabled']."</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['call_center_queues']['total']."</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}

	//ivr menus
		if (permission_exists('ivr_menu_view') && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/ivr_menus/")) {
			$tr_link = "href='".PROJECT_PATH."/app/ivr_menus/ivr_menus.php'";
			echo "<tr ".$tr_link.">\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-ivr_menus']."</a></td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['ivr_menus']['disabled']."</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['ivr_menus']['total']."</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}

	//ring groups
		if (permission_exists('ring_group_view') && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/ring_groups/")) {
			$tr_link = "href='".PROJECT_PATH."/app/ring_groups/ring_groups.php'";
			echo "<tr ".$tr_link.">\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-ring_groups']."</a></td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['ring_groups']['disabled']."</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['ring_groups']['total']."</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}

	//voicemails
		if (permission_exists('voicemail_view') && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/voicemails/")) {
			$tr_link = "href='".PROJECT_PATH."/app/voicemails/voicemails.php'";
			echo "<tr ".$tr_link.">\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-voicemail']."</a></td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['voicemails']['disabled']."</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['voicemails']['total']."</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}

	//messages
		if (permission_exists('voicemail_message_view') && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/voicemails/")) {
			echo "<tr>\n";
			echo "<th class='hud_heading' width='50%'>".$text['label-item']."</th>\n";
			echo "<th class='hud_heading' width='50%' style='text-align: center; padding-left: 0; padding-right: 0;'>".$text['label-new']."</th>\n";
			echo "<th class='hud_heading' style='text-align: center;'>".$text['label-total']."</th>\n";
			echo "</tr>\n";

			$tr_link = "href='".PROJECT_PATH."/app/voicemails/voicemails.php'";
			echo "<tr ".$tr_link.">\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-messages']."</a></td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['messages']['new']."</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['messages']['total']."</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}

	echo "</table>\n";
	echo "</div>";
	$n++;

	echo "<span class='hud_expander' onclick=\"$('#hud_system_counts_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>\n";
	echo "</div>\n";
?>
