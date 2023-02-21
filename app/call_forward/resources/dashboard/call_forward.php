<?php

//includes
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('follow_me') || permission_exists('call_forward') || permission_exists('do_not_disturb')) {
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
	$text = $language->get($_SESSION['domain']['language']['code'], 'app/call_forward');

//set the row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//get data
	$sql = "
		select
			extension_uuid,
			extension,
			forward_all_enabled,
			forward_all_destination,
			follow_me_enabled,
			follow_me_uuid,
			do_not_disturb
		from
			v_extensions ";
	if ($_GET['show'] == "all" && permission_exists('call_forward_all')) {
		$sql .= "where true ";
	}
	else {
		$sql .= "where domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	}
	$sql .= "and enabled = 'true' ";
	if (!permission_exists('extension_edit')) {
		if (is_array($_SESSION['user']['extension']) && count($_SESSION['user']['extension']) > 0) {
			$sql .= "and (";
			$x = 0;
			foreach($_SESSION['user']['extension'] as $row) {
				if ($x > 0) { $sql .= "or "; }
				$sql .= "extension = '".$row['user']."' ";
				$x++;
			}
			$sql .= ")";
		}
		else {
			//used to hide any results when a user has not been assigned an extension
			$sql .= "and extension = 'disabled' ";
		}
	}
	$sql .= order_by($order_by, $order, 'extension', 'asc');
	$database = new database;
	$extensions = $database->select($sql, $parameters, 'all');
	unset($parameters);

//determine keys and stats
	unset($stats);
	$show_stat = false;
	if (is_array($extensions) && @sizeof($extensions) != 0) {
		foreach ($extensions as $row) {
			if (permission_exists('call_forward')) {
				$stats['call_forward'] += $row['forward_all_enabled'] == 'true' && $row['forward_all_destination'] ? 1 : 0;
			}
			if (permission_exists('follow_me')) {
				$stats['follow_me'] += $row['follow_me_enabled'] == 'true' && is_uuid($row['follow_me_uuid']) ? 1 : 0;
			}
			if (permission_exists('do_not_disturb')) {
				$stats['dnd'] += $row['do_not_disturb'] == 'true' ? 1 : 0;
			}
		}
		$stats['active'] = @sizeof($extensions) - $stats['call_forward'] - $stats['follow_me'] - $stats['dnd'];
	}
	if (is_array($stats) && @sizeof($stats) != 0) {
		$show_stat = true;
	}

//begin widget
	echo "<div class='hud_box'>\n";

//set defaults
	if ($stats['dnd'] == null) { $stats['dnd'] = 0; }
	if ($stats['follow_me'] == null) { $stats['follow_me'] = 0; }
	if ($stats['call_forward'] == null) { $stats['call_forward'] = 0; }
	if ($stats['active'] == null) { $stats['active'] = 0; }

//doughnut chart
	echo "<div style='display: flex; flex-wrap: wrap; justify-content: center; padding-bottom: 20px;' onclick=\"$('#hud_call_forward_details').slideToggle('fast');\">\n";
	echo "	<div style='width: 275px; height: 175px;'><canvas id='call_forward_chart'></canvas></div>\n";
	echo "</div>\n";

	echo "<script>\n";
	echo "	const call_forward_chart = new Chart(\n";
	echo "		document.getElementById('call_forward_chart').getContext('2d'),\n";
	echo "		{\n";
	echo "			type: 'doughnut',\n";
	echo "			data: {\n";
	echo "				labels: [\n";
	if (permission_exists('do_not_disturb')) {
		echo "				'".$text['label-dnd'].": ".$stats['dnd']."',\n";
	}
	if (permission_exists('follow_me')) {
		echo "				'".$text['label-follow_me'].": ".$stats['follow_me']."',\n";
	}
	if (permission_exists('call_forward')) {
		echo "				'".$text['label-call_forward'].": ".$stats['call_forward']."',\n";
	}
	echo "					'".$text['label-active'].": ".$stats['active']."',\n";
	echo "					],\n";
	echo "				datasets: [{\n";
	echo "					data: [\n";
	if (permission_exists('do_not_disturb')) {
		echo "					'".$stats['dnd']."',\n";
	}
	if (permission_exists('follow_me')) {
		echo "					'".$stats['follow_me']."',\n";
	}
	if (permission_exists('call_forward')) {
		echo "					'".$stats['call_forward']."',\n";
	}
	echo "						'".$stats['active']."',\n";
	echo "						0.00001,\n";
	echo "						],\n";
	echo "					backgroundColor: [\n";
	if (permission_exists('do_not_disturb')) {
		echo "					'".$_SESSION['dashboard']['call_forward_chart_color_do_not_disturb']['text']."',\n";
	}
	if (permission_exists('follow_me')) {
		echo "					'".$_SESSION['dashboard']['call_forward_chart_color_follow_me']['text']."',\n";
	}
	if (permission_exists('call_forward')) {
		echo "					'".$_SESSION['dashboard']['call_forward_chart_color_call_forward']['text']."',\n";
	}
	echo "						'".$_SESSION['dashboard']['call_forward_chart_color_active']['text']."',\n";
	echo "						'".$_SESSION['dashboard']['call_forward_chart_color_active']['text']."',\n";
	echo "					],\n";
	echo "					borderColor: '".$_SESSION['dashboard']['call_forward_chart_border_color']['text']."',\n";
	echo "					borderWidth: '".$_SESSION['dashboard']['call_forward_chart_border_width']['text']."',\n";
	echo "					cutout: chart_cutout,\n";
	echo "				}]\n";
	echo "			},\n";
	echo "			options: {\n";
	echo "				responsive: true,\n";
	echo "				maintainAspectRatio: false,\n";
	echo "				plugins: {\n";
	echo "					chart_counter: {\n";
	echo "						chart_text: '".$stats['call_forward']."'\n";
	echo "					},\n";
	echo "					legend: {\n";
	echo "						position: 'right',\n";
	echo "						reverse: true,\n";
	echo "						labels: {\n";
	echo "							usePointStyle: true,\n";
	echo "							pointStyle: 'rect'\n";
	echo "						}\n";
	echo "					},\n";
	echo "					title: {\n";
	echo "						display: true,\n";
	echo "						text: '".$text['header-call_forward']."'\n";
	echo "					}\n";
	echo "				}\n";
	echo "			},\n";
	echo "			plugins: [chart_counter],\n";
	echo "		}\n";
	echo "	);\n";
	echo "</script>\n";

//details
	echo "<div class='hud_details hud_box' id='hud_call_forward_details'>";
	echo "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "<tr style='position: -webkit-sticky; position: sticky; z-index: 5; top: 0;'>\n";
	echo "<th class='hud_heading'>".$text['label-extension']."</th>\n";
	if (permission_exists('call_forward')) {
		echo "	<th class='hud_heading' style='text-align: center;'>".$text['label-call_forward']."</th>\n";
	}
	if (permission_exists('follow_me')) {
		echo "	<th class='hud_heading' style='text-align: center;'>".$text['label-follow_me']."</th>\n";
	}
	if (permission_exists('do_not_disturb')) {
		echo "	<th class='hud_heading' style='text-align: center;'>".$text['label-dnd']."</th>\n";
	}
	echo "</tr>\n";

// data
	if (is_array($extensions) && @sizeof($extensions) != 0) {
		foreach ($extensions as $row) {
			$tr_link = PROJECT_PATH."/app/call_forward/call_forward_edit.php?id=".$row['extension_uuid'];
			echo "<tr href='".$tr_link."'>\n";
			echo "	<td valign='top' class='".$row_style[$c]." hud_text'><a href='".$tr_link."' title=\"".$text['button-edit']."\">".escape($row['extension'])."</a></td>\n";
			if (permission_exists('call_forward')) {
				echo "	<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".($row['forward_all_enabled'] == 'true' ? escape(format_phone($row['forward_all_destination'])) : '&nbsp;')."</td>\n";
			}
			if (permission_exists('follow_me')) {
				//get destination count
				$follow_me_destination_count = 0;
				if ($row['follow_me_enabled'] == 'true' && is_uuid($row['follow_me_uuid'])) {
					$sql = "select count(*) from v_follow_me_destinations ";
					$sql .= "where follow_me_uuid = :follow_me_uuid ";
					$sql .= "and domain_uuid = :domain_uuid ";
					$parameters['follow_me_uuid'] = $row['follow_me_uuid'];
					$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
					$database = new database;
					$follow_me_destination_count = $database->select($sql, $parameters, 'column');
					unset($sql, $parameters);
				}
				echo "	<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".($follow_me_destination_count ? $text['label-enabled'].' ('.$follow_me_destination_count.')' : '&nbsp;')."</td>\n";
			}
			if (permission_exists('do_not_disturb')) {
				echo "	<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".($row['do_not_disturb'] == 'true' ? $text['label-enabled'] : '&nbsp;')."</td>\n";
			}
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}
		unset($extensions);
	}

	echo "</table>\n";
	echo "</div>";
	$n++;

	echo "<span class='hud_expander' onclick=\"$('#hud_call_forward_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>\n";
	echo "</div>\n";

?>
