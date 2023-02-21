<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2017-2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permisions
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

//connect to the database
	if (!isset($database)) {
		$database = new database;
	}

//clear initial stat
	unset($hud_stat);

//domain limits
	if (is_array($_SESSION['limit']) && sizeof($_SESSION['limit']) > 0) {

		//set the row style
			$c = 0;
			$row_style["0"] = "row_style0";
			$row_style["1"] = "row_style1";

		//caller id
			echo "<div class='hud_box'>\n";

		//determine stats
			if (permission_exists('user_view')) {
				$sql_select[] = "(select count(user_uuid) from v_users where domain_uuid = :domain_uuid) as users";
			}
			if (permission_exists('call_center_active_view')) {
				$sql_select[] = "(select count(call_center_queue_uuid) from v_call_center_queues where domain_uuid = :domain_uuid) as call_center_queues";
			}
			if (permission_exists('destination_view')) {
				$sql_select[] = "(select count(destination_uuid) from v_destinations where domain_uuid = :domain_uuid) as destinations";
			}
			if (permission_exists('device_view')) {
				$sql_select[] = "(select count(device_uuid) from v_devices where domain_uuid = :domain_uuid) as devices";
			}
			if (permission_exists('extension_view')) {
				$sql_select[] = "(select count(extension_uuid) from v_extensions where domain_uuid = :domain_uuid) as extensions";
			}
			if (permission_exists('gateway_view')) {
				$sql_select[] = "(select count(gateway_uuid) from v_gateways where domain_uuid = :domain_uuid) as gateways";
			}
			if (permission_exists('ivr_menu_view')) {
				$sql_select[] = "(select count(ivr_menu_uuid) from v_ivr_menus where domain_uuid = :domain_uuid) as ivr_menus";
			}
			if (permission_exists('ring_group_view')) {
				$sql_select[] = "(select count(ring_group_uuid) from v_ring_groups where domain_uuid = :domain_uuid) as ring_groups";
			}
			if (is_array($sql_select) && @sizeof($sql_select) != 0) {
				$sql = "select ".implode(', ', $sql_select)." limit 1";
				$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
				$usage = $database->select($sql, $parameters, 'row');
				unset($sql, $parameters);
			}

		//determine chart data
			if (permission_exists('extension_view')) {
				$onclick = "onclick=\"document.location.href='".PROJECT_PATH."/app/extensions/extensions.php'\"";
				$hud_stat_used = $usage['extensions'];
				$hud_stat_remaining = $_SESSION['limit']['extensions']['numeric'] - $usage['extensions'];
				$hud_stat_title = $text['label-extensions'];
			}
			else if (permission_exists('destination_view')) {
				$onclick = "onclick=\"document.location.href='".PROJECT_PATH."/app/destinations/destinations.php'\"";
				$hud_stat_used = $usage['destinations'];
				$hud_stat_remaining = $_SESSION['limit']['destinations']['numeric'] - $usage['destinations'];
				$hud_stat_title = $text['label-destinations'];
			}

		//doughnut chart
			echo "<div style='display: flex; flex-wrap: wrap; justify-content: center; padding-bottom: 20px;' onclick=\"$('#hud_domain_limits_details').slideToggle('fast');\">\n";
			echo "	<div style='width: 275px; height: 175px;'><canvas id='domain_limits_chart'></canvas></div>\n";
			echo "</div>\n";

			echo "<script>\n";
			echo "	const domain_limits_chart = new Chart(\n";
			echo "		document.getElementById('domain_limits_chart').getContext('2d'),\n";
			echo "		{\n";
			echo "			type: 'doughnut',\n";
			echo "			data: {\n";
			echo "				labels: [\n";
			echo "					'".$hud_stat_title.": ".$hud_stat_used."',\n";
			echo "					'".$text['label-remaining'].": ".$hud_stat_remaining."',\n";
			echo "					],\n";
			echo "				datasets: [{\n";
			echo "					data: [\n";
			echo "						'".$hud_stat_used."',\n";
			echo "						'".$hud_stat_remaining."',\n";
			echo "						0.00001,\n";
			echo "						],\n";
			echo "					backgroundColor: [\n";
			echo "						'".$_SESSION['dashboard']['domain_limits_chart_color_used']['text']."',\n";
			echo "						'".$_SESSION['dashboard']['domain_limits_chart_color_remaining']['text']."',\n";
			echo "					],\n";
			echo "					borderColor: '".$_SESSION['dashboard']['domain_limits_chart_border_color']['text']."',\n";
			echo "					borderWidth: '".$_SESSION['dashboard']['domain_limits_chart_border_width']['text']."',\n";
			echo "					cutout: chart_cutout,\n";
			echo "				}]\n";
			echo "			},\n";
			echo "			options: {\n";
			echo "				responsive: true,\n";
			echo "				maintainAspectRatio: false,\n";
			echo "				plugins: {\n";
			echo "					chart_counter: {\n";
			echo "						chart_text: '".$hud_stat_used."'\n";
			echo "					},\n";
			echo "					legend: {\n";
			echo "						position: 'right',\n";
			echo "						reverse: false,\n";
			echo "						labels: {\n";
			echo "							usePointStyle: true,\n";
			echo "							pointStyle: 'rect'\n";
			echo "						}\n";
			echo "					},\n";
			echo "					title: {\n";
			echo "						display: true,\n";
			echo "						text: '".$text['label-domain_limits']."',\n";
			echo "						fontFamily: chart_text_font\n";
			echo "					}\n";
			echo "				}\n";
			echo "			},\n";
			echo "			plugins: [chart_counter],\n";
			echo "		}\n";
			echo "	);\n";
			echo "</script>\n";

		//details
			echo "<div class='hud_details hud_box' id='hud_domain_limits_details'>";

			echo "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
			echo "<tr style='position: -webkit-sticky; position: sticky; z-index: 5; top: 0;'>\n";
			echo "<th class='hud_heading' width='50%'>".$text['label-feature']."</th>\n";
			echo "<th class='hud_heading' width='50%' style='text-align: center;'>".$text['label-used']."</th>\n";
			echo "<th class='hud_heading' style='text-align: center;'>".$text['label-total']."</th>\n";
			echo "</tr>\n";

		//data
			foreach ($_SESSION['limit'] as $category => $value) {
				$used = $usage[$category];
				$limit = $value['numeric'];
				switch ($category) {
					case 'users':
						if (!permission_exists('user_view')) { continue 2; }
						$url = '/core/users/users.php';
						break;
					case 'call_center_queues':
						if (!permission_exists('call_center_active_view')) { continue 2; }
						$url = '/app/call_centers/call_center_queues.php';
						break;
					case 'destinations':
						if (!permission_exists('destination_view')) { continue 2; }
						$url = '/app/destinations/destinations.php';
						break;
					case 'devices':
						if (!permission_exists('device_view')) { continue 2; }
						$url = '/app/devices/devices.php';
						break;
					case 'extensions':
						if (!permission_exists('extension_view')) { continue 2; }
						$url = '/app/extensions/extensions.php';
						break;
					case 'gateways':
						if (!permission_exists('gateway_view')) { continue 2; }
						$url = '/app/gateways/gateways.php';
						break;
					case 'ivr_menus':
						if (!permission_exists('ivr_menu_view')) { continue 2; }
						$url = '/app/ivr_menus/ivr_menus.php';
						break;
					case 'ring_groups':
						if (!permission_exists('ring_group_view')) { continue 2; }
						$url = '/app/ring_groups/ring_groups.php';
						break;
				}
				$tr_link = "href='".PROJECT_PATH.$url."'";
				echo "<tr ".$tr_link." style='cursor: pointer;'>\n";
				echo "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-'.$category]."</a></td>\n";
				echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$used."</td>\n";
				echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$limit."</td>\n";
				echo "</tr>\n";
				$c = ($c) ? 0 : 1;
			}

			echo "</table>\n";
			echo "</div>";
			$n++;

			echo "<span class='hud_expander' onclick=\"$('#hud_domain_limits_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
			echo "</div>\n";
	}

?>