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
	Copyright (C) 2008-2015 All Rights Reserved.

	Contributor(s):
	KonradSC <konrd@yahoo.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('wizard_template_add') || permission_exists('wizard_template_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$wizard_template_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
		$wizard_template_uuid = uuid();
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$wizard_template_name = check_str($_POST["wizard_template_name"]);
		$emergency_caller_id_number = check_str($_POST["emergency_caller_id_number"]);
		$outbound_caller_id_number = check_str($_POST["outbound_caller_id_number"]);
		$call_group = check_str($_POST["call_group"]);
		$toll_allow = check_str($_POST["toll_allow"]);
		$hold_music = check_str($_POST["hold_music"]);
		$user_record = check_str($_POST["user_record"]);
		$call_timeout = check_str($_POST["call_timeout"]);
		$forward_user_not_registered_destination = check_str($_POST["forward_user_not_registered_destination"]);
		$forward_user_not_registered_enabled = check_str($_POST["forward_user_not_registered_enabled"]);
		$time_zone = check_str($_POST["time_zone"]);
		$description = check_str($_POST["description"]);
		//$wizard_group_uuid = check_str($_POST["group_uuid"]);   group_uuid_name
		$wizard_group_uuid = check_str($_POST["wizard_user_uuid"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$wizard_template_name = check_str($_POST["wizard_template_name"]);
	}

	//check for all required data
		if (strlen($wizard_template_name) == 0) { $msg .= $text['message-required']." ".$text['label-wizard_template_name']."<br>\n"; }
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

	//add or update the database
		if ($_POST["persistformvar"] != "true") {
			if ($action == "add" || $action == "update" && permission_exists('wizard_template_add')) {
				$i=0;
			//v_wizard_templates
				$array["wizard_templates"][$i]["domain_uuid"] = $domain_uuid;
				$array["wizard_templates"][$i]["wizard_template_uuid"] = $wizard_template_uuid;
				$array["wizard_templates"][$i]["wizard_template_name"] = $wizard_template_name;
				$array["wizard_templates"][$i]["emergency_caller_id_number"] = $emergency_caller_id_number;
				$array["wizard_templates"][$i]["outbound_caller_id_number"] = $outbound_caller_id_number;
				$array["wizard_templates"][$i]["call_group"] = $call_group;
				$array["wizard_templates"][$i]["toll_allow"] = $toll_allow;
				$array["wizard_templates"][$i]["hold_music"] = $hold_music;
				$array["wizard_templates"][$i]["user_record"] = $user_record;
				$array["wizard_templates"][$i]["call_timeout"] = $call_timeout;
				$array["wizard_templates"][$i]["forward_user_not_registered_destination"] = $forward_user_not_registered_destination;
				$array["wizard_templates"][$i]["forward_user_not_registered_enabled"] = $forward_user_not_registered_enabled;
				$array["wizard_templates"][$i]["description"] = $description;
				$array["wizard_templates"][$i]["wizard_group_uuid"] = $wizard_group_uuid;
				$array["wizard_templates"][$i]["time_zone"] = $time_zone;
			//save to the datbase
				$database = new database;
				$database->app_name = 'wizard';
				$database->app_uuid = null;
				$database->save($array);
				$message = $database->message;
				//echo "<pre>".print_r($message, true)."<pre>\n";
				//exit;
				unset($database,$array,$i);				
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				} else {
					$_SESSION["message"] = $text['message-update'];
				}
				header("Location: wizard_templates.php");
				return;

			} 

		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$wizard_template_uuid = check_str($_GET["id"]);
		$sql = "select * from v_wizard_templates ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and wizard_template_uuid = '$wizard_template_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$wizard_template_name = $row["wizard_template_name"];
			$emergency_caller_id_number = $row["emergency_caller_id_number"];
			$outbound_caller_id_number = $row["outbound_caller_id_number"];
			$call_group = $row["call_group"];
			$toll_allow = $row["toll_allow"];
			$hold_music = $row["hold_music"];
			$user_record = $row["user_record"];
			$call_timeout = $row["call_timeout"];
			$forward_user_not_registered_destination = $row["forward_user_not_registered_destination"];
			$forward_user_not_registered_enabled = $row["forward_user_not_registered_enabled"];
			$time_zone = $row["time_zone"];
			$description = $row["description"];
			$wizard_group_uuid = $row["wizard_group_uuid"];
		}
		unset ($prep_statement);
	}

if ($call_timeout == "") {
	$call_timeout = "30";
}
if ($time_zone == "") {
	$time_zone = $_SESSION['domain']['time_zone']['name'];
}

//show the header
	require_once "resources/header.php";
	
//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap' valign='top'><b>".$text['title-pin_number']."</b><br><br></td>\n";
	echo "<td width='70%' align='right' valign='top'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='wizard_templates.php'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>";
	echo "</td>\n";
	echo "</tr>\n";

	//Extension Template Name
	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-wizard_template_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='wizard_template_name' autocomplete='off' maxlength='255' value=\"$wizard_template_name\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-wizard_template_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";	
	//Emergency Caller-ID
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-emergency_caller_id_number']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='emergency_caller_id_number' autocomplete='off' maxlength='255' min='0' step='1' value=\"$emergency_caller_id_number\">\n";
	echo "<br />\n";
	echo $text['description-emergency_caller_id_number']."\n";
	echo "</td>\n";
	echo "</tr>\n";	
	//Outbound Caller-ID
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-outbound_caller_id_number']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='outbound_caller_id_number' autocomplete='off' maxlength='255' min='0' step='1' value=\"$outbound_caller_id_number\">\n";
	echo "<br />\n";
	echo $text['description-outbound_caller_id_number']."\n";
	echo "</td>\n";
	echo "</tr>\n";	
	//Call Group
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_group']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (is_array($_SESSION['call group']['name'])) {
		echo "	<select class='formfld' name='call_group'>\n";
		echo "		<option value=''></option>\n";
		foreach ($_SESSION['call group']['name'] as $name) {
			if ($_SESSION['call group']['name'] == $call_group) {
				echo "		<option value='$name' selected='selected'>$name</option>\n";
			}
			else {
				echo "		<option value='$name'>$name</option>\n";
			}
		}
		echo "	</select>\n";
	} else {
		echo "	<input class='formfld' type='text' name='call_group' maxlength='255' value=\"$call_group\">\n";
	}
	echo "<br />\n";
	echo $text['description-call_group']."\n";
	echo "</td>\n";
	echo "</tr>\n";		
	//Toll Allow
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-toll_allow']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";;
	echo "    <input class='formfld' type='text' name='toll_allow' maxlength='255' value=\"$toll_allow\">\n";
	echo "<br />\n";
	echo $text['description_toll_allow']."\n";
	echo "</td>\n";
	echo "</tr>\n";				
	//Hold Music
	if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/music_on_hold')) {
		echo "<tr>\n";
		echo "<td width=\"30%\" class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-hold_music']."\n";
		echo "</td>\n";
		echo "<td width=\"70%\" class='vtable' align='left'>\n";
		require_once "app/music_on_hold/resources/classes/switch_music_on_hold.php";
		$moh = new switch_music_on_hold;
		echo $moh->select('hold_music', $hold_music);
		echo "	<br />\n";
		echo $text['description-hold_music']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	//User Record
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-user_record']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='user_record'>\n";
	echo "    <option value=''>".$text['label-user_record_none']."</option>\n";
	if ($user_record == "all") {
		echo "    <option value='all' selected='selected'>".$text['label-user_record_all']."</option>\n";
	}
	else {
		echo "    <option value='all'>".$text['label-user_record_all']."</option>\n";
	}
	if ($user_record == "local") {
		echo "    <option value='local' selected='selected'>".$text['label-user_record_local']."</option>\n";
	}
	else {
		echo "    <option value='local'>".$text['label-user_record_local']."</option>\n";
	}
	if ($user_record == "inbound") {
		echo "    <option value='inbound' selected='selected'>".$text['label-user_record_inbound']."</option>\n";
	}
	else {
		echo "    <option value='inbound'>".$text['label-user_record_inbound']."</option>\n";
	}
	if ($user_record == "outbound") {
		echo "    <option value='outbound' selected='selected'>".$text['label-user_record_outbound']."</option>\n";
	}
	else {
		echo "    <option value='outbound'>".$text['label-user_record_outbound']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-user_record']."\n";
	echo "</td>\n";
	echo "</tr>\n";	
	//Call Timeout
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_timeout']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='number' name='call_timeout' maxlength='255' min='1' step='1' value=\"$call_timeout\">\n";
	echo "<br />\n";
	echo $text['description-call_timeout']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	//Forward Not Registered
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-not_registered']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$on_click = "document.getElementById('forward_user_not_registered_destination').focus();";
	echo "	<label for='forward_user_not_registered_disabled'><input type='radio' name='forward_user_not_registered_enabled' id='forward_user_not_registered_disabled' onclick=\"\" value='false' ".(($forward_user_not_registered_enabled == "false" || $forward_user_not_registered_enabled == "") ? "checked='checked'" : null)." /> ".$text['label-disabled']."</label> \n";
	echo "	<label for='forward_user_not_registered_enabled'><input type='radio' name='forward_user_not_registered_enabled' id='forward_user_not_registered_enabled' onclick=\"$on_click\" value='true' ".(($forward_user_not_registered_enabled == "true") ? "checked='checked'" : null)."/> ".$text['label-enabled']."</label> \n";
	unset($on_click);
	echo "&nbsp;&nbsp;&nbsp;";
	echo "	<input class='formfld' type='text' name='forward_user_not_registered_destination' id='forward_user_not_registered_destination' maxlength='255' placeholder=\"".$text['label-destination']."\" value=\"".$forward_user_not_registered_destination."\">\n";
	echo "	<br />".$text['description-not_registered']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	//User Group
	if ((permission_exists("user_add") && $action == 'add') || (permission_exists("user_edit"))) {
		echo "	<tr>";
		echo "		<td class='vncellreq' valign='top'>".$text['label-user_group']."</td>";
		echo "		<td class='vtable'>";
		$sql = "select * from v_groups ";
		$sql .= "where (domain_uuid = '".$domain_uuid."' or domain_uuid is null) ";
		$sql .= "order by domain_uuid desc, group_name asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		if ($result_count > 0) {
			echo "<select name=\"wizard_user_uuid\" class='formfld' style='width: auto; margin-right: 3px;'>\n";
			echo "	<option value=''></option>\n";
			foreach($result as $field) {
				if ($field['group_uuid'] == $wizard_group_uuid) { $selected = "selected='selected'"; } else { $selected = ''; }
				echo "	<option value='".$field['group_uuid']."' $selected>".$field['group_name']."</option>\n";
			}
			echo "</select>";
		}
		unset($sql, $prep_statement, $result);

		echo "		</td>";
		echo "	</tr>";
	}

	//Time Zone
	echo "	<tr>\n";
	echo "	<td width='20%' class=\"vncell\" valign='top'>\n";
	echo "		".$text['label-time_zone']."\n";
	echo "	</td>\n";
	echo "	<td class=\"vtable\" align='left'>\n";
	echo "		<select id='time_zone' name='time_zone' class='formfld' style=''>\n";
	echo "		<option value=''></option>\n";
	//$list = DateTimeZone::listAbbreviations();
    $time_zone_identifiers = DateTimeZone::listIdentifiers();
	$previous_category = '';
	$x = 0;
	foreach ($time_zone_identifiers as $key => $row) {
		$time_zone_row = explode("/", $row);
		$category = $time_zone_row[0];
		if ($category != $previous_category) {
			if ($x > 0) {
				echo "		</optgroup>\n";
			}
			echo "		<optgroup label='".$category."'>\n";
		}
		//if ($row == $user_settings['domain']['time_zone']['name']) {
		if ($row == $time_zone) {
			echo "			<option value='".$row."' selected='selected'>".$row."</option>\n";
		}
		else {
			echo "			<option value='".$row."'>".$row."</option>\n";
		}
		$previous_category = $category;
		$x++;
	}
	echo "		</select>\n";
	echo "		<br />\n";
	echo "		".$text['description-time_zone']."<br />\n";
	echo "	</td>\n";
	echo "	</tr>\n";
	
	//Description
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <textarea class='formfld' name='description' rows='4'>$description</textarea>\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='wizard_template_uuid' value='$wizard_template_uuid'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";

?>