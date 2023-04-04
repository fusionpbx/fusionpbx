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

//check permissions
	if (permission_exists('extension_caller_id')) {

		//add multi-lingual support
			$language = new text;
			$text = $language->get($_SESSION['domain']['language']['code'], 'app/extensions');

		//connect to the database
			if (!isset($database)) {
				$database = new database;
			}

		//add or update the database
			if (is_array($_POST['extensions']) && @sizeof($_POST['extensions']) != 0) {

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER["DOCUMENT_ROOT"].'/extensions/resources/dashboard/caller_id.php')) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: /core/dashboard/');
						exit;
					}

				//build a new array to make sure it only contains what the user is allowed to change
					$x=0;
					foreach ($_POST['extensions'] as $row) {
						//loop through the extensions
							$found = false;
							foreach ($_SESSION['user']['extension'] as $field) {
								if ($field['extension_uuid'] == $row['extension_uuid']) {
									//set as found
										$found = true;
								}
							}

						//build the array on what is allowed.
							if ($found) {
								if (permission_exists('outbound_caller_id_select')) {
									$caller_id = explode('@', $row['outbound_caller_id']);
									$outbound_caller_id_name = $caller_id[0];
									$outbound_caller_id_number = $caller_id[1];
								}
								else {
									$outbound_caller_id_name = $row['outbound_caller_id_name'];
									$outbound_caller_id_number = $row['outbound_caller_id_number'];
								}
								$array['extensions'][$x]['extension_uuid'] = $row['extension_uuid'];
								$array['extensions'][$x]['outbound_caller_id_name'] = $outbound_caller_id_name;
								if (is_numeric($outbound_caller_id_number)) {
									$array['extensions'][$x]['outbound_caller_id_number'] = $outbound_caller_id_number;
								}
							}

						//increment the row id
							$x++;
					}

				//create temp array for session update
					$array_temp = $array;

				//add the dialplan permission
					$p = new permissions;
					$p->add("extension_edit", "temp");

				//save to the data
					$database->app_name = 'extensions';
					$database->app_uuid = 'e68d9689-2769-e013-28fa-6214bf47fca3';
					$database->save($array);
					$message = $database->message;

				//update the session array
					if ($message['message'] == 'OK' && $message['code'] == '200') {
						foreach ($array_temp['extensions'] as $row) {
							$x=0;
							foreach ($_SESSION['user']['extension'] as $field) {
								if ($field['extension_uuid'] == $row['extension_uuid']) {
									$_SESSION['user']['extension'][$x]['outbound_caller_id_name'] = $row['outbound_caller_id_name'];
									$_SESSION['user']['extension'][$x]['outbound_caller_id_number'] = $row['outbound_caller_id_number'];
								}
								$x++;
							}
						}
					}
					unset($array_temp);

				//remove the temporary permission
					$p->delete("extension_edit", "temp");

				//clear the cache
					$cache = new cache;
					foreach($_SESSION['user']['extension'] as $field) {
						$cache->delete("directory:".$field['destination']."@".$field['user_context']);
					}

				//set the message
					message::add($text['message-update']);

				//redirect the browser
					header("Location: /core/dashboard/");
					exit;
			}

		//get the extensions
			$extensions = $_SESSION['user']['extension'];

		//get the destinations
			if (permission_exists('outbound_caller_id_select')) {
				$sql = "select destination_caller_id_name, destination_caller_id_number from v_destinations ";
				$sql .= "where domain_uuid = :domain_uuid ";
				$sql .= "and destination_type = 'inbound' ";
				$sql .= "order by destination_caller_id_name asc, destination_caller_id_number asc";
				$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
				$destinations = $database->select($sql, $parameters, 'all');
				unset($sql, $parameters);
			}

		//determine stats
			if (is_array($extensions) && @sizeof($extensions) != 0) {
				foreach ($extensions as $row) {
					if (is_numeric($row['outbound_caller_id_number'])) {
						$stats['defined']++;
					}
					else {
						$stats['undefined']++;
					}
				}
			}

		//set defaults
			if ($stats['defined'] == null) { $stats['defined'] = 0; }
			if ($stats['undefined'] == null) { $stats['undefined'] = 0; }

		//set the row style
			$c = 0;
			$row_style["0"] = "row_style0";
			$row_style["1"] = "row_style1";

		//create token
			$object = new token;
			$token = $object->create($_SERVER["DOCUMENT_ROOT"].'/extensions/resources/dashboard/caller_id.php');

		//caller id
			echo "<div class='hud_box'>\n";

		//doughnut chart
			echo "<div style='display: flex; flex-wrap: wrap; justify-content: center; padding-bottom: 20px;' onclick=\"$('#hud_caller_id_details').slideToggle('fast');\">\n";
			echo "	<div style='width: 275px; height: 175px;'><canvas id='caller_id_chart'></canvas></div>\n";
			echo "</div>\n";

			echo "<script>\n";
			echo "	const caller_id_chart = new Chart(\n";
			echo "		document.getElementById('caller_id_chart').getContext('2d'),\n";
			echo "		{\n";
			echo "			type: 'doughnut',\n";
			echo "			data: {\n";
			echo "				labels: [\n";
			echo "					'".$text['label-defined'].": ".$stats['defined']."',\n";
			echo "					'".$text['label-undefined'].": ".$stats['undefined']."',\n";
			echo "					],\n";
			echo "				datasets: [{\n";
			echo "					data: [\n";
			echo "						'".$stats['defined']."',\n";
			echo "						'".$stats['undefined']."',\n";
			echo "						0.00001,\n";
			echo "						],\n";
			echo "					backgroundColor: [\n";
			echo "						'".$_SESSION['dashboard']['caller_id_chart_color_defined']['text']."',\n";
			echo "						'".$_SESSION['dashboard']['caller_id_chart_color_undefined']['text']."',\n";
			echo "					],\n";
			echo "					borderColor: '".$_SESSION['dashboard']['caller_id_chart_border_color']['text']."',\n";
			echo "					borderWidth: '".$_SESSION['dashboard']['caller_id_chart_border_width']['text']."',\n";
			echo "					cutout: chart_cutout,\n";
			echo "				}]\n";
			echo "			},\n";
			echo "			options: {\n";
			echo "				responsive: true,\n";
			echo "				maintainAspectRatio: false,\n";
			echo "				plugins: {\n";
			echo "					chart_counter: {\n";
			echo "						chart_text: '".$stats['undefined']."'\n";
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
			echo "						text: '".$text['label-caller_id_number']."'\n";
			echo "					}\n";
			echo "				}\n";
			echo "			},\n";
			echo "			plugins: [chart_counter],\n";
			echo "		}\n";
			echo "	);\n";
			echo "</script>\n";

		//details
			echo "<form id='form_list_caller_id' method='post' action='".PROJECT_PATH."/app/extensions/resources/dashboard/caller_id.php'>\n";

			echo "<div class='hud_details hud_box' id='hud_caller_id_details' style='text-align: right;'>";

			if (is_array($extensions) && @sizeof($extensions) != 0) {
				echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'collapse'=>false,'style'=>"position: absolute; margin-top: -35px; margin-left: -72px;"]);
			}

			echo "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
			echo "<tr style='position: -webkit-sticky; position: sticky; z-index: 5; top: 0;'>\n";
			echo "<th class='hud_heading'>".$text['label-extension']."</th>\n";
			echo "<th class='hud_heading'>".$text['label-caller_id']."</th>\n";
			if (!permission_exists('outbound_caller_id_select')) {
				echo "<th class='hud_heading'>".$text['label-destination']."</th>\n";
			}
			echo "</tr>\n";

		//data
			if (is_array($extensions) && @sizeof($extensions) != 0) {
				$x = 0;
				foreach ($extensions as $row) {
					$tr_link = PROJECT_PATH."/app/extensions/extension_edit.php?id=".$row['extension_uuid'];
					echo "<tr href='".$tr_link."'>\n";
					echo "	<td valign='top' class='".$row_style[$c]." hud_text'>";
					echo 		"<a href='".$tr_link."' title=\"".$text['button-edit']."\">".escape($row['destination'])."</a>";
					if (is_uuid($row['extension_uuid'])) {
						echo 	"<input type='hidden' name='extensions[".$x."][extension_uuid]' value=\"".escape($row['extension_uuid'])."\">\n";
					}
					echo "	</td>\n";
					//select caller id
					if (permission_exists('outbound_caller_id_select')) {
						echo "<td valign='top' class='".$row_style[$c]." hud_text input tr_link_void'>";
						if (count($destinations) > 0) {
							echo "<select class='formfld' name='extensions[".$x."][outbound_caller_id]' id='outbound_caller_id_number_".$x."' style='width: 100%; min-width: 150px;'>\n";
							echo "	<option value=''></option>\n";
							foreach ($destinations as &$field) {
								if (strlen($field['destination_caller_id_number']) > 0) {
									echo "<option value='".escape($field['destination_caller_id_name'])."@".escape($field['destination_caller_id_number'])."' ".($row['outbound_caller_id_number'] == $field['destination_caller_id_number'] ? "selected='selected'" : null).">".escape($field['destination_caller_id_name'])." ".escape($field['destination_caller_id_number'])."</option>\n";
								}
							}
							echo "</select>\n";
						}
						echo "</td>\n";
					}
					//input caller id
					else {
						echo "<td valign='top' class='".$row_style[$c]." hud_text input tr_link_void'>";
						echo "	<input class='formfld' style='width: 100%; min-width: 80px;' type='text' name='extensions[".$x."][outbound_caller_id_name]' maxlength='255' value=\"".escape($row['outbound_caller_id_name'])."\">\n";
						echo "</td>\n";
						echo "<td valign='top' class='".$row_style[$c]." hud_text input tr_link_void'>";
						echo "	<input class='formfld' style='width: 100%; min-width: 80px;' type='text' name='extensions[".$x."][outbound_caller_id_number]' maxlength='255' value=\"".$row['outbound_caller_id_number']."\">\n";
						echo "</td>\n";
					}
					echo "</tr>\n";
					$x++;
					$c = ($c) ? 0 : 1;
				}
				unset($extensions);
			}

			echo "</table>\n";
			echo "</div>";
			$n++;

			echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
			echo "</form>\n";

			echo "<span class='hud_expander' onclick=\"$('#hud_caller_id_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
			echo "</div>\n";

	}

?>