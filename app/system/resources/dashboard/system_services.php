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
 * Portions created by the Initial Developer are Copyright (C) 2008-2025
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

//function to check for running process: returns [running, pid, etime]
	if (!function_exists('is_running')) {
		function is_running(string $name) {
			$name = escapeshellarg($name);
			$pid = trim(shell_exec("ps -aux | grep $name | grep -v grep | awk '{print \$2}' | head -n 1"));
			if ($pid && is_numeric($pid)) {
				$etime = trim(shell_exec("ps -p $pid -o etime= | tr -d '\n'"));
				return ['running' => true, 'pid' => $pid, 'etime' => $etime];
			}
			return ['running' => false, 'pid' => null, 'etime' => null];
		}
	}

//function to format etime into friendly display
	if (!function_exists('format_etime')) {
		function format_etime($etime) {
			// Format: [[dd-]hh:]mm:ss
			if (empty($etime)) return '-';

			$days = 0; $hours = 0; $minutes = 0; $seconds = 0;

			// Handle dd-hh:mm:ss
			if (preg_match('/^(\d+)-(\d+):(\d+):(\d+)$/', $etime, $m)) {
				[$_, $days, $hours, $minutes, $seconds] = $m;
			}
			// Handle hh:mm:ss
			elseif (preg_match('/^(\d+):(\d+):(\d+)$/', $etime, $m)) {
				[$_, $hours, $minutes, $seconds] = $m;
			}
			// Handle mm:ss
			elseif (preg_match('/^(\d+):(\d+)$/', $etime, $m)) {
				[$_, $minutes, $seconds] = $m;
			}

			$out = [];
			if ($days)		$out[] = $days . 'd';
			if ($hours)	 $out[] = $hours . 'h';
			if ($minutes) $out[] = $minutes . 'm';
			if ($seconds || empty($out)) $out[] = $seconds . 's';

			return implode(' ', $out);
		}
	}

//friendly labels
	$service_labels = [
		'email_queue'	 => 'Email Queue',
		'event_guard'	 => 'Event Guard',
		'fax_queue'		 => 'Fax Queue',
		'maintenance_service' => 'Maintenance Service',
		'message_events'			=> 'Message Events',
		'message_queue'			 => 'Message Queue',
		'xml_cdr'			 => 'XML CDR',
		'freeswitch'		=> 'FreeSWITCH',
		'nginx'				 => 'Nginx',
		'postgresql'		=> 'PostgreSQL',
		'event_guard'	 => 'Event Guard',
		'sshd'		=> 'SSH Server'
	];

	$files = glob(PROJECT_ROOT . '/*/*/resources/service/*.service');
	$services = [];
	$total_running = 0;

	// load FusionPBX installed services
	foreach ($files as $file) {
		$service = get_classname($file);
		//check if the service name was found
		if (!empty($service)) {
			$basename = basename($service, '.php');
			$info = is_running($service);
			$info['label'] = $service_labels[$basename] ?? ucwords(str_replace('_', ' ', $basename));
			$services[$basename] = $info;
			if ($info['running']) $total_running++;
		}
	}

	// Get extra system services from default settings
	$extra_services_string = $settings->get('theme', 'dashboard_extra_system_services');

	// Only proceed if the setting is not empty
	if (!empty($extra_services_string) && is_string($extra_services_string)) {
		// Convert comma-separated list to array
		$extra_services = array_filter(array_map('trim', explode(',', $extra_services_string)));

		// Loop through extra services if array is not empty
		if (!empty($extra_services)) {
			foreach ($extra_services as $extra) {
				if (!isset($services[$extra])) {
					$info = is_running($extra);
					$info['label'] = $service_labels[$extra] ?? ucwords($extra);
					$services[$extra] = $info;
					if ($info['running']) $total_running++;
				}
			}
		}
	}

//track total installed services for charts
	$total_services = count($services);

//add multi-lingual support
	$text = (new text())->get($settings->get('domain','language','en-us'), 'core/user_settings');

//show the results
echo "<div class='hud_box'>\n";
echo "	<div class='hud_content' ".($dashboard_details_state == 'disabled' ?: "onclick=\"$('#hud_system_services_details').slideToggle('fast');\""). ">\n";
echo "		<span class='hud_title'>System Services</span>\n";

//doughnut chart
if (!isset($dashboard_chart_type) || $dashboard_chart_type == "doughnut") {
	echo "	<div class='hud_chart' style='width: 250px;'><canvas id='system_services_chart'></canvas></div>\n";
	echo "	<script>\n";
	echo "		const system_services_chart = new Chart (\n";
	echo "			document.getElementById('system_services_chart').getContext('2d'),\n";
	echo "			{\n";
	echo "				type: 'doughnut',\n";
	echo "				data: {\n";
	echo "					labels: ['Active: ".$total_running."' , 'Inactive: ".($total_services-$total_running)."'],\n";
	echo "					datasets: [{\n";
	echo "							data: ['".$total_running."','".($total_services-$total_running)."'],\n";
	echo "							backgroundColor: [\n";
	echo "								'".$settings->get('theme', 'dashboard_system_counts_chart_main_color','#2a9df4')."',\n";
	echo "								'".$settings->get('theme', 'dashboard_system_counts_chart_sub_color','#d4d4d4')."'\n";
	echo "							],\n";
	echo "							borderColor: '".$settings->get('theme', 'dashboard_chart_border_color')."',\n";
	echo "							borderWidth: '".$settings->get('theme', 'dashboard_chart_border_width')."'\n";
	echo "					}]\n";
	echo "				},\n";
	echo "				options: {\n";
	echo "					plugins: {\n";
	echo "						chart_number: {\n";
	echo "							text: '$total_services'\n";
	echo "						},\n";
	echo "						legend: {\n";
	echo "							display: true,\n";
	echo "							position: 'right',\n";
	echo "							labels: {\n";
	echo "								usePointStyle: true,\n";
	echo "								pointStyle: 'rect',\n";
	echo "								color: '$dashboard_heading_text_color'\n";
	echo "							}\n";
	echo "						}\n";
	echo "					}\n";
	echo "				},\n";
	echo "				plugins: [{\n";
	echo "					id: 'chart_number',\n";
	echo "					beforeDraw(chart, args, options) {\n";
	echo "						const {ctx, chartArea: {top, right, bottom, left, width, height} } = chart;\n";
	echo "						ctx.font = chart_text_size + ' ' + chart_text_font;\n";
	echo "						ctx.textBaseline = 'middle';\n";
	echo "						ctx.textAlign = 'center';\n";
	echo "						ctx.fillStyle = '$dashboard_number_text_color';\n";
	echo "						ctx.fillText(options.text, width / 2, top + (height / 2));\n";
	echo "						ctx.save();\n";
	echo "					}\n";
	echo "				}]\n";
	echo "			}\n";
	echo "		);\n";
	echo "	</script>\n";
}
if ($dashboard_chart_type == "number") {
	echo "	<span class='hud_stat'>".$total_services."</span>";
}
echo "	</div>\n";

if ($dashboard_details_state != 'disabled') {
	echo "	<div class='hud_details hud_box' id='hud_system_services_details'>\n";
	echo "		<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "			<tr>\n";
	echo "				<th class='hud_heading' width='45%'>".($text['label-service'] ?? 'Service')."</th>\n";
	echo "				<th class='hud_heading' width='20%' style='text-align: center;'>".($text['label-running'] ?? 'Running')."</th>\n";
	echo "				<th class='hud_heading' width='35%' style='text-align: center;'>".($text['label-runtime'] ?? 'Runtime')."</th>\n";
	echo "			</tr>\n";

	$row_style[false] = "row_style0";
	$row_style[true] = "row_style1";
	$c = true;
	foreach ($services as $info) {
		$label = $info['label'];
		$status = $info['running']
			? "<span style='background-color: #28a745; color: white; padding: 2px 8px; border-radius: 10px;'>Yes</span>"
			: "<span style='background-color: #dc3545; color: white; padding: 2px 8px; border-radius: 10px;'>No</span>";
		$etime = isset($info['etime']) ? format_etime($info['etime']) : '-';
		$pid = $info['pid'] ?? '';
		$tooltip_attr = $pid ? "title='PID: $pid'" : '';

		echo "			<tr>\n";
		echo "	<td class='{$row_style[$c]}' hud_text $tooltip_attr>$label</td>\n";
		echo "	<td class='{$row_style[$c]}' hud_text style='text-align: center;' $tooltip_attr>$status</td>\n";
		echo "	<td class='{$row_style[$c]}' hud_text style='text-align: center;' $tooltip_attr>$etime</td>\n";
		echo "			</tr>\n";
		$c = !$c;
	}

	echo "		</table>\n";
	echo "	</div>\n";
	echo "<span class='hud_expander' onclick=\"$('#hud_system_services_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
}
echo "</div>\n";
