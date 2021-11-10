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

//system counts
	//if (is_array($selected_blocks) && in_array('counts', $selected_blocks)) {
		$c = 0;
		$row_style["0"] = "row_style0";
		$row_style["1"] = "row_style1";

		$scope = (permission_exists('dialplan_add')) ? 'system' : 'domain';

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

		if ($show_stat) {
			echo "
				<div style='display: flex; flex-wrap: wrap; justify-content: center; padding-bottom:10px;'>
					<div style='width: 250px; height: 175px;'><canvas id='system_count_chart'></canvas></div>
				</div>

				<script>
					var system_count_bgc = ['#d4d4d4', '#2a9df4'];

					const system_count_data = {
						labels: ['InActive: ".$domain_inactive."', 'Active: ".$domain_active."'],
						datasets: [{
							data:[".$domain_inactive.", ".$domain_active."],
							borderColor: 'rgba(0,0,0,0)',
							backgroundColor: ['#d4d4d4', '#2a9df4'],
							cutout: chart_cutout
						}]
					};

					const system_count_config = {
						type: 'doughnut',
						data: system_count_data,
						options: {
						responsive: true,
							maintainAspectRatio: false,
							plugins: {
								chart_counter: {
									chart_text: ".$domain_total."
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
									text: '".$text['label-system_counts']."'
								}
							}
						},
						plugins: [chart_counter],
					};

					const system_count_chart = new Chart(
						document.getElementById('system_count_chart'),
						system_count_config
					);
				</script>
			";
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
	//}
	echo "			<span class='hud_expander' onclick=\"$('#hud_system_counts_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";

?>
