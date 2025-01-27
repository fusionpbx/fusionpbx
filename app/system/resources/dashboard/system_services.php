<?php

/*
 * FusionPBX
 * Version: MPL 1.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is FusionPBX
 *
 * The Initial Developer of the Original Code is
 * Mark J Crane <markjcrane@fusionpbx.com>
 * Portions created by the Initial Developer are Copyright (C) 2008-2024
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 * Mark J Crane <markjcrane@fusionpbx.com>
 * Tim Fry <tim@fusionpbx.com>
 */

//includes files
	require_once dirname(__DIR__, 4) . "/resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('xml_cdr_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

	$dashboard_name = "System Services";

//function to parse a FusionPBX service from a .service file
	if (!function_exists('get_classname')) {
		function get_classname(string $file) {
			$parsed = parse_ini_file($file);
			$exec_cmd = $parsed['ExecStart'];
			$parts = explode(' ', $exec_cmd);
			$php_file = $parts[1] ?? '';
			if (!empty($php_file)) {
				return $php_file;
			}
			return '';
		}
	}

//function to check for running process
	if (!function_exists('is_running')) {
		function is_running(string $name) {
			$output = '';

			//escape for better safety
			$name = escapeshellarg($name);

		    // Use pgrep to search for the program by its name
			$output = shell_exec("ps -aux | grep $name | grep -v grep");

			// If there is a process id then the program is running
			return ($output !== null && strlen($output) > 0);
		}
	}

//load installed services
	$files = glob(PROJECT_ROOT . '/*/*/resources/service/*.service');
	$services = [];
	$total_running = 0;
	foreach ($files as $file) {
		$service = get_classname($file);
		//check if the service name was found
		if (!empty($service)) {
			$basename = basename($service, '.php');
			//clean up the name
			//$basename = ucwords(str_replace('_', ' ', $basename));
			//check if service is running
			$services[$basename] = is_running($service);
			//keep total count for charts
			if ($services[$basename]) {
				++$total_running;
			}
		}
	}

//track total installed services for charts
	$total_services = count($services);

//add multi-lingual support
	$text = (new text())->get($settings->get('domain','language','en-us'), 'core/user_settings');

//show the results
echo "<div class='hud_box'>\n";
echo "	<div class='hud_content' ".($dashboard_details_state == 'disabled' ?: "onclick=\"$('#hud_system_services_details').slideToggle('fast'); toggle_grid_row_end('$dashboard_name')\""). ">\n";
echo "		<span class='hud_title'>System Services</span>\n";
echo "		<div class='hud_chart' style='width: 250px;'><canvas id='system_services_chart'></canvas></div>\n";
echo "	</div>\n";
echo "		<script>\n";
echo "			const system_services_chart = new Chart (\n";
echo "				document.getElementById('system_services_chart').getContext('2d'),\n";
echo "				{\n";
echo "					type: 'doughnut',\n";
echo "					data: {\n";
echo "						labels: ['Active: $total_running' , 'Inactive: ".$total_services-$total_running."'],\n";
echo "						datasets: [{\n";
echo "								data: ['5','".$total_services-$total_running."'],\n";
echo "								backgroundColor: [\n";
echo "									'".$settings->get('theme', 'dashboard_system_counts_chart_main_color','#2a9df4')."',\n";
echo "									'".$settings->get('theme', 'dashboard_system_counts_chart_sub_color','#d4d4d4')."'\n";
echo "								],\n";
echo "								borderColor: '".$settings->get('theme', 'dashboard_chart_border_color')."',\n";
echo "								borderWidth: '".$settings->get('theme', 'dashboard_chart_border_width')."'\n";
echo "						}]\n";
echo "					},\n";
echo "					options: {\n";
echo "						plugins: {\n";
echo "							chart_number: {\n";
echo "								text: '$total_services'\n";
echo "							},\n";
echo "							legend: {\n";
echo "								display: true,\n";
echo "								position: 'right',\n";
echo "								labels: {\n";
echo "									usePointStyle: true,\n";
echo "									pointStyle: 'rect',\n";
echo "									color: '$dashboard_heading_text_color'\n";
echo "								}\n";
echo "							}\n";
echo "						}\n";
echo "					},\n";
echo "					plugins: [{\n";
echo "						id: 'chart_number',\n";
echo "						beforeDraw(chart, args, options) {\n";
echo "							const {ctx, chartArea: {top, right, bottom, left, width, height} } = chart;\n";
echo "							ctx.font = chart_text_size + ' ' + chart_text_font;\n";
echo "							ctx.textBaseline = 'middle';\n";
echo "							ctx.textAlign = 'center';\n";
echo "							ctx.fillStyle = '$dashboard_number_text_color';\n";
echo "							ctx.fillText(options.text, width / 2, top + (height / 2));\n";
echo "							ctx.save();\n";
echo "						}\n";
echo "					}]\n";
echo "				}\n";
echo "			);\n";
echo "		</script>\n";

if ($dashboard_details_state != 'disabled') {
echo "	<div class='hud_details hud_box' id='hud_system_services_details'>\n";
echo "		<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
echo "			<tr>\n";
echo "				<th class='hud_heading' width='50%'>".($text['label-service'] ?? 'Service')."</th>\n";
echo "				<th class='hud_heading' width='50%' style='text-align: center; padding-left: 0; padding-right: 0;'>".($text['label-running'] ?? 'Running')."</th>\n";
echo "			</tr>\n";
		$row_style[false] = "row_style0";
		$row_style[true] = "row_style1";
		$c = true;
		foreach ($services as $name => $enabled) {
			echo "  <tr>\n";
			echo "    <td valign='top' class='{$row_style[$c]}' hud_text>$name</td>\n";
			echo "    <td valign='top' class='{$row_style[$c]}' hud_text style='text-align: center;'>" . ($enabled ? $text['label-yes'] ?? 'Yes' : $text['label-no'] ?? 'No') . "</td>\n";
			echo "  </tr>\n";
			$c = !$c;
		}
echo "		</table>\n";
echo "	</div>\n";
}
echo "</div>\n";
