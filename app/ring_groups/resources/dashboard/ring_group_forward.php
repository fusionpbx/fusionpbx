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
	Portions created by the Initial Developer are Copyright (C) 2013-2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";

//add multi-lingual support
	$language = new text;
	$text = $language->get($_SESSION['domain']['language']['code'], 'app/ring_groups');

//get the list
	if (permission_exists('ring_group_add') || permission_exists('ring_group_edit')) {
		$domain_uuid = $_SESSION['domain_uuid'];
	}
	else {
		//show only assigned ring groups
		$domain_uuid = $_SESSION['user']['domain_uuid'];
	}

//connect to the database
	if (!isset($database)) {
		$database = new database;
	}

//find the path
	switch ($_SERVER['REQUEST_URI']) {
		case PROJECT_PATH."/core/dashboard/index.php":
			$validated_path = PROJECT_PATH."/core/dashboard/index.php";
			break;
		case PROJECT_PATH."/app/ring_groups/ring_group_forward.php":
			$validated_path = PROJECT_PATH."/app/ring_groups/ring_group_forward.php";
			break;
		default:
			$validated_path = PROJECT_PATH."/app/ring_groups/resources/dashboard/ring_group_forward.php";
	}

//update ring group forwarding
	if (is_array($_POST['ring_groups']) && @sizeof($_POST['ring_groups']) != 0 && permission_exists('ring_group_forward')) {

		//validate the token
			$token = new token;
			if (!$token->validate('/app/ring_groups/ring_group_forward.php')) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: '.$validated_path);
				exit;
			}

		$x = 0;
		foreach ($_POST['ring_groups'] as $row) {
			//build array
				if (is_uuid($row['ring_group_uuid'])) {
					$array['ring_groups'][$x]['ring_group_uuid'] = $row['ring_group_uuid'];
					$array['ring_groups'][$x]['ring_group_forward_enabled'] = $row['ring_group_forward_enabled'] == 'true' && $row['ring_group_forward_destination'] != '' ? 'true' : 'false';
					$array['ring_groups'][$x]['ring_group_forward_destination'] = $row['ring_group_forward_destination'];
				}
			//increment counter
				$x++;
		}

		if (is_array($array) && sizeof($array) != 0) {
			//update ring group
				$p = new permissions;
				$p->add('ring_group_edit', 'temp');

				$database->app_name = 'ring_groups';
				$database->app_uuid = '1d61fb65-1eec-bc73-a6ee-a6203b4fe6f2';
				$database->save($array);
				unset($array);

				$p->delete('ring_group_edit', 'temp');

			//set message
				message::add($text['message-update']);
				$validated_path = PROJECT_PATH."/core/dashboard/index.php";

			//redirect the user
				header("Location: ".$validated_path);
				exit;
		}
	}

//get the list
	if (permission_exists('ring_group_add') || permission_exists('ring_group_edit')) {
		//show all ring groups
		$sql = "select * from v_ring_groups ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	else {
		//show only assigned ring groups
		$sql = "select r.ring_group_name, r.ring_group_uuid, r.ring_group_extension, r.ring_group_forward_destination, ";
		$sql .= "r.ring_group_forward_enabled, r.ring_group_description from v_ring_groups as r, v_ring_group_users as u ";
		$sql .= "where r.ring_group_uuid = u.ring_group_uuid ";
		$sql .= "and r.domain_uuid = :domain_uuid ";
		$sql .= "and u.user_uuid = :user_uuid ";
		$parameters['domain_uuid'] = $_SESSION['user']['domain_uuid'];
		$parameters['user_uuid'] = $_SESSION['user']['user_uuid'];
	}
	$sql .= "order by ring_group_extension asc ";
	$result = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//determine keys and stats
	unset($stats);
	if (is_array($result) && @sizeof($result) != 0) {
		foreach ($result as $row) {
			$stats['forwarding'] += $row['ring_group_forward_enabled'] == 'true' && $row['ring_group_forward_destination'] ? 1 : 0;
		}
		$stats['active'] = @sizeof($result) - $stats['forwarding'];
	}

//set defaults
	if ($stats['forwarding'] == null) { $stats['forwarding'] = 0; }
	if ($stats['active'] == null) { $stats['active'] = 0; }

//set the row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//create token
	$object = new token;
	$token = $object->create('/app/ring_groups/ring_group_forward.php');

//ring group forward
	echo "<div class='hud_box'>\n";

//doughnut chart
	echo "<div style='display: flex; flex-wrap: wrap; justify-content: center; padding-bottom: 20px;' onclick=\"$('#hud_ring_group_forward_details').slideToggle('fast');\">\n";
	echo "	<div style='width: 275px; height: 175px;'><canvas id='ring_group_forward_chart'></canvas></div>\n";
	echo "</div>\n";

	echo "<script>\n";
	echo "	const ring_group_forward_chart = new Chart(\n";
	echo "		document.getElementById('ring_group_forward_chart').getContext('2d'),\n";
	echo "		{\n";
	echo "			type: 'doughnut',\n";
	echo "			data: {\n";
	echo "				labels: [\n";
	echo "					'".$text['label-active'].": ".$stats['active']."',\n";
	echo "					'".$text['label-forwarding'].": ".$stats['forwarding']."',\n";
	echo "					],\n";
	echo "				datasets: [{\n";
	echo "					data: [\n";
	echo "						'".$stats['active']."',\n";
	echo "						'".$stats['forwarding']."',\n";
	echo "						0.00001,\n";
	echo "						],\n";
	echo "					backgroundColor: [\n";
	echo "						'".$_SESSION['dashboard']['ring_group_forward_chart_color_active']['text']."',\n";
	echo "						'".$_SESSION['dashboard']['ring_group_forward_chart_color_forwarding']['text']."',\n";
	echo "					],\n";
	echo "					borderColor: '".$_SESSION['dashboard']['ring_group_forward_chart_border_color']['text']."',\n";
	echo "					borderWidth: '".$_SESSION['dashboard']['ring_group_forward_chart_border_width']['text']."',\n";
	echo "					cutout: chart_cutout,\n";
	echo "				}]\n";
	echo "			},\n";
	echo "			options: {\n";
	echo "				responsive: true,\n";
	echo "				maintainAspectRatio: false,\n";
	echo "				plugins: {\n";
	echo "					chart_counter: {\n";
	echo "						chart_text: '".$stats['forwarding']."'\n";
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
	echo "						text: '".$text['header-ring-group-forward']."'\n";
	echo "					}\n";
	echo "				}\n";
	echo "			},\n";
	echo "			plugins: [chart_counter],\n";
	echo "		}\n";
	echo "	);\n";
	echo "</script>\n";

//details
	echo "<form id='form_list_ring_group_forward' method='post' action='".$validated_path."'>\n";

	echo "<div class='hud_details hud_box' id='hud_ring_group_forward_details' style='text-align: right;'>";

	if (is_array($result) && @sizeof($result) != 0) {
		echo button::create(['type'=>'button','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'collapse'=>false,'style'=>"position: absolute; margin-top: -35px; margin-left: -72px;",'onclick'=>"list_form_submit('form_list_ring_group_forward');"]);
	}

	echo "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "<tr style='position: -webkit-sticky; position: sticky; z-index: 5; top: 0;'>\n";
	echo "<th class='hud_heading'>".$text['label-name']."</th>\n";
	echo "<th class='hud_heading'>".$text['label-extension']."</th>\n";
	echo "<th class='hud_heading'>".$text['label-forwarding']."</th>\n";
	echo "<th class='hud_heading'>".$text['label-destination']."</th>\n";
	echo "</tr>\n";

//data
	if (is_array($result) && @sizeof($result) != 0) {
		$x = 0;
		foreach ($result as $row) {
			$tr_link = PROJECT_PATH."/app/ring_groups/ring_group_edit.php?id=".$row['ring_group_uuid'];
			echo "<tr href='".$tr_link."'>\n";
			echo "	<td valign='top' class='".$row_style[$c]." hud_text'>".escape($row['ring_group_name'])."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]." hud_text'><a href='".$tr_link."' title=\"".$text['button-edit']."\">".escape($row['ring_group_extension'])."</a></td>\n";
			echo "	<td valign='top' class='".$row_style[$c]." hud_text input tr_link_void' style='width: 1%; text-align: center;'>";
			echo "		<input type='hidden' name='ring_groups[".$x."][ring_group_uuid]' value=\"".escape($row["ring_group_uuid"])."\">";
			// switch
			if (substr($_SESSION['theme']['input_toggle_style']['text'], 0, 6) == 'switch') {
				echo "	<label class='switch'>\n";
				echo "		<input type='checkbox' id='".escape($row['ring_group_uuid'])."' name='ring_groups[".$x."][ring_group_forward_enabled]' value='true' ".($row["ring_group_forward_enabled"] == 'true' ? "checked='checked'" : null)." onclick=\"this.checked && !document.getElementById('destination_".$x."').value ? document.getElementById('destination_".$x."').focus() : null;\">\n";
				echo "		<span class='slider'></span>\n";
				echo "	</label>\n";
			}
			// select
			else {
				echo "	<select class='formfld' id='".escape($row['ring_group_uuid'])."' name='ring_groups[".$x."][ring_group_forward_enabled]' onchange=\"this.selectedIndex && !document.getElementById('destination_".$x."').value ? document.getElementById('destination_".$x."').focus() : null;\">\n";
				echo "		<option value='false'>".$text['option-disabled']."</option>\n";
				echo "		<option value='true' ".($row["ring_group_forward_enabled"] == 'true' ? "selected='selected'" : null).">".$text['option-enabled']."</option>\n";
				echo "	</select>\n";
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]." hud_text input tr_link_void'>";
			echo "		<input class='formfld' style='width: 100%; min-width: 80px;' type='text' name='ring_groups[".$x."][ring_group_forward_destination]' id='destination_".$x."' placeholder=\"".$text['label-forward_destination']."\" maxlength='255' value=\"".escape($row["ring_group_forward_destination"])."\">";
			echo "	</td>\n";
			echo "</tr>\n";
			$x++;
			$c = ($c) ? 0 : 1;
		}
		unset($result);
	}

	echo "</table>\n";
	echo "</div>";
	$n++;

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

	echo "<span class='hud_expander' onclick=\"$('#hud_ring_group_forward_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
	echo "</div>\n";

?>