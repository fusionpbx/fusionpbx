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
	Portions created by the Initial Developer are Copyright (C) 2026
	the Initial Developer. All Rights Reserved.
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!(permission_exists('paging_add') || permission_exists('paging_edit'))) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//connect to the database
	$database = database::new();

//add the settings object
	$settings = new settings(["domain_uuid" => $_SESSION['domain_uuid'], "user_uuid" => $_SESSION['user_uuid']]);

//set from session variables
	$button_icon_back = $settings->get('theme', 'button_icon_back', '');
	$button_icon_copy = $settings->get('theme', 'button_icon_copy', '');
	$button_icon_delete = $settings->get('theme', 'button_icon_delete', '');
	$button_icon_save = $settings->get('theme', 'button_icon_save', '');
	$input_toggle_style = $settings->get('theme', 'input_toggle_style', 'switch round');

//action add or update
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$paging_uuid = $_REQUEST["id"];
		$id = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//set the defaults
	$paging_uuid = '';
	$paging_extension = '';
	$dialplan_uuid = '';
	$paging_pin_number = '';
	$paging_caller_id_name = '';
	$paging_caller_id_number = '';
	$paging_sound = '';
	$paging_delay = '';
	$paging_mute = '';
	$paging_destination_status = '';
	$paging_hangup_all = '';
	$paging_schedule_hangup = '';
	$paging_enabled = '';
	$paging_description = '';
	$paging_destinations = [];
	$paging_destination_uuid = '';

//get http post variables and set them to php variables
	if (!empty($_POST)) {
		$paging_extension = $_POST["paging_extension"] ?? null;
		$dialplan_uuid = $_POST["dialplan_uuid"] ?? null;
		$paging_pin_number = $_POST["paging_pin_number"] ?? null;
		$paging_destinations = $_POST["paging_destinations"] ?? null;
		$paging_caller_id_name = $_POST["paging_caller_id_name"] ?? null;
		$paging_caller_id_number = $_POST["paging_caller_id_number"] ?? null;
		$paging_sound = $_POST["paging_sound"] ?? null;
		$paging_delay = $_POST["paging_delay"] ?? null;
		$paging_mute = $_POST["paging_mute"] ?? null;
		$paging_destination_status = $_POST["paging_destination_status"] ?? null;
		$paging_hangup_all = $_POST["paging_hangup_all"] ?? null;
		$paging_schedule_hangup = $_POST["paging_schedule_hangup"] ?? null;
		$paging_enabled = $_POST["paging_enabled"] ?? null;
		$paging_description = $_POST["paging_description"] ?? null;
	}

//process the data and save it to the database
	if (!empty($_POST) && empty($_POST["persistformvar"])) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: paging.php');
				exit;
			}

		//process the http post data by submitted action
			if ($_POST['action'] != '' && strlen($_POST['action']) > 0) {

				//prepare the array
				$array[0]['checked'] = 'true';
				$array[0]['uuid'] = $paging_uuid;

				//send the array to the database class
				switch ($_POST['action']) {
					case 'copy':
						if (permission_exists('paging_add')) {
							$obj = new paging;
							$obj->copy($array);
						}
						break;
					case 'delete':
						if (permission_exists('paging_delete')) {
							$obj = new paging;
							$obj->delete($array);
						}
						break;
					case 'toggle':
						if (permission_exists('paging_edit')) {
							$obj = new paging;
							$obj->toggle($array);
						}
						break;
				}

				//redirect the user
				if (in_array($_POST['action'], array('copy', 'delete', 'toggle'))) {
					header('Location: paging_edit.php?id='.$id);
					exit;
				}
			}

		//check for all required data
			$msg = '';
			if (empty($paging_extension)) { $msg .= $text['message-required']." ".$text['label-paging_extension']."<br>\n"; }
			//if (strlen($dialplan_uuid) == 0) { $msg .= $text['message-required']." ".$text['label-dialplan_uuid']."<br>\n"; }
			//if (strlen($paging_pin_number) == 0) { $msg .= $text['message-required']." ".$text['label-paging_pin_number']."<br>\n"; }
			//if (strlen($paging_destinations) == 0) { $msg .= $text['message-required']." ".$text['label-paging_destinations']."<br>\n"; }
			//if (strlen($paging_caller_id_name) == 0) { $msg .= $text['message-required']." ".$text['label-paging_caller_id_name']."<br>\n"; }
			//if (strlen($paging_caller_id_number) == 0) { $msg .= $text['message-required']." ".$text['label-paging_caller_id_number']."<br>\n"; }
			//if (strlen($paging_sound) == 0) { $msg .= $text['message-required']." ".$text['label-paging_sound']."<br>\n"; }
			//if (strlen($paging_delay) == 0) { $msg .= $text['message-required']." ".$text['label-paging_delay']."<br>\n"; }
			//if (strlen($paging_mute) == 0) { $msg .= $text['message-required']." ".$text['label-paging_mute']."<br>\n"; }
			//if (strlen($paging_destination_status) == 0) { $msg .= $text['message-required']." ".$text['label-paging_destination_status']."<br>\n"; }
			//if (strlen($paging_hangup_all) == 0) { $msg .= $text['message-required']." ".$text['label-paging_hangup_all']."<br>\n"; }
			//if (strlen($paging_schedule_hangup) == 0) { $msg .= $text['message-required']." ".$text['label-paging_schedule_hangup']."<br>\n"; }
			//if (strlen($paging_enabled) == 0) { $msg .= $text['message-required']." ".$text['label-paging_enabled']."<br>\n"; }
			if (empty($paging_description)) { $msg .= $text['message-required']." ".$text['label-paging_description']."<br>\n"; }
			if (!empty($msg) && empty($_POST["persistformvar"])) {
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

		//add the paging_uuid
			if (!is_uuid($_POST["paging_uuid"])) {
				$paging_uuid = uuid();
			}

		//add the dialplan_uuid
			if (empty($_POST["dialplan_uuid"]) || !is_uuid($_POST["dialplan_uuid"])) {
				$dialplan_uuid = uuid();
			}

		//add the paging name
			$paging_name = 'paging_'.$paging_extension;

		//build the destinations string
			$destinations = '';
			if (is_array($paging_destinations)) {
				foreach ($paging_destinations as $row) {
					if (!empty($row['destination_number']) && trim($row['destination_number']) != '') {
						$destinations .= ($destinations != '' ? ',' : '').$row['destination_number'];
					}
				}
			}

		//build the xml dialplan
			$dialplan_xml = "<extension name=\"$paging_name\">\n";
			$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^".xml::sanitize($paging_extension)."\$\" >\n";
			$dialplan_xml .= "		<action application=\"set\" data=\"caller_id_name=".xml::sanitize($paging_caller_id_name)."\" />\n";
			$dialplan_xml .= "		<action application=\"set\" data=\"caller_id_number=".xml::sanitize($paging_caller_id_number)."\" />\n";
			$dialplan_xml .= "		<action application=\"set\" data=\"pin_number=".xml::sanitize($paging_pin_number)."\" />\n";
			$dialplan_xml .= "		<action application=\"set\" data=\"destinations=".xml::sanitize($destinations). "\" />\n";
			$dialplan_xml .= "		<action application=\"set\" data=\"moderator=false\" />\n";
			$dialplan_xml .= "		<action application=\"set\" data=\"mute=".xml::sanitize($paging_mute)."\" />\n";
			$dialplan_xml .= "		<action application=\"set\" data=\"delay=".xml::sanitize($paging_delay)."\" />\n";
			$dialplan_xml .= "		<action application=\"set\" data=\"check_destination_status=".xml::sanitize($paging_destination_status)."\" />\n";
			if ($paging_hangup_all) {
				$dialplan_xml .= "		<action application=\"set\" data=\"api_hangup_hook=conference page-\${destination_number}@\${domain_name} hup all\" />\n";
			}
			if (!empty($paging_schedule_hangup) && is_numeric($paging_schedule_hangup) && $paging_schedule_hangup > 0) {
				$dialplan_xml .= "		<action application=\"set\" data=\"execute_on_answer=sched_hangup +".xml::sanitize($paging_schedule_hangup)." allotted_timeout\" />\n";
			}
			$dialplan_xml .= "		<action application=\"lua\" data=\"page.lua\" />\n";
			$dialplan_xml .= "	</condition>\n";
			$dialplan_xml .= "</extension>\n";

		//build the dialplan array
			$array["dialplans"][0]["domain_uuid"] = $_SESSION["domain_uuid"];
			$array["dialplans"][0]["dialplan_uuid"] = $dialplan_uuid;
			$array["dialplans"][0]["dialplan_name"] = $paging_name;
			$array["dialplans"][0]["dialplan_number"] = $paging_extension;
			$array["dialplans"][0]["dialplan_context"] = $_SESSION['domain_name'];
			$array["dialplans"][0]["dialplan_continue"] = 'false';
			$array["dialplans"][0]["dialplan_xml"] = $dialplan_xml;
			$array["dialplans"][0]["dialplan_order"] = "240";
			$array["dialplans"][0]["dialplan_enabled"] = $paging_enabled;
			$array["dialplans"][0]["dialplan_description"] = $paging_description;
			$array["dialplans"][0]["app_uuid"] = "1d61fb65-1eec-bc73-a6ee-a6203b4fe6f2";

		//prepare the array
			$array['paging'][0]['paging_uuid'] = $paging_uuid;
			$array['paging'][0]['paging_extension'] = $paging_extension;
			$array['paging'][0]['dialplan_uuid'] = $dialplan_uuid;
			$array['paging'][0]['paging_pin_number'] = $paging_pin_number;
			$array['paging'][0]['paging_caller_id_name'] = $paging_caller_id_name;
			$array['paging'][0]['paging_caller_id_number'] = $paging_caller_id_number;
			$array['paging'][0]['paging_sound'] = $paging_sound;
			$array['paging'][0]['paging_delay'] = $paging_delay;
			$array['paging'][0]['paging_mute'] = $paging_mute;
			$array['paging'][0]['paging_destination_status'] = $paging_destination_status;
			$array['paging'][0]['paging_hangup_all'] = $paging_hangup_all;
			$array['paging'][0]['paging_schedule_hangup'] = $paging_schedule_hangup;
			$array['paging'][0]['paging_enabled'] = $paging_enabled;
			$array['paging'][0]['paging_description'] = $paging_description;
			$y = 0;
			if (is_array($paging_destinations)) {
				foreach ($paging_destinations as $row) {
					if (strlen($row['destination_number']) > 0) {
						$array['paging'][0]['paging_destinations'][$y]['paging_destination_uuid'] = $row["paging_destination_uuid"];
						$array['paging'][0]['paging_destinations'][$y]['destination_number'] = $row["destination_number"];
						$array['paging'][0]['paging_destinations'][$y]['destination_enabled'] = $row["destination_enabled"];
						$array['paging'][0]['paging_destinations'][$y]['destination_description'] = $row["destination_description"];
						$y++;
					}
				}
			}

		//save the data
			$database->app_name = 'paging';
			$database->app_uuid = 'bae044dd-e773-471c-a890-5220ebca3bc9';
			$database->save($array);

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				//header('Location: paging.php');
				header('Location: paging_edit.php?id='.urlencode($paging_uuid));
				return;
			}
	}

//pre-populate the form
	if (!empty($_GET['id']) && is_uuid($_GET['id']) && (empty($_POST["persistformvar"]) || $_POST["persistformvar"] != "true")) {
		$paging_uuid = $_GET['id'];
		$sql = "select ";
		$sql .= " paging_uuid, ";
		$sql .= " paging_extension, ";
		$sql .= " dialplan_uuid, ";
		$sql .= " paging_pin_number, ";
		$sql .= " paging_caller_id_name, ";
		$sql .= " paging_caller_id_number, ";
		$sql .= " paging_sound, ";
		$sql .= " paging_delay , ";
		$sql .= " paging_mute , ";
		$sql .= " paging_destination_status , ";
		$sql .= " paging_hangup_all , ";
		$sql .= " paging_schedule_hangup, ";
		$sql .= " paging_enabled , ";
		$sql .= " paging_description ";
		$sql .= "from v_paging ";
		$sql .= "where paging_uuid = :paging_uuid ";
		$parameters['paging_uuid'] = $paging_uuid;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$paging_extension = $row["paging_extension"];
			$dialplan_uuid = $row["dialplan_uuid"];
			$paging_pin_number = $row["paging_pin_number"];
			$paging_caller_id_name = $row["paging_caller_id_name"];
			$paging_caller_id_number = $row["paging_caller_id_number"];
			$paging_sound = $row["paging_sound"];
			$paging_delay = $row["paging_delay"];
			$paging_mute = $row["paging_mute"];
			$paging_destination_status = $row["paging_destination_status"];
			$paging_hangup_all = $row["paging_hangup_all"];
			$paging_schedule_hangup = $row["paging_schedule_hangup"];
			$paging_enabled = $row["paging_enabled"];
			$paging_description = $row["paging_description"];
		}
		unset($sql, $parameters, $row);
	}

//get the child data
	if (is_uuid($paging_uuid)) {
		$sql = "select ";
		$sql .= " paging_destination_uuid, ";
		$sql .= " paging_uuid, ";
		$sql .= " destination_number, ";
		$sql .= " destination_enabled , ";
		$sql .= " destination_description ";
		$sql .= "from v_paging_destinations ";
		$sql .= "where paging_uuid = :paging_uuid ";
		$parameters['paging_uuid'] = $paging_uuid;
		$paging_destinations = $database->select($sql, $parameters, 'all');
		unset ($sql, $parameters);
	}

//add the $paging_destination_uuid
	if (!is_uuid($paging_destination_uuid)) {
		$paging_destination_uuid = uuid();
	}

//add an empty row
	$x = is_array($paging_destinations) ? count($paging_destinations) : 0;
	$paging_destinations[$x]['paging_uuid'] = $paging_uuid;
	$paging_destinations[$x]['paging_destination_uuid'] = uuid();
	$paging_destinations[$x]['destination_number'] = '';
	$paging_destinations[$x]['destination_enabled'] = '';
	$paging_destinations[$x]['destination_description'] = '';

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-paging'];
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<input class='formfld' type='hidden' name='paging_uuid' value='".escape($paging_uuid)."'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-paging']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$button_icon_back,'id'=>'btn_back','collapse'=>'hide-xs','style'=>'margin-right: 15px;','link'=>'paging.php']);
	if ($action == 'update') {
		if (permission_exists('paging_add')) {
			echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$button_icon_copy,'id'=>'btn_copy','name'=>'btn_copy','style'=>'margin-left: 15px;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
		}
		if (permission_exists('paging_delete')) {
			echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$button_icon_delete,'id'=>'btn_delete','name'=>'btn_delete','style'=>'margin-left: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
		}
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$button_icon_save,'id'=>'btn_save','collapse'=>'hide-xs']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['title_description-paging']."\n";
	echo "<br /><br />\n";

	if ($action == 'update') {
		if (permission_exists('paging_add')) {
			echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'copy','onclick'=>"modal_close();"])]);
		}
		if (permission_exists('paging_delete')) {
			echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
		}
	}

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-paging_extension']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='paging_extension' maxlength='255' value='".escape($paging_extension)."'>\n";
	echo "<br />\n";
	echo $text['description-paging_extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-paging_pin_number']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='paging_pin_number' maxlength='255' value='".escape($paging_pin_number)."'>\n";
	echo "<br />\n";
	echo $text['description-paging_pin_number']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-paging_destinations']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<table>\n";
	echo "		<tr>\n";
	echo "			<td class='vtable'>".$text['label-destination_number']."</td>\n";
	echo "			<td class='vtable'>".$text['label-destination_enabled']."</td>\n";
	echo "			<td class='vtable'>".$text['label-destination_description']."</td>\n";
	if (is_array($paging_destinations) && @sizeof($paging_destinations) > 1 && permission_exists('paging_destination_delete')) {
		echo "			<td class='vtable edit_delete_checkbox_all' onmouseover=\"swap_display('delete_label_details', 'delete_toggle_details');\" onmouseout=\"swap_display('delete_label_details', 'delete_toggle_details');\">\n";
		echo "				<span id='delete_label_details'>".$text['label-action']."</span>\n";
		echo "				<span id='delete_toggle_details'><input type='checkbox' id='checkbox_all_details' name='checkbox_all' onclick=\"edit_all_toggle('details'); checkbox_on_change(this);\"></span>\n";
		echo "			</td>\n";
	}
	echo "		</tr>\n";
	$x = 0;
	if (permission_exists('paging_destination_edit')) {
		foreach($paging_destinations as $row) {
			echo "			<tr>\n";
			echo "				<td class='formfld'>\n";
				echo "			<input type='hidden' name='paging_destinations[$x][paging_uuid]' value=\"".escape($row["paging_uuid"])."\">\n";
				echo "			<input type='hidden' name='paging_destinations[$x][paging_destination_uuid]' value=\"".escape($row["paging_destination_uuid"])."\">\n";
			echo "				<input class='formfld' type='text' name='paging_destinations[$x][destination_number]' maxlength='255' value=\"".escape($row["destination_number"])."\">\n";
			echo "			</td>\n";
			echo "				<td class='formfld'>\n";
			if ($input_toggle_style_switch) {
				echo "	<span class='switch'>\n";
			}
			echo "	<select class='formfld' id='destination_enabled' name='paging_destinations[$x][destination_enabled]'>\n";
			echo "		<option value='true' ".($row['destination_enabled'] == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
			echo "		<option value='false' ".($row['destination_enabled'] == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
			echo "	</select>\n";
			if ($input_toggle_style_switch) {
				echo "		<span class='slider'></span>\n";
				echo "	</span>\n";
			}
			echo "			</td>\n";
			echo "				<td class='formfld'>\n";
			echo "				<textarea class='formfld' name='paging_destinations[$x][destination_description]' style='line-height: 1;'>".escape($row["destination_description"])."</textarea>\n";
			echo "			</td>\n";
			if (is_array($paging_destinations) && @sizeof($paging_destinations) > 1 && permission_exists('paging_destination_delete')) {
				if (is_uuid($row['paging_destination_uuid'])) {
					echo "		<td class='vtable' style='text-align: center; padding-bottom: 3px;'>\n";
					echo "			<input type='checkbox' name='paging_destinations[".$x."][checked]' value='true' class='chk_delete checkbox_details' onclick=\"checkbox_on_change(this);\">\n";
					echo "		</td>\n";
				}
				else {
					echo "		<td></td>\n";
				}
			}
			echo "		</tr>\n";
			$x++;
		}
	}
	echo "	</table>\n";
	echo "<br />\n";
	echo $text['description-destination_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-paging_caller_id_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='paging_caller_id_name' maxlength='255' value='".escape($paging_caller_id_name)."'>\n";
	echo "<br />\n";
	echo $text['description-paging_caller_id_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-paging_caller_id_number']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='paging_caller_id_number' maxlength='255' value='".escape($paging_caller_id_number)."'>\n";
	echo "<br />\n";
	echo $text['description-paging_caller_id_number']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	// echo "<tr>\n";
	// echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	// echo "	".$text['label-paging_sound']."\n";
	// echo "</td>\n";
	// echo "<td class='vtable' style='position: relative;' align='left'>\n";
	// echo "	<input class='formfld' type='text' name='paging_sound' maxlength='255' value='".escape($paging_sound)."'>\n";
	// echo "<br />\n";
	// echo $text['description-paging_sound']."\n";
	// echo "</td>\n";
	// echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-paging_delay']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if ($input_toggle_style_switch) {
		echo "	<span class='switch'>\n";
	}
	echo "	<select class='formfld' id='paging_delay' name='paging_delay'>\n";
	echo "		<option value='true' ".($paging_delay == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
	echo "		<option value='false' ".($paging_delay == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
	echo "	</select>\n";
	if ($input_toggle_style_switch) {
		echo "		<span class='slider'></span>\n";
		echo "	</span>\n";
	}
	echo "<br />\n";
	echo $text['description-paging_delay']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-paging_mute']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if ($input_toggle_style_switch) {
		echo "	<span class='switch'>\n";
	}
	echo "	<select class='formfld' id='paging_mute' name='paging_mute'>\n";
	echo "		<option value='true' ".($paging_mute == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
	echo "		<option value='false' ".($paging_mute == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
	echo "	</select>\n";
	if ($input_toggle_style_switch) {
		echo "		<span class='slider'></span>\n";
		echo "	</span>\n";
	}
	echo "<br />\n";
	echo $text['description-paging_mute']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-paging_destination_status']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if ($input_toggle_style_switch) {
		echo "	<span class='switch'>\n";
	}
	echo "	<select class='formfld' id='paging_destination_status' name='paging_destination_status'>\n";
	echo "		<option value='true' ".($paging_destination_status == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
	echo "		<option value='false' ".($paging_destination_status == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
	echo "	</select>\n";
	if ($input_toggle_style_switch) {
		echo "		<span class='slider'></span>\n";
		echo "	</span>\n";
	}
	echo "<br />\n";
	echo $text['description-paging_destination_status']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-paging_hangup_all']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if ($input_toggle_style_switch) {
		echo "	<span class='switch'>\n";
	}
	echo "	<select class='formfld' id='paging_hangup_all' name='paging_hangup_all'>\n";
	echo "		<option value='true' ".($paging_hangup_all == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
	echo "		<option value='false' ".($paging_hangup_all == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
	echo "	</select>\n";
	if ($input_toggle_style_switch) {
		echo "		<span class='slider'></span>\n";
		echo "	</span>\n";
	}
	echo "<br />\n";
	echo $text['description-paging_hangup_all']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-paging_schedule_hangup']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "  <input class='formfld' type='text' name='paging_schedule_hangup' maxlength='255' value='".escape($paging_schedule_hangup)."'>\n";
	echo "<br />\n";
	echo $text['description-paging_schedule_hangup']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-paging_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if ($input_toggle_style_switch) {
		echo "	<span class='switch'>\n";
	}
	echo "	<select class='formfld' id='paging_enabled' name='paging_enabled'>\n";
	echo "		<option value='true' ".($paging_enabled == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
	echo "		<option value='false' ".($paging_enabled == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
	echo "	</select>\n";
	if ($input_toggle_style_switch) {
		echo "		<span class='slider'></span>\n";
		echo "	</span>\n";
	}
	echo "<br />\n";
	echo $text['description-paging_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-paging_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<textarea class='formfld' name='paging_description' style='width: 185px; height: 80px;'>".escape($paging_description)."</textarea>\n";
	echo "<br />\n";
	echo $text['description-paging_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br /><br />";

	if (!empty($dialplan_uuid)) {
		echo "<input type='hidden' name='dialplan_uuid' value='".escape($dialplan_uuid)."'>\n";
	}
	if (!empty($paging_uuid)) {
		echo "<input type='hidden' name='paging_uuid' value='".escape($paging_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
