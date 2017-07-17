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
	Portions created by the Initial Developer are Copyright (C) 2008-2017
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Matthew Vale <github@mafoo.org>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('number_translation_add')	|| permission_exists('number_translation_edit') ) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the action as an add or an update
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$number_translation_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}
	if (strlen($_REQUEST["app_uuid"]) > 0) {
		$app_uuid = $_REQUEST["app_uuid"];
	}

//get the http post values and set them as php variables
	if (count($_POST) > 0) {
		$hostname = check_str($_POST["hostname"]);
		$number_translation_name = check_str($_POST["number_translation_name"]);
		$number_translation_enabled = check_str($_POST["number_translation_enabled"]);
		$number_translation_description = check_str($_POST["number_translation_description"]);
	}

//process and save the data
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//get the number_translation uuid
			if ($action == "update") {
				$number_translation_uuid = check_str($_POST["number_translation_uuid"]);
			}

		//check for all required data
			$msg = '';
			if (strlen($number_translation_name) == 0) { $msg .= $text['message-required'].$text['label-name']."<br>\n"; }
			if (strlen($number_translation_enabled) == 0) { $msg .= $text['message-required'].$text['label-enabled']."<br>\n"; }
			//if (strlen($number_translation_description) == 0) { $msg .= $text['message-required'].$text['label-description']."<br>\n"; }
			if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
				require_once "resources/header.php";
				require_once "resources/persist_form_var.php";
				echo "<div align='center'>\n";
				echo "<table><tr><td>\n";
				echo $msg."<br />";
				echo "</td></tr></table>\n";
				persistformvar($_POST);
				echo "</div>\n";
				require_once "resources/footer.php";
				return;
			}

		//remove the invalid characters from the number_translation name
			$number_translation_name = $_POST["number_translation_name"];
			$number_translation_name = str_replace(" ", "_", $number_translation_name);
			$number_translation_name = str_replace("/", "", $number_translation_name);

		//build the array
			$x = 0;
			if (isset($_POST["number_translation_uuid"])) {
				$array['number_translations'][$x]['number_translation_uuid'] = $_POST["number_translation_uuid"];
			}
			$array['number_translations'][$x]['number_translation_name'] = $number_translation_name;
			$array['number_translations'][$x]['number_translation_enabled'] = $_POST["number_translation_enabled"];
			$array['number_translations'][$x]['number_translation_description'] = $_POST["number_translation_description"];
			$y = 0;
			if (is_array($_POST["number_translation_details"])) {
				foreach ($_POST["number_translation_details"] as $row) {
					if (strlen($row["number_translation_detail_regex"]) > 0) {
						if (strlen($row["number_translation_detail_uuid"]) > 0) {
							$array['number_translations'][$x]['number_translation_details'][$y]['number_translation_detail_uuid'] = $row["number_translation_detail_uuid"];
						}else{
							$array['number_translations'][$x]['number_translation_details'][$y]['number_translation_uuid'] = $_POST["number_translation_uuid"];
						}
						$array['number_translations'][$x]['number_translation_details'][$y]['number_translation_detail_regex'] = $row["number_translation_detail_regex"];
						$array['number_translations'][$x]['number_translation_details'][$y]['number_translation_detail_replace'] = $row["number_translation_detail_replace"];
						$array['number_translations'][$x]['number_translation_details'][$y]['number_translation_detail_order'] = $row["number_translation_detail_order"];
					}
					$y++;
				}
			}

		//add or update the database
			if ($_POST["persistformvar"] != "true") {
				$permissions = new permissions;
				$permissions->add('number_translation_detail_add', 'temp');
				$permissions->add('number_translation_detail_edit', 'temp');
				$database = new database;
				$database->app_name = 'number_translations';
				$database->save($array);
				$permissions->delete('number_translation_detail_add', 'temp');
				$permissions->delete('number_translation_detail_edit', 'temp');
				if ($database->message['code'] != '200'){
					messages::add('Failed to update record(s), database reported:'.$database->message['message'], 'negative');
					header("Location: ?id=$number_translation_uuid");
					exit;
				}
			}

		//update the number_translation xml
			$number_translations = new number_translation;
			$number_translations->xml();

		//set the message
			if ($action == "add") {
				messages::add($text['message-add']);
			}
			else if ($action == "update") {
				messages::add($text['message-update']);
			}
			header("Location: ?id=$number_translation_uuid");
			exit;

	} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$sql = "select * from v_number_translations ";
		$sql .= "where number_translation_uuid = '$number_translation_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		if (is_array($result)) foreach ($result as &$row) {
			$domain_uuid = $row["domain_uuid"];
			//$app_uuid = $row["app_uuid"];
			$hostname = $row["hostname"];
			$number_translation_name = $row["number_translation_name"];
			$number_translation_enabled = $row["number_translation_enabled"];
			$number_translation_description = $row["number_translation_description"];
		}
		unset ($prep_statement);
	}

//get the number_translation details in an array
	$sql = "select * from v_number_translation_details ";
	$sql .= "where number_translation_uuid = '$number_translation_uuid' ";
	$sql .= "order by number_translation_detail_order asc";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$results = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$results_count = count($result);
	unset ($prep_statement, $sql);

//show the header
	require_once "resources/header.php";
	$document['title'] = $text['title-number_translation_edit'];

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<input type='hidden' name='id' value='$number_translation_uuid'>\n";

	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"1\">\n";
	echo "	<tr>\n";
	echo "		<td align='left' width='30%'>\n";
	echo"			<span class=\"title\">".$text['title-number_translation_edit']."</span><br />\n";
	echo "		</td>\n";
	echo "		<td width='70%' align='right'>\n";
	echo "			<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='number_translations.php".((strlen($app_uuid) > 0) ? "?app_uuid=".$app_uuid : null)."';\" value='".$text['button-back']."'>\n";
	echo "			<input type='button' class='btn' name='' alt='".$text['button-copy']."' onclick=\"if (confirm('".$text['confirm-copy']."')){window.location='number_translation_copy.php?id=".$number_translation_uuid."';}\" value='".$text['button-copy']."'>\n";
	echo "			<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			".$text['description-number_translation-edit']."\n";
	echo "			\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>";
	echo "<br />\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td width='50%' style='vertical-align: top;'>\n";

	echo "	<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "	<td class='vncellreq' valign='top' align='left' nowrap='nowrap' width='30%'>\n";
	echo "		".$text['label-name']."\n";
	echo "	</td>\n";
	echo "	<td class='vtable' width='70%' align='left'>\n";
	echo "		<input class='formfld' type='text' name='number_translation_name' maxlength='255' placeholder='' value=\"".htmlspecialchars($number_translation_name)."\" required='required'>\n";
	echo "	</td>\n";
	echo "	</tr>\n";

	echo "	<tr>\n";
	echo "	<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "		".$text['label-hostname']."\n";
	echo "	</td>\n";
	echo "	<td class='vtable' align='left'>\n";
	echo "		<input class='formfld' type='text' name='hostname' maxlength='255' value=\"$hostname\">\n";
	echo "		<br />\n";
	echo "		".$text['description-hostname']."\n";
	echo "	</td>\n";
	echo "	</tr>\n";
	echo "	</table>\n";

	echo "</td>";
	echo "<td width='50%' style='vertical-align: top;'>\n";

	echo "	<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "	<tr>\n";
	echo "	<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "		".$text['label-enabled']."\n";
	echo "	</td>\n";
	echo "	<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='number_translation_enabled'>\n";
	if ($number_translation_enabled == "true") {
		echo "		<option value='true' selected='selected'>".$text['option-true']."</option>\n";
	}
	else {
		echo "		<option value='true'>".$text['option-true']."</option>\n";
	}
	if ($number_translation_enabled == "false") {
		echo "		<option value='false' selected='selected'>".$text['option-false']."</option>\n";
	}
	else {
		echo "		<option value='false'>".$text['option-false']."</option>\n";
	}
	echo "		</select>\n";
	echo "	</td>\n";
	echo "	</tr>\n";

	echo "	<tr>\n";
	echo "	<td class='vncell' valign='top' align='left' nowrap='nowrap' width='30%'>\n";
	echo "		".$text['label-description']."\n";
	echo "	</td>\n";
	echo "	<td class='vtable' align='left' width='70%'>\n";
	echo "		<textarea class='formfld' style='width: 250px; height: 68px;' name='number_translation_description'>".htmlspecialchars($number_translation_description)."</textarea>\n";
	echo "	</td>\n";
	echo "	</tr>\n";
	echo "	</table>\n";

	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "<br><br>";

	//number_translation details
	if ($action == "update") {
		?>
		<!--javascript to change select to input and back again-->
			<script language="javascript">

				function label_to_form(label_id, form_id) {
					if (document.getElementById(label_id) != null) {
						label = document.getElementById(label_id);
						label.parentNode.removeChild(label);
					}
					document.getElementById(form_id).style.display='';
				}

			</script>
		<?php

		//display the results
			if ($results_count > 0) {

				echo "<table width='100%' border='0' cellpadding='0' cellspacing='0' style='margin: -2px; border-spacing: 2px;'>\n";

				echo "<tr>\n";
				echo "<td class='vncellcolreq' stlye='width:40%'>".$text['label-regex']."</td>\n";
				echo "<td class='vncellcolreq' stlye='width:40%'>".$text['label-replace']."</td>\n";
				echo "<td class='vncellcolreq' style='text-align: center;'>".$text['label-order']."</td>\n";
				echo "<td>&nbsp;</td>\n";
				echo "</tr>\n";

				$x=0;
				$results[]['number_translation_uuid'] = $number_translation_uuid;
				foreach($results as $index => $row) {

					//get the values from the database and set as variables
						$number_translation_detail_uuid = $row['number_translation_detail_uuid'];
						$number_translation_detail_regex = $row['number_translation_detail_regex'];
						$number_translation_detail_replace = $row['number_translation_detail_replace'];
						$number_translation_detail_order = (strlen($row['number_translation_detail_order']) > 0 ? $row['number_translation_detail_order'] : $number_translation_detail_order + 5 );

					//no border on last row
						$no_border = (strlen($number_translation_detail_uuid) == 0) ? "border: none;" : null;

					//begin the row
						echo "<tr>\n";
					//determine whether to hide the element
						if (strlen($number_translation_detail_regex) == 0) {
							$element['hidden'] = false;
							$element['visibility'] = "";
						}
						else {
							$element['hidden'] = true;
							$element['visibility'] = "display: none;";
						}
					//add the primary key uuid
						if (strlen($number_translation_detail_uuid) > 0) {
							echo "	<input name='number_translation_details[".$x."][number_translation_detail_uuid]' type='hidden' value=\"".$number_translation_detail_uuid."\">\n";
						}
					//regex
						echo "<td class='vtablerow' onclick=\"label_to_form('label_number_translation_detail_regex_".$x."','number_translation_detail_regex_".$x."');\" style='".$no_border." width: 40%; max-width: 150px; overflow: hidden; _text-overflow: ellipsis; white-space: nowrap;' nowrap='nowrap'>\n";
						if ($element['hidden']) {
							echo "	<label id=\"label_number_translation_detail_regex_".$x."\">".htmlspecialchars($number_translation_detail_regex)."</label>\n";
						}
						echo "	<input id='number_translation_detail_regex_".$x."' name='number_translation_details[".$x."][number_translation_detail_regex]' class='formfld' type='text' style='width: calc(100% - 2px); min-width: calc(100% - 2px); max-width: calc(100% - 2px); ".$element['visibility']."' placeholder='' value=\"".htmlspecialchars($number_translation_detail_regex)."\">\n";
						echo "</td>\n";
					//replace
						echo "<td class='vtablerow' onclick=\"label_to_form('label_number_translation_detail_replace_".$x."','number_translation_detail_replace_".$x."');\" style='".$no_border." width: 40%; max-width: 150px; overflow: hidden; _text-overflow: ellipsis; white-space: nowrap;' nowrap='nowrap'>\n";
						if ($element['hidden']) {
							echo "	<label id=\"label_number_translation_detail_replace_".$x."\">".htmlspecialchars($number_translation_detail_replace)."</label>\n";
						}
						echo "	<input id='number_translation_detail_replace_".$x."' name='number_translation_details[".$x."][number_translation_detail_replace]' class='formfld' type='text' style='width: calc(100% - 2px); min-width: calc(100% - 2px); max-width: calc(100% - 2px); ".$element['visibility']."' placeholder='' value=\"".htmlspecialchars($number_translation_detail_replace)."\">\n";
						echo "</td>\n";
					//order
						echo "<td class='vtablerow' style='".$no_border." text-align: center;' onclick=\"label_to_form('label_number_translation_detail_order_".$x."','number_translation_detail_order_".$x."');\" nowrap='nowrap'>\n";
						if ($element['hidden']) {
							echo "	<label id=\"label_number_translation_detail_order_".$x."\">".$number_translation_detail_order."</label>\n";
						}
						echo "	<input id='number_translation_detail_order_".$x."' name='number_translation_details[".$x."][number_translation_detail_order]' class='formfld' type='number' min='0' step='1' style='width: 32px; text-align: center; ".$element['visibility']."' placeholder='' value=\"".htmlspecialchars($number_translation_detail_order)."\" onclick='this.select();'>\n";
						echo "</td>\n";
					//tools
						echo "	<td class='list_control_icon'>\n";
						if ($element['hidden']) {
							//echo "		<a href='number_translation_detail_edit.php?id=".$number_translation_detail_uuid."&number_translation_uuid=".$number_translation_uuid."&app_uuid=".$app_uuid."' alt='".$text['button-edit']."'>$v_link_label_edit</a>\n";
							echo "		<a href='number_translation_detail_delete.php?id=".$number_translation_detail_uuid."&number_translation_uuid=".$number_translation_uuid.(($app_uuid != '') ? "&app_uuid=".$app_uuid : null)."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
						}
						echo "	</td>\n";
					//end the row
						echo "</tr>\n";
					//increment the value
						$x++;
				}
				unset($sql, $result, $row_count);

				echo "</table>";

			} //end if results

	} //end if update

	echo "<br>\n";
	echo "<div align='right'>\n";
	if ($action == "update") {
		echo "	<input type='hidden' name='number_translation_uuid' value='$number_translation_uuid'>\n";
	}
	echo "	<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</div>\n";
	echo "<br><br>\n";
	echo "</form>";

	if (file_exists($_SERVER["PROJECT_ROOT"]."/app/billing/app_config.php")){
		echo "<p>".$text['billing-warning']."</p>";
	}

//show the footer
	require_once "resources/footer.php";

?>
